<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternshipOpportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403);
    }

    private function actionResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function adminDashboard(Request $request)
    {
        $this->ensureAdmin();

        $totalUsers = User::whereIn('role', ['student', 'supervisor'])->count();
        $totalCompanies = User::where('role', 'company')->count();
        $totalAll = User::count();
        $currentDate = now()->format('M d, Y');

        $recentActivities = User::select('id', 'name', 'email', 'role', 'status', 'created_at', 'company_name')
            ->whereIn('role', ['company', 'supervisor'])
            ->latest()
            ->take(5)
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            $pendingCompaniesCount = User::where('role', 'company')->where('status', 'pending')->count();

            return response()->json([
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_companies' => $totalCompanies,
                    'total_all' => $totalAll,
                    'pending_companies' => $pendingCompaniesCount,
                ],
                'recent_activities' => $recentActivities,
                'pending_companies' => User::select('id', 'company_name', 'name', 'email', 'industry', 'status')
                    ->where('role', 'company')
                    ->where('status', 'pending')
                    ->latest()
                    ->take(8)
                    ->get(),
                'current_date' => $currentDate,
            ]);
        }

        return view('spa');
    }

    public function adminUsersPage(Request $request)
    {
        $this->ensureAdmin();

        $supervisors = User::where('role', 'supervisor')->latest()->get();

        $totalSupervisors = User::where('role', 'supervisor')->count();
        $approvedSupervisors = User::where('role', 'supervisor')->where('status', 'active')->count();
        $pendingSupervisors = User::where('role', 'supervisor')->where('status', 'pending')->count();
        $rejectedSupervisors = User::where('role', 'supervisor')->where('status', 'rejected')->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'stats' => [
                    'total_supervisors' => $totalSupervisors,
                    'approved_supervisors' => $approvedSupervisors,
                    'pending_supervisors' => $pendingSupervisors,
                    'rejected_supervisors' => $rejectedSupervisors,
                ],
                'users' => $supervisors,
            ]);
        }

        return view('spa');
    }

    public function adminCompaniesPage(Request $request)
    {
        $this->ensureAdmin();

        $companies = User::where('role', 'company')->latest()->get();
        $totalCompanies = User::where('role', 'company')->count();
        $approvedCompanies = User::where('role', 'company')->where('status', 'active')->count();
        $pendingCompanies = User::where('role', 'company')->where('status', 'pending')->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'stats' => [
                    'total_companies' => $totalCompanies,
                    'approved_companies' => $approvedCompanies,
                    'pending_companies' => $pendingCompanies,
                ],
                'companies' => $companies,
            ]);
        }

        return view('spa');
    }

    public function updateSupervisorStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'action' => ['required', 'in:active,reject,delete'],
        ]);

        $supervisor = User::where('id', $id)->where('role', 'supervisor')->firstOrFail();

        if ($validated['action'] === 'delete') {
            $supervisor->delete();
            return $this->actionResponse($request, 'deleted successfully.');
        }

        $supervisor->status = $validated['action'] === 'active' ? 'active' : 'rejected';
        $supervisor->save(); 
        $this->adminNotification($id, "Updated Successfully", "Update Supervisor Status Successfully");

        return $this->actionResponse($request, 'supervisor status updated successfully.');
    }

    public function updateCompanyStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'action' => ['required', 'in:active,reject,delete'],
        ]);

        $company = User::where('id', $id)->where('role', 'company')->firstOrFail();

        if ($validated['action'] === 'delete') {
            DB::transaction(function () use ($company) {
                InternshipOpportunity::withTrashed()
                    ->where('company_user_id', $company->id)
                    ->get()
                    ->each
                    ->forceDelete();

                $company->delete();
            });

            return $this->actionResponse($request, 'company deleted successfully.');
        }

        $company->status = $validated['action'] === 'active' ? 'active' : 'rejected';
        $company->save();
        $this->adminNotification($id, "Updated Successfully", "Update Company Status Successfully");

        return $this->actionResponse($request, 'company status updated successfully.');
    }

    public function createSupervisor(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'university_id' => ['required', 'numeric', 'unique:users,university_id'],
            'phone_number' => ['required', 'string', 'min:9'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $supervisorCode = $this->generateSupervisorCode();

        $user = User::create([
            'role' => 'supervisor',
            'name' => trim($validated['name']),
            'email' => $validated['email'] ?: null,
            'password' => Hash::make($validated['password']),
            'phone_number' => trim($validated['phone_number']),
            'university_id' => $validated['university_id'],
            'supervisor_code' => $supervisorCode,
            'status' => 'active',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Supervisor created successfully.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'university_id' => $user->university_id,
                    'phone_number' => $user->phone_number,
                    'supervisor_code' => $user->supervisor_code,
                ],
            ]);
        }
        $this->adminNotification($user->id, "Add Successfully", "Add  Supervisor Successfully");

        return back()->with('success', 'Supervisor created successfully.');
    }

    private function generateSupervisorCode(): string
    {
        do {
            $code = 'SUP-' . strtoupper(Str::random(6));
        } while (User::where('supervisor_code', $code)->exists());

        return $code;
    }

    private function adminNotification(int $userId,string $title,string $description): void
    {
        try {
            if (! Schema::hasTable('user_notifications')) {
                return;
            }

            UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'type' => 'success',
                // 'meta' => ['category' => 'auth'],
            ]);
        } catch (\Throwable) {
        }
    }
}
