<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipOpportunity;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function admin(Request $request): JsonResponse
    {
        $this->ensureRole('admin');

        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        $userRegistrations = $months->map(function (Carbon $month) {
            return User::whereBetween('created_at', [$month->copy(), $month->copy()->endOfMonth()])
                ->whereIn('role', ['student', 'supervisor', 'company'])
                ->count();
        })->values();

        $programCompletions = $months->map(function (Carbon $month) {
            return Application::whereNotNull('training_completed_at')
                ->whereBetween('training_completed_at', [$month->copy(), $month->copy()->endOfMonth()])
                ->count();
        })->values();

        $totalUsers = User::whereIn('role', ['student', 'supervisor', 'company'])->count();
        $reportsThisMonth = Application::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $pendingGeneration = Application::where('final_status', 'pending')->count();
        $totalCompanies = User::where('role', 'company')->count();
        $activePrograms = InternshipOpportunity::where('status', 'open')->count();
        $completedTrainings = Application::whereNotNull('training_completed_at')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    ['key' => 'total', 'icon' => 'bi bi-file-earmark-text', 'iconClass' => 'bg-primary text-white', 'label' => 'total_users', 'value' => (string) $totalUsers],
                    ['key' => 'monthly', 'icon' => 'bi bi-calendar-check', 'iconClass' => 'bg-success text-white', 'label' => 'reports_this_month', 'value' => (string) $reportsThisMonth],
                    ['key' => 'pending', 'icon' => 'bi bi-clock-history', 'iconClass' => 'bg-warning text-white', 'label' => 'pending_applications', 'value' => (string) $pendingGeneration],
                ],
                'charts' => [
                    'labels' => $months->map(fn (Carbon $month) => $month->format('M'))->values(),
                    'user_registrations' => $userRegistrations,
                    'program_completions' => $programCompletions,
                ],
                'reports' => [
                    [
                        'id' => 1,
                        'name' => 'monthly_performance',
                        'type' => 'performance',
                        'generatedDate' => now()->toDateString(),
                        'format' => 'PDF',
                        'size' => $this->estimateSize($totalUsers),
                        'icon' => 'bi bi-file-earmark-pdf',
                        'color' => '#dc2626',
                        'summary' => [
                            'total_users' => $totalUsers,
                            'total_companies' => $totalCompanies,
                            'active_programs' => $activePrograms,
                            'completed_trainings' => $completedTrainings,
                        ],
                    ],
                    [
                        'id' => 2,
                        'name' => 'company_activity',
                        'type' => 'activity',
                        'generatedDate' => now()->subDay()->toDateString(),
                        'format' => 'Excel',
                        'size' => $this->estimateSize($totalCompanies),
                        'icon' => 'bi bi-file-earmark-spreadsheet',
                        'color' => '#22c55e',
                        'summary' => [
                            'total_companies' => $totalCompanies,
                            'active_programs' => $activePrograms,
                        ],
                    ],
                    [
                        'id' => 3,
                        'name' => 'user_statistics',
                        'type' => 'statistics',
                        'generatedDate' => now()->subDays(2)->toDateString(),
                        'format' => 'CSV',
                        'size' => $this->estimateSize($totalUsers + $completedTrainings),
                        'icon' => 'bi bi-file-earmark-code',
                        'color' => '#3b82f6',
                        'summary' => [
                            'registrations' => $userRegistrations->sum(),
                            'completions' => $programCompletions->sum(),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function company(Request $request): JsonResponse
    {
        $this->ensureRole('company');

        $companyId = Auth::id();
        $applications = Application::with('opportunity')
            ->whereHas('opportunity', fn ($q) => $q->where('company_user_id', $companyId))
            ->get();

        $activePrograms = InternshipOpportunity::where('company_user_id', $companyId)->where('status', 'open')->count();
        $approvedApplications = $applications->where('company_status', 'approved')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    [
                        'key' => 'total',
                        'label' => 'total_reports',
                        'value' => (string) $applications->count(),
                        'icon' => 'bi bi-files',
                        'iconClass' => 'bg-primary',
                        'trend' => '+' . $applications->where('created_at', '>=', now()->subDays(30))->count() . ' this month',
                        'trendClass' => 'text-success',
                        'trendIcon' => 'bi bi-arrow-up',
                    ],
                    [
                        'key' => 'applications',
                        'label' => 'total_applications',
                        'value' => (string) $applications->count(),
                        'icon' => 'bi bi-people',
                        'iconClass' => 'bg-success',
                        'trend' => '+' . $applications->where('created_at', '>=', now()->subDays(7))->count() . ' this week',
                        'trendClass' => 'text-success',
                        'trendIcon' => 'bi bi-arrow-up',
                    ],
                    [
                        'key' => 'programs',
                        'label' => 'active_programs',
                        'value' => (string) $activePrograms,
                        'icon' => 'bi bi-journal-bookmark',
                        'iconClass' => 'bg-info',
                        'trend' => $approvedApplications . ' approved',
                        'trendClass' => 'text-warning',
                        'trendIcon' => 'bi bi-exclamation-circle',
                    ],
                ],
                'reports' => [
                    [
                        'id' => 1,
                        'title' => 'monthly_applications_report',
                        'description' => 'Applications received: ' . $applications->count(),
                        'date' => now()->toDateString(),
                        'format' => 'PDF',
                        'size' => $this->estimateSize($applications->count()),
                        'icon' => 'bi bi-file-earmark-pdf',
                        'iconClass' => 'pdf',
                    ],
                    [
                        'id' => 2,
                        'title' => 'program_performance_q4',
                        'description' => 'Active programs: ' . $activePrograms,
                        'date' => now()->subDay()->toDateString(),
                        'format' => 'Excel',
                        'size' => $this->estimateSize($activePrograms),
                        'icon' => 'bi bi-file-earmark-spreadsheet',
                        'iconClass' => 'excel',
                    ],
                    [
                        'id' => 3,
                        'title' => 'applicant_demographics',
                        'description' => 'Approved applicants: ' . $approvedApplications,
                        'date' => now()->subDays(2)->toDateString(),
                        'format' => 'PDF',
                        'size' => $this->estimateSize($approvedApplications),
                        'icon' => 'bi bi-file-earmark-pdf',
                        'iconClass' => 'pdf',
                    ],
                ],
            ],
        ]);
    }

    public function supervisor(Request $request): JsonResponse
    {
        $this->ensureRole('supervisor');

        $supervisor = Auth::user();
        $students = User::where('role', 'student')->where('supervisor_code', $supervisor->supervisor_code)->get();
        $approvedApplications = Application::with(['student', 'opportunity'])
            ->whereHas('student', fn ($q) => $q->where('supervisor_code', $supervisor->supervisor_code))
            ->where('final_status', 'approved')
            ->get();

        $applicationIds = $approvedApplications->pluck('id');
        $tasks = Task::whereIn('application_id', $applicationIds)->get();

        $averageGrade = $tasks->filter(fn (Task $task) => $task->company_score !== null || $task->supervisor_score !== null)
            ->map(function (Task $task) {
                $scores = collect([$task->company_score, $task->supervisor_score])->filter(fn ($score) => $score !== null);
                return $scores->isNotEmpty() ? (int) round($scores->avg()) : null;
            })
            ->filter()
            ->avg();

        $completionRate = $approvedApplications->count() > 0
            ? round(($approvedApplications->whereNotNull('training_completed_at')->count() / $approvedApplications->count()) * 100)
            : 0;

        $studentRows = $approvedApplications->map(function (Application $application) {
            $progress = $this->applicationProgress($application);
            return [
                'application' => $application,
                'progress' => $progress,
            ];
        });

        $performanceDistribution = [
            $this->distributionRow('excellent', $studentRows, fn (array $row) => $row['progress'] >= 85, 'bg-success'),
            $this->distributionRow('good', $studentRows, fn (array $row) => $row['progress'] >= 70 && $row['progress'] < 85, 'bg-primary'),
            $this->distributionRow('average', $studentRows, fn (array $row) => $row['progress'] >= 50 && $row['progress'] < 70, 'bg-warning'),
            $this->distributionRow('below_average', $studentRows, fn (array $row) => $row['progress'] < 50, 'bg-danger'),
        ];

        $gradedTasks = $tasks->whereNotNull('supervisor_score')->count();
        $pendingReviews = $tasks->filter(fn (Task $task) => !empty($task->student_solution) && $task->supervisor_score === null)->count();
        $avgResponseTime = $tasks->whereNotNull('supervisor_score')
            ->filter(fn (Task $task) => $task->created_at && $task->updated_at)
            ->avg(fn (Task $task) => max(0, $task->created_at->diffInDays($task->updated_at)));

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    [
                        'key' => 'students',
                        'icon' => 'fas fa-users',
                        'iconClass' => 'bg-primary text-white',
                        'label' => 'total_students',
                        'value' => (string) $students->count(),
                        'trend' => '+' . $students->where('created_at', '>=', now()->subDays(30))->count() . '%',
                        'trendClass' => 'trend-up',
                        'trendIcon' => 'fas fa-arrow-up',
                    ],
                    [
                        'key' => 'grade',
                        'icon' => 'fas fa-medal',
                        'iconClass' => 'bg-warning text-white',
                        'label' => 'average_grade',
                        'value' => ($averageGrade ? round($averageGrade) : 0) . '%',
                        'trend' => 'Live',
                        'trendClass' => 'trend-up',
                        'trendIcon' => 'fas fa-arrow-up',
                    ],
                    [
                        'key' => 'completion',
                        'icon' => 'fas fa-check-circle',
                        'iconClass' => 'bg-success text-white',
                        'label' => 'completion_rate',
                        'value' => $completionRate . '%',
                        'trend' => 'Current',
                        'trendClass' => 'trend-up',
                        'trendIcon' => 'fas fa-arrow-up',
                    ],
                    [
                        'key' => 'tasks',
                        'icon' => 'fas fa-bullseye',
                        'iconClass' => 'bg-info text-white',
                        'label' => 'active_tasks',
                        'value' => (string) $tasks->whereIn('status', ['todo', 'progress'])->count(),
                        'trend' => (string) $tasks->where('status', 'done')->count(),
                        'trendClass' => 'trend-down',
                        'trendIcon' => 'fas fa-arrow-down',
                    ],
                ],
                'reports' => [
                    [
                        'id' => 1,
                        'title' => 'student_performance_report',
                        'description' => 'performance_report_desc',
                        'category' => 'student',
                        'badgeClass' => 'bg-primary-subtle text-primary',
                        'icon' => 'fas fa-user-graduate',
                        'iconClass' => 'bg-light text-primary',
                        'lastGenerated' => now()->toDateString(),
                        'format' => 'PDF',
                        'formatIcon' => 'far fa-file-pdf text-danger',
                        'size' => $this->estimateSize($students->count()),
                    ],
                    [
                        'id' => 2,
                        'title' => 'program_progress_summary',
                        'description' => 'progress_summary_desc',
                        'category' => 'program',
                        'badgeClass' => 'bg-warning-subtle text-warning',
                        'icon' => 'fas fa-book',
                        'iconClass' => 'bg-light text-warning',
                        'lastGenerated' => now()->subDay()->toDateString(),
                        'format' => 'Excel',
                        'formatIcon' => 'far fa-file-excel text-success',
                        'size' => $this->estimateSize($approvedApplications->count()),
                    ],
                ],
                'performanceData' => $performanceDistribution,
                'quickStats' => [
                    ['key' => 'graded', 'icon' => 'fas fa-check-double', 'iconClass' => 'text-success', 'label' => 'submissions_graded', 'value' => (string) $gradedTasks],
                    ['key' => 'pending', 'icon' => 'far fa-clock', 'iconClass' => 'text-warning', 'label' => 'pending_reviews', 'value' => (string) $pendingReviews],
                    ['key' => 'response', 'icon' => 'far fa-star', 'iconClass' => 'text-primary', 'label' => 'avg_response_time', 'value' => number_format((float) ($avgResponseTime ?? 0), 1) . ' days'],
                ],
            ],
        ]);
    }

    private function ensureRole(string $role): void
    {
        abort_unless(Auth::check() && Auth::user()->role === $role, 403);
    }

    private function estimateSize(int $rows): string
    {
        $kb = max(120, $rows * 24);

        if ($kb >= 1024) {
            return number_format($kb / 1024, 1) . ' MB';
        }

        return $kb . ' KB';
    }

    private function applicationProgress(Application $application): int
    {
        if (! $application->approved_at || ! $application->opportunity?->duration) {
            return 0;
        }

        if ($application->training_completed_at) {
            return 100;
        }

        $start = Carbon::parse($application->approved_at);
        $end = Carbon::parse($application->approved_at)->addMonths((int) $application->opportunity->duration);
        $totalSeconds = max(1, $start->diffInSeconds($end));
        $elapsedSeconds = min($totalSeconds, max(0, $start->diffInSeconds(now())));

        return (int) round(($elapsedSeconds / $totalSeconds) * 100);
    }

    private function distributionRow(string $label, Collection $rows, callable $filter, string $barClass): array
    {
        $count = $rows->filter($filter)->count();
        $total = max(1, $rows->count());

        return [
            'label' => $label,
            'count' => $count,
            'percentage' => (int) round(($count / $total) * 100),
            'barClass' => $barClass,
        ];
    }
}
