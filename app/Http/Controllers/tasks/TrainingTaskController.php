<?php

namespace App\Http\Controllers\tasks;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipOpportunity;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TrelloIntegration;
use App\Models\TrelloInternshipLink;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TrainingEvaluationNotifier;
use App\Services\TrelloService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingTaskController extends Controller
{
    public function __construct(
        private readonly TrelloService $trello,
        private readonly NotificationService $notifications,
        private readonly TrainingEvaluationNotifier $trainingEvaluationNotifier
    ) {
    }

    public function workspace(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->workspaceData($request);
        }

        return view('spa');

        if (! $application) {
            return back()->with('error', 'لا يوجد تدريب مكتمل الموافقات بعد لعرض لوحة المهام.');
        }

        $this->trainingEvaluationNotifier->notifyIfTrainingEnded($application);

        return redirect()->route('tasks.board', $application->id);
    }

    public function storeWorkspaceTask(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['company', 'supervisor'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'training_id' => ['required', 'integer', 'exists:internship_opportunities,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $training = InternshipOpportunity::query()->findOrFail((int) $validated['training_id']);
        if ($user->role === 'company') {
            abort_unless((int) $training->company_user_id === (int) $user->id, 403);
        }

        $studentIds = collect($validated['student_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($user->role === 'supervisor') {
            $allowedCount = User::query()
                ->whereIn('id', $studentIds)
                ->where('role', 'student')
                ->where('supervisor_code', $user->supervisor_code)
                ->count();
            abort_unless($allowedCount === $studentIds->count(), 403);
        }

        $applications = Application::query()
            ->with('student')
            ->where('opportunity_id', $training->id)
            ->whereIn('student_id', $studentIds)
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get()
            ->keyBy('student_id');

        abort_unless($applications->isNotEmpty(), 422, 'No active approved students in selected training.');

        $integration = TrelloIntegration::query()
            ->where('company_user_id', (int) $training->company_user_id)
            ->where('is_active', true)
            ->first();

        $link = $integration
            ? TrelloInternshipLink::query()
                ->where('trello_integration_id', $integration->id)
                ->where('opportunity_id', $training->id)
                ->first()
            : null;

        $createdTasks = collect();

        foreach ($studentIds as $studentId) {
            $application = $applications->get($studentId);
            if (! $application) {
                continue;
            }

            $task = Task::query()->create([
                'application_id' => $application->id,
                'training_id' => $training->id,
                'company_user_id' => (int) $training->company_user_id,
                'created_by' => (int) $user->id,
                'title' => trim((string) $validated['title']),
                'description' => $validated['description'] ?? null,
                'details' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'label' => $validated['label'] ?? null,
                'status' => 'todo',
                'source' => 'manual',
                'assigned_user' => $application->student?->name,
                'order' => ((int) Task::query()
                    ->where('application_id', $application->id)
                    ->where('status', 'todo')
                    ->max('order')) + 1,
            ]);

            $task->assignedStudents()->syncWithoutDetaching([$studentId]);
            TaskUser::query()
                ->where('task_id', $task->id)
                ->where('student_id', $studentId)
                ->update(['status' => 'pending']);

            $createdTasks->push($task);
        }

        $trelloCardId = null;
        if ($integration && $link) {
            $card = $this->trello->createCard(
                listId: (string) $link->trello_list_id,
                name: trim((string) $validated['title']),
                desc: (string) ($validated['description'] ?? ''),
                integration: $integration
            );

            if (! empty($card['id'])) {
                $trelloCardId = (string) $card['id'];
                $labelId = $integration->trello_board_id
                    ? $this->trello->findOrCreateBoardLabel((string) $integration->trello_board_id, (string) $training->title, $integration)
                    : null;

                if ($labelId) {
                    $this->trello->addLabelToCard($trelloCardId, $labelId, $integration);
                }

                Task::query()
                    ->whereIn('id', $createdTasks->pluck('id')->all())
                    ->update([
                        'trello_card_id' => $trelloCardId,
                        'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                        'trello_integration_id' => $integration->id,
                        'source' => 'trello',
                        'trello_last_synced_at' => now(),
                    ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully.',
            'data' => [
                'task_ids' => $createdTasks->pluck('id')->values(),
                'trello_card_id' => $trelloCardId,
            ],
        ]);
    }
    public function submitWorkspaceTask(Request $request, Task $task): JsonResponse
    {
        $task->load(['application.student', 'attachments']);
        $application = $task->application;

        abort_if(! $application, 404);
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless($request->user()->role === 'student', 403);
        abort_unless($task->assignedStudents()->where('users.id', $request->user()->id)->exists(), 403);

        $validated = $request->validate([
            'student_solution' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $task->update([
            'student_solution' => trim($validated['student_solution']),
            'status' => 'done',
        ]);

        TaskUser::query()
            ->where('task_id', $task->id)
            ->where('student_id', (int) $request->user()->id)
            ->update(['status' => 'submitted']);

        foreach ((array) $request->file('attachments', []) as $file) {
            $path = $file->store('attachments', 'public');
            $task->attachments()->create([
                'user_id' => $request->user()->id,
                'filename' => $file->getClientOriginalName(),
                'filepath' => $path,
            ]);
        }

        $this->syncStudentSubmissionToTrello($task->fresh(['attachments']), $application);
        $integration = $this->resolveCompanyIntegration($application);
        if ($integration && $task->trello_card_id) {
            $this->trello->addCardComment(
                (string) $task->trello_card_id,
                'Student ' . $request->user()->name . ' has submitted this task ' . "\u{2705}",
                $integration
            );
        }
        $this->notifyTaskSubmissionReviewers($application);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسليم المهمة بنجاح.',
            'task' => $this->formatWorkspaceTask($task->fresh(['application.student', 'application.opportunity.companyUser', 'assignedStudents', 'attachments.user'])),
        ]);
    }

    public function gradeWorkspaceTask(Request $request, Task $task): JsonResponse
    {
        $task->load(['application.student', 'application.opportunity']);
        $application = $task->application;

        abort_if(! $application, 404);
        $this->authorizeApplication($request, $application);
        abort_unless(in_array($request->user()->role, ['company', 'supervisor', 'admin'], true), 403);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        if ($request->user()->role === 'company') {
            $task->company_score = $validated['score'];
        } elseif ($request->user()->role === 'supervisor') {
            $task->supervisor_score = $validated['score'];
        } else {
            $task->company_score = $validated['score'];
            $task->supervisor_score = $validated['score'];
        }

        $task->save();

        if ($application->student_id) {
            $this->notifications->notifyUser(
                userId: (int) $application->student_id,
                title: 'Training Task Evaluated',
                description: 'تم تقييم إحدى مهام التدريب الخاصة بك.',
                type: 'success',
                meta: ['category' => 'evaluation']
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ التقييم.',
            'task' => $this->formatWorkspaceTask($task->fresh(['application.student', 'application.opportunity.companyUser', 'assignedStudents', 'attachments.user'])),
        ]);
    }

    public function adminWorkspace(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $approvedApplications = Application::with(['student:id,name', 'opportunity:id,title'])
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->latest()
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'approved_applications' => $approvedApplications,
            ]);
        }

        return view('spa');
    }

    public function adminBroadcastTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $applications = Application::with('student')
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get();

        foreach ($applications as $application) {
            $application->loadMissing('opportunity');
            if ($this->isTrainingEnded($application)) {
                continue;
            }

            $task = Task::create([
                'application_id' => $application->id,
                'training_id' => (int) $application->opportunity_id,
                'company_user_id' => (int) optional($application->opportunity)->company_user_id,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'description' => $validated['details'] ?: null,
                'details' => $validated['details'] ?: null,
                'due_date' => $validated['due_date'] ?: null,
                'label' => $validated['label'] ?: null,
                'assigned_user' => $application->student?->name,
                'status' => 'todo',
                'source' => 'manual',
                'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
            ]);

            $this->attachTaskToStudent($task, $application->student_id ? (int) $application->student_id : null);

            $integration = $this->resolveCompanyIntegration($application);
            $link = $this->resolveInternshipLink($application, $integration);
            if ($integration && $link) {
                $card = $this->trello->createCard(
                    listId: (string) $link->trello_list_id,
                    name: '[' . ($application->student?->name ?? 'Student') . '] ' . $task->title,
                    desc: (string) ($task->details ?? ''),
                    integration: $integration
                );

                if (! empty($card['id'])) {
                    $task->update([
                        'trello_card_id' => $card['id'],
                        'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                        'trello_integration_id' => $integration->id,
                        'source' => 'trello',
                        'trello_last_synced_at' => now(),
                    ]);
                }
            }

            if ($application->student_id) {
                $this->notifications->notifyUser(
                    userId: (int) $application->student_id,
                    title: 'New General Task',
                    description: 'تمت إضافة مهمة عامة جديدة من الإدارة.',
                    type: 'info',
                    meta: ['category' => 'task']
                );
            }
        }

        return back()->with('success', 'تم نشر المهمة العامة بنجاح.');
    }

    public function supervisorBroadcastTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'supervisor', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $applications = Application::with('student')
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->whereHas('student', function (Builder $query) use ($request) {
                $query->where('supervisor_code', $request->user()->supervisor_code);
            })
            ->get();

        if ($applications->isEmpty()) {
            return back()->with('error', 'لا يوجد طلاب مقبولون نهائيًا ضمن إشرافك.');
        }

        foreach ($applications as $application) {
            $application->loadMissing('opportunity');
            if ($this->isTrainingEnded($application)) {
                continue;
            }

            $task = Task::create([
                'application_id' => $application->id,
                'training_id' => (int) $application->opportunity_id,
                'company_user_id' => (int) optional($application->opportunity)->company_user_id,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'description' => $validated['details'] ?: null,
                'details' => $validated['details'] ?: null,
                'due_date' => $validated['due_date'] ?: null,
                'label' => $validated['label'] ?: null,
                'assigned_user' => $application->student?->name,
                'status' => 'todo',
                'source' => 'manual',
                'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
            ]);

            $this->attachTaskToStudent($task, $application->student_id ? (int) $application->student_id : null);

            $integration = $this->resolveCompanyIntegration($application);
            $link = $this->resolveInternshipLink($application, $integration);
            if ($integration && $link) {
                $card = $this->trello->createCard(
                    listId: (string) $link->trello_list_id,
                    name: '[' . ($application->student?->name ?? 'Student') . '] ' . $task->title,
                    desc: (string) ($task->details ?? ''),
                    integration: $integration
                );

                if (! empty($card['id'])) {
                    $task->update([
                        'trello_card_id' => $card['id'],
                        'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                        'trello_integration_id' => $integration->id,
                        'source' => 'trello',
                        'trello_last_synced_at' => now(),
                    ]);
                }
            }

            if ($application->student_id) {
                $this->notifications->notifyUser(
                    userId: (int) $application->student_id,
                    title: 'New General Task',
                    description: 'تمت إضافة مهمة عامة جديدة من المشرف.',
                    type: 'info',
                    meta: ['category' => 'task']
                );
            }
        }

        return back()->with('success', 'تم نشر المهمة العامة بنجاح.');
    }

    private function authorizeApplication(Request $request, Application $application): void
    {
        $user = $request->user();

        $allowed = (int) $application->student_id === (int) $user->id
            || ($user->role === 'company' && (int) optional($application->opportunity)->company_user_id === (int) $user->id)
            || ($user->role === 'supervisor' && optional($application->student)->supervisor_code === $user->supervisor_code)
            || ($user->role === 'admin');

        abort_unless($allowed, 403);

        abort_unless(
            $application->company_status === 'approved'
                && $application->supervisor_status === 'approved'
                && $application->final_status === 'approved',
            403,
            'يمكن إدارة المهام فقط بعد القبول النهائي من الشركة والمشرف.'
        );
    }

    private function statusToTrelloListId(string $status): string
    {
        return match ($status) {
            'todo' => (string) config('services.trello.todo_list_id', ''),
            'progress' => (string) config('services.trello.progress_list_id', ''),
            'done' => (string) config('services.trello.done_list_id', ''),
            default => '',
        };
    }

    private function resolveCompanyIntegration(Application $application): ?TrelloIntegration
    {
        $companyId = (int) optional($application->opportunity)->company_user_id;
        if ($companyId <= 0) {
            return null;
        }

        return TrelloIntegration::query()
            ->where('company_user_id', $companyId)
            ->where('is_active', true)
            ->first();
    }

    private function resolveInternshipLink(Application $application, ?TrelloIntegration $integration): ?TrelloInternshipLink
    {
        if (! $integration) {
            return null;
        }

        return TrelloInternshipLink::query()
            ->where('trello_integration_id', $integration->id)
            ->where('opportunity_id', (int) $application->opportunity_id)
            ->first();
    }

    private function attachTaskToStudent(Task $task, ?int $studentId): void
    {
        if (! $studentId) {
            return;
        }

        $task->assignedStudents()->syncWithoutDetaching([$studentId]);
    }

    private function workspaceData(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['company', 'student', 'supervisor', 'admin'], true), 403);

        if (in_array($user->role, ['company', 'supervisor', 'student'], true)) {
            $this->syncTasksFromTrelloForUser($user);
        }

        $tasksQuery = Task::with([
            'application.student:id,name,email,supervisor_code',
            'application.opportunity.companyUser:id,name,company_name,email',
            'creator:id,name,role',
            'assignedStudents:id,name,email',
            'attachments.user:id,name,role',
        ]);

        $approvedApplicationsQuery = Application::with([
            'student:id,name,email,supervisor_code',
            'opportunity:id,title,company_user_id,duration',
        ])
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved');

        if ($user->role === 'company') {
            $tasksQuery->where(function (Builder $query) use ($user) {
                $query->where('company_user_id', $user->id)
                    ->orWhereHas('application.opportunity', fn (Builder $q) => $q->where('company_user_id', $user->id));
            });

            $approvedApplicationsQuery->whereHas('opportunity', fn (Builder $q) => $q->where('company_user_id', $user->id));
        } elseif ($user->role === 'student') {
            $trainingIds = Application::query()
                ->where('student_id', $user->id)
                ->where('company_status', 'approved')
                ->where('supervisor_status', 'approved')
                ->where('final_status', 'approved')
                ->whereNull('training_completed_at')
                ->pluck('opportunity_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $tasksQuery
                ->whereIn('training_id', $trainingIds)
                ->whereHas('assignedStudents', fn (Builder $q) => $q->where('users.id', $user->id));

            $approvedApplicationsQuery->where('student_id', $user->id);
        } elseif ($user->role === 'supervisor') {
            $tasksQuery->whereHas('application.student', function (Builder $query) use ($user) {
                $query->where('supervisor_code', $user->supervisor_code);
            });

            $approvedApplicationsQuery->whereHas('student', function (Builder $query) use ($user) {
                $query->where('supervisor_code', $user->supervisor_code);
            });
        }

        $tasks = $tasksQuery
            ->latest()
            ->get()
            ->map(fn (Task $task) => $this->formatWorkspaceTask($task))
            ->values();

        $approvedApplications = $approvedApplicationsQuery
            ->whereNull('training_completed_at')
            ->latest()
            ->get()
            ->map(fn (Application $application) => $this->formatWorkspaceApplication($application))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'role' => $user->role,
                'applications' => $approvedApplications,
                'tasks' => $tasks,
                'stats' => [
                    'total' => $tasks->count(),
                    'todo' => $tasks->where('status', 'todo')->count(),
                    'progress' => $tasks->where('status', 'progress')->count(),
                    'done' => $tasks->where('status', 'done')->count(),
                    'submitted' => $tasks->where('submitted', true)->count(),
                    'graded' => $tasks->filter(fn ($task) => $task['company_score'] !== null || $task['supervisor_score'] !== null)->count(),
                ],
            ],
        ]);
    }

    private function formatWorkspaceApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'training_id' => $application->opportunity_id,
            'opportunity_id' => $application->opportunity_id,
            'student_id' => $application->student_id,
            'student_name' => $application->student?->name,
            'student_email' => $application->student?->email,
            'program_title' => $application->opportunity?->title,
            'training_end_date' => optional($this->getTrainingEndDate($application))->toDateString(),
            'board_url' => route('tasks.board', ['application' => $application->id]),
        ];
    }

    private function formatWorkspaceTask(Task $task): array
    {
        $application = $task->application;
        $student = $application?->student;
        $company = $application?->opportunity?->companyUser;
        $attachments = $task->attachments->map(fn ($attachment) => [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'url' => asset('storage/' . ltrim((string) $attachment->filepath, '/')),
            'uploaded_by' => $attachment->user?->name,
        ])->values();

        return [
            'id' => $task->id,
            'application_id' => $task->application_id,
            'title' => $task->title,
            'details' => $task->details,
            'status' => $task->status,
            'label' => $task->label,
            'due_date' => optional($task->due_date)->toDateString(),
            'student_solution' => $task->student_solution,
            'submitted' => filled($task->student_solution) || $attachments->isNotEmpty(),
            'company_score' => $task->company_score,
            'supervisor_score' => $task->supervisor_score,
            'source' => $task->source ?? 'manual',
            'created_at' => optional($task->created_at)->toDateTimeString(),
            'updated_at' => optional($task->updated_at)->toDateTimeString(),
            'board_url' => $application ? route('tasks.board', ['application' => $application->id]) : null,
            'student' => $student ? [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ] : null,
            'program' => $application?->opportunity?->title,
            'company' => $company ? ($company->company_name ?: $company->name) : null,
            'creator' => $task->creator ? [
                'id' => $task->creator->id,
                'name' => $task->creator->name,
                'role' => $task->creator->role,
            ] : null,
            'assigned_students' => $task->assignedStudents->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ])->values(),
            'attachments' => $attachments,
        ];
    }

    private function notifyTaskSubmissionReviewers(Application $application): void
    {
        $recipients = [];
        $companyId = (int) optional($application->opportunity)->company_user_id;

        if ($companyId > 0) {
            $recipients[] = $companyId;
        }

        $supervisor = $application->student?->supervisor_code
            ? User::where('role', 'supervisor')->where('supervisor_code', $application->student->supervisor_code)->first()
            : null;

        if ($supervisor) {
            $recipients[] = (int) $supervisor->id;
        }

        $this->notifications->notifyMany(
            userIds: array_values(array_unique($recipients)),
            title: 'Training Task Submitted',
            description: 'قام الطالب بتسليم مهمة تدريبية جديدة.',
            type: 'success',
            meta: ['category' => 'task']
        );
    }

    private function syncStudentSubmissionToTrello(Task $task, Application $application): void
    {
        $integration = $this->resolveCompanyIntegration($application);
        if (! $integration || ! $task->trello_card_id) {
            return;
        }

        $task->loadMissing(['attachments']);
        $student = $application->student;
        $attachmentLines = $task->attachments
            ->map(fn ($attachment) => '- ' . $attachment->filename . ': ' . asset('storage/' . ltrim((string) $attachment->filepath, '/')))
            ->implode("\n");

        $comment = trim(
            "تم تسليم المهمة من الطالب داخل نظام SIP.\n"
            . 'الطالب: ' . ($student?->name ?? '-') . "\n"
            . 'البريد: ' . ($student?->email ?? '-') . "\n"
            . 'الحل: ' . ($task->student_solution ?: '-') . "\n"
            . ($attachmentLines !== '' ? "الملفات:\n{$attachmentLines}\n" : '')
            . 'رابط المتابعة داخل النظام: ' . route('tasks.board', ['application' => $application->id])
        );

        try {
            $this->trello->addCardComment((string) $task->trello_card_id, $comment, $integration);
            $relatedTasks = Task::query()
                ->with('application.student:id,name,email')
                ->where('trello_integration_id', $integration->id)
                ->where('trello_card_id', (string) $task->trello_card_id)
                ->get();

            $submittedCount = $relatedTasks
                ->filter(fn (Task $relatedTask) => filled($relatedTask->student_solution))
                ->count();

            $totalCount = $relatedTasks->count();
            $allSubmitted = $totalCount > 0 && $submittedCount >= $totalCount;

            $statusLines = $relatedTasks->map(function (Task $relatedTask) {
                $studentName = $relatedTask->application?->student?->name ?: 'Student';
                $isSubmitted = filled($relatedTask->student_solution);
                $mark = $isSubmitted ? '?' : '⬜';

                return $mark . ' ' . $studentName . ' (ID: ' . (int) $relatedTask->application?->student_id . ')';
            })->implode("\n");

            $completedDueDate = $task->due_date
                ? $task->due_date->copy()->endOfDay()->toIso8601String()
                : now()->toIso8601String();

            $baseTitle = preg_replace('/^\[\d+\s*\/\s*\d+\]\s*/', '', (string) $task->title) ?: (string) $task->title;

            $this->trello->updateCard((string) $task->trello_card_id, [
                'due' => $completedDueDate,
                'dueComplete' => $allSubmitted,
                'name' => '[' . $submittedCount . '/' . max($totalCount, 1) . '] ' . $baseTitle,
                'desc' => trim((string) ($task->details ?? '') . "\n\nSIPS Submission Progress:\n" . $statusLines),
            ], $integration);
        } catch (\Throwable) {
            // Keep the local submission saved even if Trello is temporarily unavailable.
        }
    }

    public function syncWorkspaceTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['company', 'supervisor'], true), 403);

        $result = $this->syncTasksFromTrelloForUser($user, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Trello sync completed.',
            'data' => $result,
        ]);
    }

    private function syncTasksFromTrelloForUser(User $user, bool $force = false): array
    {
        $linksQuery = TrelloInternshipLink::query()->with(['integration']);

        if ($user->role === 'company') {
            $linksQuery->whereHas('integration', function (Builder $query) use ($user) {
                $query->where('company_user_id', $user->id)->where('is_active', true);
            });
        } elseif ($user->role === 'supervisor') {
            $companyIds = Application::query()
                ->whereHas('student', fn (Builder $q) => $q->where('supervisor_code', $user->supervisor_code))
                ->join('internship_opportunities', 'internship_opportunities.id', '=', 'applications.opportunity_id')
                ->distinct()
                ->pluck('internship_opportunities.company_user_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $linksQuery->whereHas('integration', function (Builder $query) use ($companyIds) {
                $query->whereIn('company_user_id', $companyIds)->where('is_active', true);
            });
        } elseif ($user->role === 'student') {
            $trainingIds = Application::query()
                ->where('student_id', $user->id)
                ->where('company_status', 'approved')
                ->where('supervisor_status', 'approved')
                ->where('final_status', 'approved')
                ->whereNull('training_completed_at')
                ->pluck('opportunity_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($trainingIds->isEmpty()) {
                return ['created' => 0, 'updated' => 0, 'skipped' => 0];
            }

            $linksQuery->whereIn('opportunity_id', $trainingIds)
                ->whereHas('integration', fn (Builder $query) => $query->where('is_active', true));
        } else {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $links = $linksQuery->get();

        foreach ($links as $link) {
            $integration = $link->integration;
            if (! $integration || ! $integration->is_active || ! $integration->trello_board_id) {
                continue;
            }

            if (! $force && $link->last_synced_at && now()->diffInSeconds($link->last_synced_at) < 45) {
                continue;
            }

            $applications = Application::query()
                ->with('student')
                ->where('opportunity_id', $link->opportunity_id)
                ->where('company_status', 'approved')
                ->where('supervisor_status', 'approved')
                ->where('final_status', 'approved')
                ->whereNull('training_completed_at')
                ->get();

            if ($applications->isEmpty()) {
                continue;
            }

            $cards = collect($this->trello->getBoardCards((string) $integration->trello_board_id, $integration))
                ->filter(fn (array $card) => (string) ($card['idList'] ?? '') === (string) $link->trello_list_id)
                ->values();

            foreach ($cards as $card) {
                $cardId = (string) ($card['id'] ?? '');
                if ($cardId === '') {
                    $totals['skipped']++;
                    continue;
                }

                $existingTasks = Task::query()
                    ->where('training_id', (int) $link->opportunity_id)
                    ->where('trello_card_id', $cardId)
                    ->get();

                if ($existingTasks->isEmpty()) {
                    foreach ($applications as $application) {
                        $task = Task::query()->create([
                            'application_id' => $application->id,
                            'training_id' => (int) $link->opportunity_id,
                            'company_user_id' => (int) $integration->company_user_id,
                            'trello_integration_id' => (int) $integration->id,
                            'created_by' => (int) $integration->company_user_id,
                            'trello_card_id' => $cardId,
                            'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                            'source' => 'trello',
                            'trello_last_synced_at' => now(),
                            'title' => trim((string) ($card['name'] ?? 'Task')),
                            'description' => (string) ($card['desc'] ?? ''),
                            'details' => (string) ($card['desc'] ?? ''),
                            'status' => ((bool) ($card['dueComplete'] ?? false) || (bool) ($card['closed'] ?? false)) ? 'done' : 'todo',
                            'assigned_user' => $application->student?->name,
                            'order' => ((int) Task::query()
                                ->where('application_id', $application->id)
                                ->where('status', 'todo')
                                ->max('order')) + 1,
                        ]);

                        if ($application->student_id) {
                            $task->assignedStudents()->syncWithoutDetaching([(int) $application->student_id]);
                            TaskUser::query()
                                ->where('task_id', $task->id)
                                ->where('student_id', (int) $application->student_id)
                                ->update(['status' => 'pending']);
                        }
                    }

                    $totals['created']++;
                } else {
                    foreach ($existingTasks as $task) {
                        $task->update([
                            'title' => trim((string) ($card['name'] ?? $task->title)),
                            'description' => (string) ($card['desc'] ?? ''),
                            'details' => (string) ($card['desc'] ?? ''),
                            'trello_list_id' => (string) ($card['idList'] ?? $task->trello_list_id),
                            'status' => ((bool) ($card['dueComplete'] ?? false) || (bool) ($card['closed'] ?? false)) ? 'done' : 'todo',
                            'trello_last_synced_at' => now(),
                        ]);
                    }

                    $totals['updated']++;
                }
            }

            $link->update(['last_synced_at' => now()]);
        }

        return $totals;
    }

    private function ensureTrainingOpen(Application $application): void
    {
        abort_if($this->isTrainingEnded($application), 422, 'Training period ended. Tasks are read-only now.');
    }

    private function getTrainingEndDate(Application $application): ?\Illuminate\Support\Carbon
    {
        if (! $application->approved_at || ! $application->opportunity || (int) $application->opportunity->duration <= 0) {
            return null;
        }

        return $application->approved_at->copy()->addMonths((int) $application->opportunity->duration)->startOfDay();
    }

    private function isTrainingEnded(Application $application): bool
    {
        if ($application->training_completed_at) {
            return true;
        }

        $endDate = $this->getTrainingEndDate($application);
        if (! $endDate) {
            return false;
        }

        return now()->startOfDay()->greaterThanOrEqualTo($endDate);
    }

    public function board(Request $request, Application $application)
    {
        $application->load(['student', 'opportunity.companyUser']);
        $this->authorizeApplication($request, $application);
        $this->trainingEvaluationNotifier->notifyIfTrainingEnded($application);

        $trainingEnded = $this->isTrainingEnded($application);
        $trainingEndDate = $this->getTrainingEndDate($application);

        if ($application->training_completed_at && $request->user()->role === 'student') {
            return redirect()->route('training.complete', $application->id);
        }

        $tasks = Task::with([
            'creator:id,name,role',
            'comments.user:id,name,role',
            'attachments.user:id,name,role',
            'assignedStudents:id,name',
        ])->where('application_id', $application->id);

        if ($request->user()->role === 'student') {
            $tasks->whereHas('assignedStudents', function (Builder $query) use ($request) {
                $query->where('users.id', $request->user()->id);
            });
        }

        $tasks = $tasks->orderBy('order')
            ->orderBy('id')
            ->get();

        return view('tasks.board', [
            'application' => $application,
            'role' => $request->user()->role,
            'trainingEnded' => $trainingEnded,
            'trainingEndDate' => $trainingEndDate,
            'todoTasks' => $tasks->where('status', 'todo')->values(),
            'progressTasks' => $tasks->where('status', 'progress')->values(),
            'doneTasks' => $tasks->where('status', 'done')->values(),
        ]);
    }

    public function createTask(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless(in_array($request->user()->role, ['supervisor', 'admin'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $task = Task::create([
            'application_id' => $application->id,
            'training_id' => (int) $application->opportunity_id,
            'company_user_id' => (int) optional($application->opportunity)->company_user_id,
            'created_by' => $request->user()->id,
            'title' => trim($validated['title']),
            'description' => $validated['details'] ?: null,
            'details' => $validated['details'] ?: null,
            'due_date' => $validated['due_date'] ?: null,
            'label' => $validated['label'] ?: null,
            'assigned_user' => $application->student?->name,
            'status' => 'todo',
            'source' => 'manual',
            'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
        ]);

        $this->attachTaskToStudent($task, $application->student_id ? (int) $application->student_id : null);

        $integration = $this->resolveCompanyIntegration($application);
        $link = $this->resolveInternshipLink($application, $integration);
        if ($integration && $link) {
            $card = $this->trello->createCard(
                listId: (string) $link->trello_list_id,
                name: $task->title,
                desc: (string) ($task->details ?? ''),
                integration: $integration
            );

            if (! empty($card['id'])) {
                $task->update([
                    'trello_card_id' => $card['id'],
                    'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                    'trello_integration_id' => $integration->id,
                    'source' => 'trello',
                    'trello_last_synced_at' => now(),
                ]);
            }
        }

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'New Task Added',
            description: 'تمت إضافة مهمة جديدة على لوحة التدريب.',
            type: 'info',
            meta: ['category' => 'task']
        );

        return back()->with('success', 'تم إنشاء المهمة بنجاح.');
    }

    public function submitSolution(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless($request->user()->role === 'student', 403);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'student_solution' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'in:todo,progress,done'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $task->student_solution = trim($validated['student_solution']);

        if (! empty($validated['status'])) {
            $task->status = $validated['status'];
        }

        $task->save();

        foreach ((array) $request->file('attachments', []) as $file) {
            $path = $file->store('attachments', 'public');
            $task->attachments()->create([
                'user_id' => $request->user()->id,
                'filename' => $file->getClientOriginalName(),
                'filepath' => $path,
            ]);
        }

        $this->syncStudentSubmissionToTrello($task->fresh(['attachments']), $application);

        $recipients = [];
        $companyId = (int) optional($application->opportunity)->company_user_id;
        if ($companyId > 0) {
            $recipients[] = $companyId;
        }

        $supervisor = $application->student?->supervisor_code
            ? User::where('role', 'supervisor')->where('supervisor_code', $application->student->supervisor_code)->first()
            : null;

        if ($supervisor) {
            $recipients[] = (int) $supervisor->id;
        }

        $this->notifications->notifyMany(
            userIds: $recipients,
            title: 'Task Solution Submitted',
            description: 'قام الطالب بتسليم حل مهمة.',
            type: 'success',
            meta: ['category' => 'task']
        );

        return back()->with('success', 'تم تسليم الحل بنجاح.');
    }

    public function addComment(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => trim($validated['content']),
        ]);

        if ((string) $request->user()->role !== 'student' && $application->student_id) {
            $this->notifications->notifyUser(
                userId: (int) $application->student_id,
                title: 'New Comment on Task',
                description: 'تمت إضافة تعليق جديد على إحدى مهامك.',
                type: 'info',
                meta: ['category' => 'task']
            );
        }

        return back()->with('success', 'تمت إضافة التعليق.');
    }

    public function gradeTask(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless(in_array($request->user()->role, ['company', 'supervisor', 'admin'], true), 403);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        if ($request->user()->role === 'company') {
            $task->company_score = $validated['score'];
        } elseif ($request->user()->role === 'supervisor') {
            $task->supervisor_score = $validated['score'];
        } else {
            $task->company_score = $validated['score'];
            $task->supervisor_score = $validated['score'];
        }

        $task->save();

        if ($application->student_id) {
            $this->notifications->notifyUser(
                userId: (int) $application->student_id,
                title: 'Task Evaluated',
                description: 'تم تقييم مهمتك من قبل الجهة المسؤولة.',
                type: 'success',
                meta: ['category' => 'evaluation']
            );
        }

        return back()->with('success', 'تم حفظ التقييم بنجاح.');
    }
}



