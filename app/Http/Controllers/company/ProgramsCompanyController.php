<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\InternshipOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramsCompanyController extends Controller
{
    public function programsPage(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $companyId = Auth::id();

        $approvedStudentsFilter = function ($query) {
            $query->where('company_status', 'approved')
                ->where('supervisor_status', 'approved')
                ->where('final_status', 'approved')
                ->whereNotNull('approved_at')
                ->whereNull('training_completed_at');
        };

        $query = InternshipOpportunity::withCount([
            'applications',
            'applications as approved_students_count' => $approvedStudentsFilter,
        ])
            ->withMin(['applications as training_started_at' => $approvedStudentsFilter], 'approved_at')
            ->where('company_user_id', $companyId);

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $status = $request->get('status');
        if (in_array($status, ['active', 'completed', 'open', 'closed'], true)) {
            $query->where('status', in_array($status, ['active', 'open'], true) ? 'open' : 'closed');
        }

        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'students') {
            $query->orderByDesc('applications_count');
        } else {
            $query->latest();
        }

        $paginated = $query->paginate(12);

        $totalPrograms = InternshipOpportunity::where('company_user_id', $companyId)->count();
        $openPrograms = InternshipOpportunity::where('company_user_id', $companyId)->where('status', 'open')->count();
        $closedPrograms = InternshipOpportunity::where('company_user_id', $companyId)->where('status', 'closed')->count();

        if ($request->expectsJson() || $request->ajax()) {
            $programs = $paginated->getCollection()->map(fn ($program) => [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->description,
                'status' => $program->status === 'open' ? 'active' : 'completed',
                'duration' => $program->duration,
                'duration_weeks' => $program->duration,
                'start_date' => $program->training_started_at,
                'published_at' => $program->created_at?->toDateString(),
                'applicants_count' => $program->applications_count,
                'students_count' => $program->approved_students_count,
                'progress' => $program->approved_students_count > 0
                    ? ($program->status === 'open' ? 45 : 100)
                    : 0,
            ])->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'programs' => $programs,
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page' => $paginated->lastPage(),
                        'per_page' => $paginated->perPage(),
                        'total' => $paginated->total(),
                    ],
                    'stats' => [
                        ['key' => 'total', 'icon' => 'bi bi-journal-bookmark', 'iconClass' => 'bg-primary', 'label' => 'total_programs', 'value' => (string) $totalPrograms],
                        ['key' => 'active', 'icon' => 'bi bi-play-circle', 'iconClass' => 'bg-success', 'label' => 'active', 'value' => (string) $openPrograms],
                        ['key' => 'draft', 'icon' => 'bi bi-file-earmark', 'iconClass' => 'bg-warning', 'label' => 'draft', 'value' => '0'],
                        ['key' => 'students', 'icon' => 'bi bi-people', 'iconClass' => 'bg-info', 'label' => 'total_students', 'value' => (string) $programs->sum('students_count')],
                    ],
                ],
            ]);
        }

        return view('spa');
    }

    public function createProgramPage(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success']);
        }

        return view('spa');
    }

    public function editProgramPage(Request $request, $id)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $program = InternshipOpportunity::where('company_user_id', Auth::id())->findOrFail($id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
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
                    'duration_weeks' => $program->duration,
                    'start_date' => null,
                    'published_at' => $program->created_at?->toDateString(),
                    'end_date' => $program->deadline,
                    'status' => $program->status === 'open' ? 'active' : 'completed',
                    'requirements' => $program->requirements ? preg_split("/\r\n|\n|\r/", $program->requirements) : [],
                ],
            ]);
        }

        return view('spa');
    }

    public function show(Request $request, $id)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $program = InternshipOpportunity::with(['applications.student'])
            ->withCount('applications')
            ->where('company_user_id', Auth::id())
            ->findOrFail($id);

        if ($request->expectsJson() || $request->ajax()) {
            $approvedApplications = $program->applications
                ->filter(fn ($application) => $application->company_status === 'approved'
                    && $application->supervisor_status === 'approved'
                    && $application->final_status === 'approved'
                    && filled($application->approved_at)
                    && blank($application->training_completed_at));
            $trainingStartedAt = $approvedApplications->min('approved_at');
            $trainingStartedAt = $trainingStartedAt ? $trainingStartedAt->toDateString() : null;

            $recentApplicants = $program->applications
                ->sortByDesc('created_at')
                ->take(6)
                ->map(function ($application) {
                    $name = (string) optional($application->student)->name;
                    return [
                        'id' => $application->id,
                        'name' => $name,
                        'initials' => collect(explode(' ', trim($name)))->filter()->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') ?: 'ST',
                        'avatarColor' => '#7c3aed',
                        'applied_at' => optional($application->created_at)->toISOString(),
                        'status' => $application->company_status === 'approved'
                            ? 'accepted'
                            : ($application->company_status === 'rejected' ? 'rejected' : 'pending'),
                    ];
                })->values();

            return response()->json([
                'status' => 'success',
                'data' => [
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
                    'duration_weeks' => $program->duration,
                    'start_date' => $trainingStartedAt,
                    'published_at' => $program->created_at?->toDateString(),
                    'end_date' => $program->deadline,
                    'status' => $program->status === 'open' ? 'active' : 'completed',
                    'progress' => $approvedApplications->count() > 0
                        ? ($program->status === 'open' ? 45 : 100)
                        : 0,
                    'students_count' => $approvedApplications->count(),
                    'applicants_count' => $program->applications_count,
                    'positions_available' => 30,
                    'requirements' => $program->requirements ? preg_split("/\r\n|\n|\r/", $program->requirements) : [],
                    'skills_required' => [],
                    'timeline' => [],
                    'recent_applicants' => $recentApplicants,
                ],
            ]);
        }

        return view('spa');
    }
}
