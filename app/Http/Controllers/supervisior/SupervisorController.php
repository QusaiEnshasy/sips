<?php

namespace App\Http\Controllers\supervisior;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Application;
use App\Models\JisrSubmission;
use App\Models\JisrTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;

class SupervisorController extends Controller
{
    private function buildJisrReviewsPayload(User $supervisor): array
    {
        // Prevent hard failures on environments where Jisr tables are not migrated yet.
        if (! Schema::hasTable('jisr_submissions') || ! Schema::hasTable('jisr_tasks')) {
            return [
                'stats' => [
                    'total_submissions' => 0,
                    'pending_reviews' => 0,
                    'accepted_submissions' => 0,
                    'rejected_submissions' => 0,
                ],
                'submissions' => collect(),
            ];
        }

        $submissions = JisrSubmission::with(['task', 'user'])
            ->whereHas('user', function ($query) use ($supervisor) {
                $query->where('role', 'student')
                    ->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->orderByRaw("CASE WHEN status = 'pending_review' THEN 0 WHEN status = 'rejected' THEN 1 ELSE 2 END")
            ->latest('submitted_at')
            ->get();

        $tasksCount = JisrTask::query()->count();

        return [
            'stats' => [
                'total_submissions' => $submissions->count(),
                'pending_reviews' => $submissions->where('status', 'pending_review')->count(),
                'accepted_submissions' => $submissions->where('status', 'accepted')->count(),
                'rejected_submissions' => $submissions->where('status', 'rejected')->count(),
            ],
            'submissions' => $submissions->map(function (JisrSubmission $submission) use ($tasksCount) {
                $student = $submission->user;
                $task = $submission->task;
                $acceptedCount = JisrSubmission::query()
                    ->where('user_id', $submission->user_id)
                    ->where('status', 'accepted')
                    ->count();

                return [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'feedback' => $submission->feedback,
                    'content' => $submission->content,
                    'submitted_at' => optional($submission->submitted_at)->toISOString(),
                    'student' => [
                        'id' => $student?->id,
                        'name' => $student?->name,
                        'email' => $student?->email,
                        'university_id' => $student?->university_id,
                        'is_in_jisr' => (bool) $student?->is_in_jisr,
                        'accepted_tasks' => $acceptedCount,
                        'total_tasks' => $tasksCount,
                    ],
                    'task' => [
                        'id' => $task?->id,
                        'title' => $task?->title,
                        'description' => $task?->description,
                        'instructions' => $task?->instructions,
                        'type' => $task?->type,
                        'max_score' => $task?->max_score,
                        'order_number' => $task?->order_number,
                    ],
                    'attachments' => collect($submission->attachments ?? [])->values()->map(function ($attachment, $index) use ($submission) {
                        $path = (string) ($attachment['path'] ?? '');

                        return [
                            'name' => $attachment['name'] ?? basename($path),
                            'path' => $path,
                            'url' => $path ? Storage::disk('public')->url($path) : null,
                            'view_url' => $path ? route('supervisor.jisr-attachments.show', ['submission' => $submission->id, 'index' => $index]) : null,
                            'download_url' => $path ? route('supervisor.jisr-attachments.download', ['submission' => $submission->id, 'index' => $index]) : null,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    private function actionResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function __construct(private readonly NotificationService $notifications) {}

    private function ensureRole(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'supervisor', 403);
    }

    public function supervisorActiveStudentAcouunt(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole();

        $student = User::where('id', $id)->where('role', 'student')->firstOrFail();

        if ($student->supervisor_code !== $request->user()->supervisor_code) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'You are not the supervisor of this student.'], 403);
            }
            return back()->with('error', 'You are not the supervisor of this student.');
        }

        $updates = ['status' => 'active'];
        $optionalColumns = [
            'skill_test_required' => true,
            'skill_test_passed' => false,
            'is_in_jisr' => false,
            'skill_test_completed_at' => null,
            'jisr_completed_at' => null,
        ];

        foreach ($optionalColumns as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $updates[$column] = $value;
            }
        }

        $student->forceFill($updates)->save();

        $this->notifications->notifyUser(
            userId: $id,
            title: 'Student Account Activated',
            description: 'Your student account has been activated successfully.',
            type: 'success',
            meta: ['category' => 'auth']
        );

        return $this->actionResponse($request, 'successfully activated the student account.');
    }

    public function rejectActiveStudentAcouunt(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $reason = trim($validated['reason']);

        $student = User::where('id', $id)->where('role', 'student')->firstOrFail();
        $student->status = 'rejected';
        $student->save();

        $this->notifications->notifyUser(
            userId: $id,
            title: 'Student Account Rejected',
            description: 'Your student account has been rejected. Please contact your supervisor for more information.',
            type: 'error',
            meta: ['category' => 'auth', 'reason' => $reason]
        );

        return $this->actionResponse($request, 'successfully rejected the student account.');
    }

    public function dashboard()
    {
        $this->ensureRole();

        return view('spa');
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $this->ensureRole();

        $supervisor = Auth::user();

        $students = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->get();

        $totalStudents = $students->count();
        $pendingStudents = $students->where('status', 'pending')->count();
        $activeStudents = $students->where('status', 'active')->count();
        $rejectedStudents = $students->where('status', 'rejected')->count();

        $approvedApplications = Application::with(['student', 'opportunity'])
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereHas('student', function ($q) use ($supervisor) {
                $q->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->get();

        $applicationIds = $approvedApplications->pluck('id');
        $totalTasks = Task::whereIn('application_id', $applicationIds)->count();
        $doneTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'done')->count();
        $progressTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'progress')->count();
        $todoTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'todo')->count();

        $studentsPayload = $approvedApplications->map(function ($application) {
            $progress = $this->calculateProgress($application);
            $tasksTotal = Task::where('application_id', $application->id)->count();
            $tasksDone = Task::where('application_id', $application->id)->where('status', 'done')->count();

            return [
                'id' => $application->student?->id,
                'application_id' => $application->id,
                'name' => $application->student?->name,
                'email' => $application->student?->email,
                'is_in_jisr' => (bool) ($application->student?->is_in_jisr),
                'initials' => collect(explode(' ', (string) $application->student?->name))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                'company' => $application->opportunity?->companyUser?->company_name ?: $application->opportunity?->companyUser?->name,
                'program' => $application->opportunity?->title,
                'status' => $progress >= 75 ? 'on-track' : 'at-risk',
                'hoursCompleted' => $progress,
                'hoursTotal' => 100,
                'tasksCompleted' => $tasksDone,
                'tasksTotal' => $tasksTotal,
                'board_url' => route('tasks.board', ['application' => $application->id]),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'supervisor_code' => $supervisor->supervisor_code,
                'quick_stats' => [
                    'total_students' => $totalStudents,
                    'pending_students' => $pendingStudents,
                    'active_students' => $activeStudents,
                    'rejected_students' => $rejectedStudents,
                    'total_tasks' => $totalTasks,
                    'todo_tasks' => $todoTasks,
                    'progress_tasks' => $progressTasks,
                    'done_tasks' => $doneTasks,
                ],
                'students' => $studentsPayload,
            ],
        ]);
    }

    public function studentsPage(Request $request)
    {
        $this->ensureRole();

        $supervisor = Auth::user();

        $pendingStudents = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $activeStudentsRaw = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->where('status', 'active')
            ->latest()
            ->get();

        $approvedStudents = $activeStudentsRaw->map(function ($student) {
            $application = Application::with('opportunity')
                ->where('student_id', $student->id)
                ->where('final_status', 'approved')
                ->latest()
                ->first();

            $progress = 0;
            $statusLabel = 'On Track';

            if ($application) {
                $progress = $this->calculateProgress($application);
                if ($application->training_completed_at) {
                    $statusLabel = 'Completed';
                } else {
                    $statusLabel = $progress >= 75 ? 'On Track' : 'At Risk';
                }
            }

            return [
                'student'      => $student,
                'application'  => $application,
                'opportunity'  => $application?->opportunity,
                'progress'     => $progress,
                'status_label' => $statusLabel,
            ];
        })->values();

        $totalStudents = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->count();

        $rejectedStudents = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->where('status', 'rejected')
            ->latest()
            ->get();

        $totalPending = $pendingStudents->count();
        $totalApproved = $approvedStudents->count();
        $totalRejected = $rejectedStudents->count();

        if ($request->expectsJson() || $request->ajax()) {
            $pendingTrainingApplications = Application::with(['student', 'opportunity'])
                ->whereHas('student', function ($q) use ($supervisor) {
                    $q->where('supervisor_code', $supervisor->supervisor_code)
                        ->where('status', 'active');
                })
                ->where('company_status', 'approved')
                ->where('supervisor_status', 'pending')
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_students' => $totalStudents,
                        'total_pending' => $totalPending,
                        'total_approved' => $totalApproved,
                        'total_rejected' => $totalRejected,
                    ],
                    'pending_students' => $pendingStudents->map(fn ($student) => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'is_in_jisr' => (bool) $student->is_in_jisr,
                        'company' => null,
                        'program' => null,
                        'status' => 'pending',
                        'initials' => collect(explode(' ', (string) $student->name))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                    ])->values(),
                    'pending_training_applications' => $pendingTrainingApplications->map(fn ($app) => [
                        'id' => $app->id,
                        'student_name' => $app->student?->name,
                        'student_email' => $app->student?->email,
                        'program_title' => $app->opportunity?->title,
                        'created_at' => optional($app->created_at)->toISOString(),
                    ])->values(),
                    'approved_students' => collect($approvedStudents)->map(function ($row) {
                        $student = $row['student'];
                        $application = $row['application'];
                        return [
                            'id' => $student?->id,
                            'application_id' => $application?->id,
                            'name' => $student?->name,
                            'email' => $student?->email,
                            'is_in_jisr' => (bool) $student?->is_in_jisr,
                            'company' => $row['opportunity']?->companyUser?->company_name ?? $row['opportunity']?->companyUser?->name,
                            'program' => $row['opportunity']?->title,
                            'status' => $row['status_label'] === 'At Risk' ? 'at-risk' : 'on-track',
                            'hoursCompleted' => $row['progress'],
                            'hoursTotal' => 100,
                            'tasksCompleted' => $application ? Task::where('application_id', $application->id)->where('status', 'done')->count() : 0,
                            'tasksTotal' => $application ? Task::where('application_id', $application->id)->count() : 0,
                            'initials' => collect(explode(' ', (string) $student?->name))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                            'board_url' => $application ? route('tasks.board', ['application' => $application->id]) : null,
                        ];
                    })->values(),
                    'rejected_students' => $rejectedStudents->map(fn ($student) => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'is_in_jisr' => (bool) $student->is_in_jisr,
                        'company' => null,
                        'program' => null,
                        'status' => 'rejected',
                        'hoursCompleted' => 0,
                        'hoursTotal' => 100,
                        'tasksCompleted' => 0,
                        'tasksTotal' => 0,
                        'initials' => collect(explode(' ', (string) $student->name))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                        'board_url' => null,
                    ])->values(),
                    'students' => User::where('role', 'student')
                        ->where('supervisor_code', $supervisor->supervisor_code)
                        ->select('id', 'name', 'email', 'university_id')
                        ->get(),
                ],
            ]);
        }

        return view('spa');
    }

    public function pendingStudentsPage()
    {
        $this->ensureRole();

        $supervisor = Auth::user();

        $pendingStudents = User::where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('spa');
    }

    public function weeklyTasksPage(Request $request)
    {
        $this->ensureRole();

        $supervisor = Auth::user();

        $approvedApplications = Application::with(['student', 'opportunity'])
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->whereHas('student', function ($query) use ($supervisor) {
                $query->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->latest()
            ->get();

        $applicationIds = $approvedApplications->pluck('id');

        $totalTasks = Task::whereIn('application_id', $applicationIds)->count();
        $todoCount = Task::whereIn('application_id', $applicationIds)->where('status', 'todo')->count();
        $progressCount = Task::whereIn('application_id', $applicationIds)->where('status', 'progress')->count();
        $doneCount = Task::whereIn('application_id', $applicationIds)->where('status', 'done')->count();

        if ($request->expectsJson() || $request->ajax()) {
            $tasks = Task::with(['application.student', 'application.opportunity'])
                ->whereIn('application_id', $applicationIds)
                ->latest()
                ->get()
                ->map(function ($task) {
                    $studentName = (string) optional($task->application?->student)->name;
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'studentName' => $studentName,
                        'initials' => collect(explode(' ', trim($studentName)))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                        'program' => optional($task->application?->opportunity)->title,
                        'status' => $task->status,
                        'dueDate' => $task->due_date,
                        'progress' => $task->status === 'done' ? 100 : ($task->status === 'progress' ? 50 : 10),
                        'hoursLogged' => $task->status === 'done' ? 15 : ($task->status === 'progress' ? 8 : 2),
                        'hoursTotal' => 15,
                        'board_url' => route('tasks.board', ['application' => $task->application_id]),
                    ];
                })->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_tasks' => $totalTasks,
                        'todo_tasks' => $todoCount,
                        'progress_tasks' => $progressCount,
                        'done_tasks' => $doneCount,
                    ],
                    'tasks' => $tasks,
                ],
            ]);
        }

        return view('spa');
    }

    public function jisrReviewsPage()
    {
        $this->ensureRole();

        $payload = $this->buildJisrReviewsPayload(Auth::user());

        return view('supervisor.jisr-reviews-fixed', [
            'stats' => $payload['stats'],
            'submissions' => $payload['submissions'],
        ]);
    }

    public function jisrReviewsData(Request $request): JsonResponse
    {
        $this->ensureRole();

        $payload = $this->buildJisrReviewsPayload(Auth::user());

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function showJisrAttachment(Request $request, int $submission, int $index)
    {
        return $this->serveJisrAttachment($request, $submission, $index, false);
    }

    public function downloadJisrAttachment(Request $request, int $submission, int $index)
    {
        return $this->serveJisrAttachment($request, $submission, $index, true);
    }

    private function serveJisrAttachment(Request $request, int $submissionId, int $index, bool $asDownload)
    {
        $this->ensureRole();

        $supervisor = $request->user();
        abort_unless($supervisor && $supervisor->role === 'supervisor', 403);
        abort_if($index < 0, 404);

        $submission = JisrSubmission::with('user')->findOrFail($submissionId);
        $student = $submission->user;
        abort_unless($student && $student->supervisor_code === $supervisor->supervisor_code, 403);

        $attachments = collect($submission->attachments ?? [])->values();
        $attachment = $attachments->get($index);
        abort_if(! is_array($attachment), 404);

        $path = (string) ($attachment['path'] ?? '');
        abort_if($path === '' || ! Storage::disk('public')->exists($path), 404);

        $fileName = (string) ($attachment['name'] ?? basename($path));
        $safeName = trim(basename($fileName)) !== '' ? basename($fileName) : basename($path);

        if ($asDownload) {
            return Storage::disk('public')->download($path, $safeName);
        }

        return Storage::disk('public')->response($path, $safeName);
    }

    public function reviewJisrSubmission(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole();

        $submission = JisrSubmission::with(['task', 'user'])->findOrFail($id);
        $student = $submission->user;
        $supervisor = $request->user();

        abort_unless(
            $student && $student->role === 'student' && $student->supervisor_code === $supervisor->supervisor_code,
            403
        );

        $maxScore = (int) ($submission->task?->max_score ?? 100);
        $validated = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'score' => ['nullable', 'integer', 'min:0', 'max:' . $maxScore],
            'feedback' => ['required', 'string', 'max:3000'],
        ]);

        $status = $validated['status'];
        $feedback = trim($validated['feedback']);
        $score = $status === 'accepted'
            ? (int) ($validated['score'] ?? $maxScore)
            : ($validated['score'] ?? null);

        if ($status === 'accepted' && ! array_key_exists('score', $validated)) {
            $score = $maxScore;
        }

        $submission->forceFill([
            'status' => $status,
            'score' => $score,
            'feedback' => $feedback,
        ])->save();

        $acceptedCount = JisrSubmission::query()
            ->where('user_id', $student->id)
            ->where('status', 'accepted')
            ->count();
        $totalTasks = JisrTask::query()->count();
        $programCompleted = $totalTasks > 0 && $acceptedCount >= $totalTasks;

        if ($programCompleted) {
            $student->forceFill([
                'is_in_jisr' => false,
                'skill_test_required' => true,
                'skill_test_passed' => false,
                'jisr_completed_at' => now(),
            ])->save();

            $this->notifications->notifyUser(
                userId: (int) $student->id,
                title: 'تم إكمال برنامج الجسر',
                description: 'تم تقييم جميع مهام برنامج الجسر بنجاح. يمكنك الآن التوجه إلى اختبار المهارات.',
                type: 'success',
                meta: ['category' => 'jisr']
            );
        } else {
            $this->notifications->notifyUser(
                userId: (int) $student->id,
                title: $status === 'accepted' ? 'تم تقييم مهمة من برنامج الجسر' : 'تم طلب تعديل مهمة من برنامج الجسر',
                description: $status === 'accepted'
                    ? 'تم قبول أحد حلولك في برنامج الجسر.'
                    : 'تم رفض أحد حلولك في برنامج الجسر ويحتاج إلى تعديل.',
                type: $status === 'accepted' ? 'success' : 'warning',
                meta: [
                    'category' => 'jisr',
                    'task_title' => $submission->task?->title,
                    'reason' => $feedback,
                ]
            );
        }

        $message = $status === 'accepted'
            ? 'تم اعتماد الحل بنجاح.'
            : 'تم حفظ التقييم وطلب إعادة المحاولة من الطالب.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'program_completed' => $programCompleted,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'score' => $submission->score,
                        'feedback' => $submission->feedback,
                    ],
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    public function studentDetails(Request $request, int $id)
    {
        $this->ensureRole();

        $supervisor = Auth::user();
        $student = User::where('id', $id)
            ->where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->firstOrFail();

        $application = Application::with(['opportunity.companyUser'])
            ->where('student_id', $student->id)
            ->where('final_status', 'approved')
            ->latest()
            ->first();

        $tasksQuery = $application
            ? Task::where('application_id', $application->id)
            : Task::query()->whereRaw('1=0');

        $tasksTotal = (clone $tasksQuery)->count();
        $tasksDone = (clone $tasksQuery)->where('status', 'done')->count();
        $progress = $application ? $this->calculateProgress($application) : 0;

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $student->id,
                    'application_id' => $application?->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'initials' => collect(explode(' ', (string) $student->name))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                    'phone' => $student->phone,
                    'location' => $student->city,
                    'company' => $application?->opportunity?->companyUser?->company_name ?: $application?->opportunity?->companyUser?->name,
                    'program' => $application?->opportunity?->title,
                    'status' => $progress >= 75 ? 'on-track' : 'at-risk',
                    'hoursCompleted' => $progress,
                    'hoursTotal' => 100,
                    'tasksCompleted' => $tasksDone,
                    'tasksTotal' => $tasksTotal,
                    'enrolledDate' => optional($application?->approved_at)->toDateString(),
                    'expectedEndDate' => $application && $application->approved_at && $application->opportunity?->duration
                        ? $application->approved_at->copy()->addMonths((int) $application->opportunity->duration)->toDateString()
                        : null,
                    'board_url' => $application ? route('tasks.board', ['application' => $application->id]) : null,
                ],
            ]);
        }

        return view('spa');
    }

    public function deleteStudent(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole();

        $student = User::where('id', $id)
            ->where('role', 'student')
            ->where('supervisor_code', $request->user()->supervisor_code)
            ->firstOrFail();

        // Logical delete to avoid FK conflicts in current DB structure.
        $student->status = 'deleted';
        $student->save();

        $this->notifications->notifyUser(
            userId: $student->id,
            title: 'Student Account Deleted',
            description: 'Your student account has been deleted. Please contact your supervisor for more information.',
            type: 'error',
            meta: ['category' => 'auth']
        );

         return $this->actionResponse($request, 'student deleted successfully.');
     }

    private function calculateProgress($application)
    {
        if (!$application->approved_at || !$application->opportunity || !$application->opportunity->duration) {
            return 0;
        }

        if ($application->training_completed_at) {
            return 100;
        }

        $start = Carbon::parse($application->approved_at);
        $end = Carbon::parse($application->approved_at)->addMonths((int)$application->opportunity->duration);
        $now = now();

        if ($now->lessThanOrEqualTo($start)) {
            return 0;
        }

        if ($now->greaterThanOrEqualTo($end)) {
            return 100;
        }

        $totalSeconds = $start->diffInSeconds($end);
        $elapsedSeconds = $start->diffInSeconds($now);

        if ($totalSeconds <= 0) {
            return 0;
        }

        return min(100, max(0, round(($elapsedSeconds / $totalSeconds) * 100)));
    }
}
