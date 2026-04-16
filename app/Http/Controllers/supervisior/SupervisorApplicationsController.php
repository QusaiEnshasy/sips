<?php

namespace App\Http\Controllers\supervisior;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupervisorApplicationsController extends Controller
{
    public function index()
    {
        abort_unless(Auth::check() && Auth::user()->role === 'supervisor', 403);

        $supervisor = Auth::user();

        $applications = Application::with([
            'student',
            'opportunity.companyUser'
        ])
            ->whereHas('student', function ($q) use ($supervisor) {
                $q->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->latest()
            ->get()
            ->each(function ($application) {
                $application->supervisor_status = $application->supervisor_status ?? 'pending';
                $application->company_status = $application->company_status ?? 'pending';
                $application->final_status = $application->final_status ?? 'pending';
            });

        $totalApplications = $applications->count();
        $pendingApplications = $applications->where('supervisor_status', 'pending')->count();
        $approvedApplications = $applications->where('supervisor_status', 'approved')->count();
        $rejectedApplications = $applications->where('supervisor_status', 'rejected')->count();

        return view('spa');
    }

    public function show(Request $request, $id)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'supervisor', 403);

        $supervisor = Auth::user();

        $application = Application::with([
            'student',
            'opportunity.companyUser'
        ])
            ->whereHas('student', function ($q) use ($supervisor) {
                $q->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->findOrFail($id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $application->id,
                    'student_name' => $application->student?->name,
                    'student_email' => $application->student?->email,
                    'program_title' => $application->opportunity?->title,
                    'company_name' => $application->opportunity?->companyUser?->company_name ?: $application->opportunity?->companyUser?->name,
                    'supervisor_status' => $application->supervisor_status,
                    'company_status' => $application->company_status,
                    'final_status' => $application->final_status,
                    'created_at' => optional($application->created_at)->toISOString(),
                ],
            ]);
        }

        return view('spa');
    }

    public function applicationsPage(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role === 'supervisor', 403);

        $supervisor = Auth::user();

        $applications = \App\Models\Application::with(['student', 'opportunity'])
            ->whereHas('student', function ($q) use ($supervisor) {
                $q->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->latest()
            ->get()
            ->each(function ($application) {
                $application->supervisor_status = $application->supervisor_status ?? 'pending';
                $application->company_status = $application->company_status ?? 'pending';
                $application->final_status = $application->final_status ?? 'pending';
            });

        $totalApplications = $applications->count();
        $pendingApplications = $applications->where('supervisor_status', 'pending')->count();
        $approvedApplications = $applications->where('supervisor_status', 'approved')->count();
        $rejectedApplications = $applications->where('supervisor_status', 'rejected')->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_applications' => $totalApplications,
                        'pending_applications' => $pendingApplications,
                        'approved_applications' => $approvedApplications,
                        'rejected_applications' => $rejectedApplications,
                    ],
                    'applications' => $applications->map(fn ($app) => [
                        'id' => $app->id,
                        'student_name' => $app->student?->name,
                        'student_email' => $app->student?->email,
                        'program_title' => $app->opportunity?->title,
                        'supervisor_status' => $app->supervisor_status,
                        'company_status' => $app->company_status,
                        'final_status' => $app->final_status,
                        'created_at' => optional($app->created_at)->toISOString(),
                    ])->values(),
                ],
            ]);
        }

        return view('spa');
    }
}
