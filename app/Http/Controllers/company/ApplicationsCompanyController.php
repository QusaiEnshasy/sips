<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Schema;

class ApplicationsCompanyController extends Controller
{
    private function trainingProgressPercent(Application $application): int
    {
        $approvedAt = $application->approved_at;
        $durationMonths = (int) optional($application->opportunity)->duration;
        if (! $approvedAt || $durationMonths <= 0) {
            return 0;
        }
            
        $start = $approvedAt->copy()->startOfDay();
        $end = $approvedAt->copy()->addMonths($durationMonths)->startOfDay();
        $totalDays = max(1, $start->diffInDays($end));
        $elapsed = min($totalDays, $start->diffInDays(now()->startOfDay()));
        
        return (int) max(0, min(100, round(($elapsed / $totalDays) * 100)));
    }

    public function applicantsPage(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $companyId = Auth::id();

        $baseQuery = Application::with(['student', 'opportunity'])
            ->whereHas('opportunity', function ($q) use ($companyId) {
                $q->where('company_user_id', $companyId);
            })
            ->latest();

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('student', function ($studentQ) use ($search) {
                    $studentQ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })->orWhereHas('opportunity', function ($opportunityQ) use ($search) {
                    $opportunityQ->where('title', 'like', '%' . $search . '%');
                });
            });
        }

        $status = $request->get('status');
        if (in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            $baseQuery->where('company_status', $status === 'accepted' ? 'approved' : $status);
        }

        $programId = $request->get('program_id');
        if ($programId && $programId !== 'all') {
            $baseQuery->where('opportunity_id', (int) $programId);
        }

        $paginated = $baseQuery->paginate(15);

        $totalApplications = Application::whereHas('opportunity', function ($q) use ($companyId) {
            $q->where('company_user_id', $companyId);
        })->count();
        $pendingApplications = Application::whereHas('opportunity', function ($q) use ($companyId) {
            $q->where('company_user_id', $companyId);
        })->where('company_status', 'pending')->count();
        $approvedApplications = Application::whereHas('opportunity', function ($q) use ($companyId) {
            $q->where('company_user_id', $companyId);
        })->where('company_status', 'approved')->count();
        $rejectedApplications = Application::whereHas('opportunity', function ($q) use ($companyId) {
            $q->where('company_user_id', $companyId);
        })->where('company_status', 'rejected')->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'applicants' => $paginated->getCollection()->map(function ($application) {
                        $name = (string) optional($application->student)->name;
                        $canOpenBoard = $application->final_status === 'approved'
                            && ! empty($application->approved_at)
                            && optional($application->student)->status === 'active';
                        return [
                            'id' => $application->id,
                            'name' => $name,
                            'email' => optional($application->student)->email,
                            'initials' => collect(explode(' ', trim($name)))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                            'avatarColor' => '#7c3aed',
                            'program_title' => optional($application->opportunity)->title,
                            'applied_at' => optional($application->created_at)->toISOString(),
                            'match_percentage' => 75,
                            'status' => $application->company_status === 'approved'
                                ? 'accepted'
                                : ($application->company_status === 'rejected' ? 'rejected' : 'pending'),
                            'can_open_board' => $canOpenBoard,
                            'board_url' => $canOpenBoard ? route('tasks.board', ['application' => $application->id]) : null,
                        ];
                    })->values(),
                    'approved_trainees' => Application::with(['student', 'opportunity'])
                        ->whereHas('opportunity', function ($q) use ($companyId) {
                            $q->where('company_user_id', $companyId);
                        })
                        ->where('final_status', 'approved')
                        ->whereNotNull('approved_at')
                        ->whereHas('student', function ($q) {
                            $q->where('status', 'active');
                        })
                        ->latest()
                        ->get()
                        ->map(function ($application) {
                            $studentName = (string) optional($application->student)->name;
                            return [
                                'application_id' => $application->id,
                                'student_name' => $studentName,
                                'student_email' => optional($application->student)->email,
                                'program_title' => optional($application->opportunity)->title,
                                'progress_percent' => $this->trainingProgressPercent($application),
                                'approved_at' => optional($application->approved_at)->toISOString(),
                                'board_url' => route('tasks.board', ['application' => $application->id]),
                            ];
                        })
                        ->values(),
                    'programs' => \App\Models\InternshipOpportunity::where('company_user_id', $companyId)
                        ->select('id', 'title')
                        ->latest()
                        ->get(),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page' => $paginated->lastPage(),
                        'per_page' => $paginated->perPage(),
                        'total' => $paginated->total(),
                    ],
                    'stats' => [
                        ['key' => 'total', 'icon' => 'bi bi-people', 'iconClass' => 'bg-primary', 'label' => 'total_applicants', 'value' => (string) $totalApplications],
                        ['key' => 'pending', 'icon' => 'bi bi-clock', 'iconClass' => 'bg-warning', 'label' => 'pending', 'value' => (string) $pendingApplications],
                        ['key' => 'accepted', 'icon' => 'bi bi-check-circle', 'iconClass' => 'bg-success', 'label' => 'accepted', 'value' => (string) $approvedApplications],
                        ['key' => 'rejected', 'icon' => 'bi bi-x-circle', 'iconClass' => 'bg-danger', 'label' => 'rejected', 'value' => (string) $rejectedApplications],
                    ],
                ],
            ]);
        }

        return view('spa');
    }

    public function applicantDetails(Request $request, $id)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $companyId = Auth::id();

        $application = Application::with(['student', 'opportunity'])
            ->whereHas('opportunity', function ($q) use ($companyId) {
                $q->where('company_user_id', $companyId);
            })
            ->findOrFail($id);

        if ($request->expectsJson() || $request->ajax()) {
            $name = (string) optional($application->student)->name;
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $application->id,
                    'name' => $name,
                    'email' => optional($application->student)->email,
                    'initials' => collect(explode(' ', trim($name)))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                    'avatarColor' => '#7c3aed',
                    'phone' => optional($application->student)->phone,
                    'location' => optional($application->student)->city,
                    'education' => optional($application->student)->education_level,
                    'experience' => null,
                    'skills' => $application->skills ? array_filter(array_map('trim', explode(',', (string) $application->skills))) : [],
                    'match_percentage' => 75,
                    'status' => $application->company_status === 'approved'
                        ? 'accepted'
                        : ($application->company_status === 'rejected' ? 'rejected' : 'pending'),
                    'final_status' => $application->final_status,
                    'can_open_board' => $application->final_status === 'approved'
                        && ! empty($application->approved_at)
                        && optional($application->student)->status === 'active',
                    'board_url' => ($application->final_status === 'approved'
                        && ! empty($application->approved_at)
                        && optional($application->student)->status === 'active')
                        ? route('tasks.board', ['application' => $application->id])
                        : null,
                    'training_progress_percent' => $this->trainingProgressPercent($application),
                    'program_title' => optional($application->opportunity)->title,
                    'applied_at' => optional($application->created_at)->toISOString(),
                    'updated_at' => optional($application->updated_at)->toISOString(),
                    'documents' => $application->cv ? [[
                        'name' => basename((string) $application->cv),
                        'type' => 'pdf',
                        'size' => 'CV',
                        'uploaded_at' => optional($application->created_at)->toISOString(),
                        'url' => route('applications.cv', ['application' => $application->id]),
                        'download_url' => route('applications.cv', ['application' => $application->id, 'download' => 1]),
                        'color' => '#dc2626',
                    ]] : [],
                    'notes' => [],
                ],
            ]);
        }

        return view('spa');
    }

}
