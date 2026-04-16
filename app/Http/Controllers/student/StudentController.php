<?php

namespace App\Http\Controllers\student;

use App\Models\Application;
use App\Models\InternshipOpportunity;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    private function ensureRole(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'student', 403);
    }

    private function calculateProgress(Application $application): int
    {
        if (! $application->approved_at || ! $application->opportunity || ! $application->opportunity->duration) {
            return 0;
        }

        if ($application->training_completed_at) {
            return 100;
        }

        $start = Carbon::parse($application->approved_at);
        $end = Carbon::parse($application->approved_at)->addMonths((int) $application->opportunity->duration);
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

        return (int) min(100, max(0, round(($elapsedSeconds / $totalSeconds) * 100)));
    }

    public function dashboard(Request $request)
    {
        $this->ensureRole();

        $student = Auth::user();

        $applications = Application::with(['opportunity.companyUser'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        $totalApplications = $applications->count();
        $pendingApplications = $applications->where('final_status', 'pending')->count();
        $approvedApplications = $applications->where('final_status', 'approved')->count();
        $rejectedApplications = $applications->where('final_status', 'rejected')->count();

        $activeTraining = $applications->first(fn ($application) => $application->final_status === 'approved');

        if ($request->expectsJson() || $request->ajax()) {
            $applicationIds = $applications->pluck('id');
            $totalTasks = Task::whereIn('application_id', $applicationIds)->count();
            $completedTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'done')->count();
            $pendingTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'todo')->count();
            $inProgressTasks = Task::whereIn('application_id', $applicationIds)->where('status', 'progress')->count();

            $weeklyTasks = collect();
            if ($activeTraining) {
                $weeklyTasks = Task::where('application_id', $activeTraining->id)
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->take(6)
                    ->get()
                    ->map(function (Task $task) use ($activeTraining) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->details ?: '',
                            'type' => $task->label ?: 'default',
                            'status' => $task->status,
                            'due_date' => $task->due_date,
                            'points' => (int) (($task->company_score ?? 0) + ($task->supervisor_score ?? 0)),
                            'board_url' => route('tasks.board', ['application' => $activeTraining->id]),
                        ];
                    })
                    ->values();
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'student_id' => $student->student_id,
                    ],
                    'stats' => [
                        'total_applications' => $totalApplications,
                        'pending_applications' => $pendingApplications,
                        'approved_applications' => $approvedApplications,
                        'rejected_applications' => $rejectedApplications,
                        'total_tasks' => $totalTasks,
                        'completed_tasks' => $completedTasks,
                        'pending_tasks' => $pendingTasks,
                        'in_progress_tasks' => $inProgressTasks,
                    ],
                    'applications' => $applications->map(function (Application $application) {
                        return [
                            'id' => $application->id,
                            'program_id' => $application->opportunity_id,
                            'program_title' => $application->opportunity?->title,
                            'company_name' => $application->opportunity?->companyUser?->company_name
                                ?: $application->opportunity?->companyUser?->name,
                            'company_status' => $application->company_status,
                            'supervisor_status' => $application->supervisor_status,
                            'final_status' => $application->final_status,
                            'created_at' => optional($application->created_at)->toISOString(),
                        ];
                    })->values(),
                    'active_training' => $activeTraining ? [
                        'application_id' => $activeTraining->id,
                        'program_title' => $activeTraining->opportunity?->title,
                        'company_name' => $activeTraining->opportunity?->companyUser?->company_name
                            ?: $activeTraining->opportunity?->companyUser?->name,
                        'progress' => $this->calculateProgress($activeTraining),
                        'board_url' => route('tasks.board', ['application' => $activeTraining->id]),
                        'workspace_url' => route('student.workspace.index'),
                    ] : null,
                    'weekly_tasks' => $weeklyTasks,
                ],
            ]);
        }

        return view('spa');
    }

    public function programs(Request $request)
    {
        $this->ensureRole();

        $student = Auth::user();

        $query = InternshipOpportunity::with('companyUser')
            ->where('status', 'open')
            ->latest();

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('field', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        $programs = $query->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                    ],
                    'programs' => $programs->map(function (InternshipOpportunity $program) {
                        return [
                            'id' => $program->id,
                            'title' => $program->title,
                            'description' => $program->description,
                            'type' => $program->type,
                            'field' => $program->field,
                            'city' => $program->city,
                            'work_type' => $program->work_type,
                            'education_level' => $program->education_level,
                            'duration' => $program->duration,
                            'deadline' => $program->deadline,
                            'status' => $program->status,
                            'company_name' => $program->companyUser?->company_name ?: $program->companyUser?->name,
                        ];
                    })->values(),
                ],
            ]);
        }

        return view('spa');
    }

    public function programShow(Request $request, $id)
    {
        $this->ensureRole();

        $student = Auth::user();

        $program = InternshipOpportunity::with('companyUser')->findOrFail($id);

        $existingApplication = Application::where('student_id', $student->id)
            ->where('opportunity_id', $program->id)
            ->first();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                    ],
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'description' => $program->description,
                        'requirements' => $program->requirements,
                        'type' => $program->type,
                        'field' => $program->field,
                        'city' => $program->city,
                        'work_type' => $program->work_type,
                        'education_level' => $program->education_level,
                        'duration' => $program->duration,
                        'deadline' => $program->deadline,
                        'status' => $program->status,
                        'company_name' => $program->companyUser?->company_name ?: $program->companyUser?->name,
                    ],
                    'existing_application' => $existingApplication ? [
                        'id' => $existingApplication->id,
                        'company_status' => $existingApplication->company_status,
                        'supervisor_status' => $existingApplication->supervisor_status,
                        'final_status' => $existingApplication->final_status,
                    ] : null,
                ],
            ]);
        }

        return view('spa');
    }

    public function applications(Request $request)
    {
        $this->ensureRole();
        $student = Auth::user();

        $applications = Application::with([
            'opportunity.companyUser'
        ])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        $activeApplication = $applications->first(function ($application) {
            return $application->final_status === 'approved';
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'applications' => $applications->map(function (Application $application) {
                        return [
                            'id' => $application->id,
                            'program_id' => $application->opportunity_id,
                            'program_title' => $application->opportunity?->title,
                            'company_name' => $application->opportunity?->companyUser?->company_name
                                ?: $application->opportunity?->companyUser?->name,
                            'company_status' => $application->company_status,
                            'supervisor_status' => $application->supervisor_status,
                            'final_status' => $application->final_status,
                            'motivation' => $application->motivation,
                            'cv' => $application->cv,
                            'created_at' => optional($application->created_at)->toISOString(),
                        ];
                    })->values(),
                    'active_application' => $activeApplication ? [
                        'id' => $activeApplication->id,
                        'program_title' => $activeApplication->opportunity?->title,
                        'company_name' => $activeApplication->opportunity?->companyUser?->company_name
                            ?: $activeApplication->opportunity?->companyUser?->name,
                        'board_url' => route('tasks.board', ['application' => $activeApplication->id]),
                    ] : null,
                ],
            ]);
        }

        return view('spa');
    }

    public function workspace(Request $request)
    {
        $this->ensureRole();
        $student = Auth::user();

        $activeApplication = Application::with([
            'student',
            'opportunity.companyUser'
        ])
            ->where('student_id', $student->id)
            ->where('final_status', 'approved')
            ->latest()
            ->first();

        $trainingEndDate = null;
        $trainingEnded = false;

        if ($activeApplication && $activeApplication->approved_at && $activeApplication->opportunity?->duration) {
            $trainingEndDate = $activeApplication->approved_at->copy()->addMonths((int) $activeApplication->opportunity->duration)->startOfDay();
            $trainingEnded = now()->startOfDay()->greaterThanOrEqualTo($trainingEndDate);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                    ],
                    'active_application' => $activeApplication ? [
                        'id' => $activeApplication->id,
                        'program_title' => $activeApplication->opportunity?->title,
                        'company_name' => $activeApplication->opportunity?->companyUser?->company_name
                            ?: $activeApplication->opportunity?->companyUser?->name,
                        'company_status' => $activeApplication->company_status,
                        'supervisor_status' => $activeApplication->supervisor_status,
                        'approved_at' => optional($activeApplication->approved_at)->toDateString(),
                        'training_completed_at' => optional($activeApplication->training_completed_at)->toDateString(),
                        'training_end_date' => optional($trainingEndDate)->toDateString(),
                        'training_ended' => $trainingEnded,
                        'progress' => $this->calculateProgress($activeApplication),
                        'board_url' => route('tasks.board', ['application' => $activeApplication->id]),
                        'complete_url' => $activeApplication->training_completed_at
                            ? route('training.complete', ['application' => $activeApplication->id])
                            : null,
                    ] : null,
                ],
            ]);
        }

        return view('spa');
    }
}
