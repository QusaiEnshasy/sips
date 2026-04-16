<?php

namespace App\Http\Controllers\company;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipOpportunity;
use Illuminate\Support\Facades\Auth;

class DashboardCompanyController extends Controller
{
    public function dashboard(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        $companyId = Auth::id();

        $totalPrograms = InternshipOpportunity::where('company_user_id', $companyId)->count();

        $openPrograms = InternshipOpportunity::where('company_user_id', $companyId)
            ->where('status', 'open')
            ->count();

        $pendingApplications = Application::whereHas('opportunity', function ($q) use ($companyId) {
            $q->where('company_user_id', $companyId);
        })->where('company_status', 'pending')->count();

        $recentApplicants = Application::with(['student', 'opportunity'])
            ->whereHas('opportunity', function ($q) use ($companyId) {
                $q->where('company_user_id', $companyId);
            })
            ->latest()
            ->take(5)
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'company' => [
                        'id' => Auth::id(),
                        'name' => Auth::user()->company_name ?: Auth::user()->name,
                        'is_verified' => Auth::user()->status === 'active',
                    ],
                    'total_programs' => $totalPrograms,
                    'active_programs' => $openPrograms,
                    'total_applicants' => Application::whereHas('opportunity', function ($q) use ($companyId) {
                        $q->where('company_user_id', $companyId);
                    })->count(),
                    'pending_reviews' => $pendingApplications,
                    'recent_programs' => InternshipOpportunity::withCount('applications')
                        ->where('company_user_id', $companyId)
                        ->latest()
                        ->take(6)
                        ->get()
                        ->map(fn ($program) => [
                            'id' => $program->id,
                            'title' => $program->title,
                            'description' => $program->description,
                            'status' => $program->status === 'open' ? 'active' : 'completed',
                            'applicants_count' => $program->applications_count,
                            'duration_weeks' => $program->duration,
                            'created_at' => optional($program->created_at)->toISOString(),
                        ])
                        ->values(),
                    'recent_applicants' => $recentApplicants->map(fn ($app) => [
                        'id' => $app->id,
                        'student_name' => optional($app->student)->name,
                        'student_email' => optional($app->student)->email,
                        'program_title' => optional($app->opportunity)->title,
                        'applied_at' => optional($app->created_at)->toISOString(),
                        'status' => $app->company_status === 'approved'
                            ? 'accepted'
                            : ($app->company_status === 'rejected' ? 'rejected' : 'pending'),
                        'board_url' => $app->final_status === 'approved'
                            ? route('tasks.board', ['application' => $app->id])
                            : null,
                    ])->values(),
                ],
            ]);
        }

        return view('spa');
    }
}
