<?php

namespace App\Http\Controllers\application;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    private function actionResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    private function ensureRole(Request $request, string $role): void
    {
        abort_unless($request->user() && $request->user()->role === $role, 403);
    }

    private function updateFinalStatus(Application $application): void
    {
        if ($application->company_status === 'approved' && $application->supervisor_status === 'approved') {
            $application->final_status = 'approved';
            if (! $application->approved_at) {
                $application->approved_at = now();
            }
            return;
        }

        if ($application->company_status === 'rejected' || $application->supervisor_status === 'rejected') {
            $application->final_status = 'rejected';
            return;
        }

        $application->final_status = 'pending';
    }

    private function assertCanCompleteTraining(Application $application): void
    {
        abort_unless($application->final_status === 'approved', 422, 'إنهاء التدريب متاح فقط بعد القبول النهائي.');
        abort_if($application->training_completed_at, 422, 'تم إنهاء التدريب مسبقًا.');

        $durationMonths = (int) ($application->opportunity?->duration ?? 0);
        abort_unless($application->approved_at && $durationMonths > 0, 422, 'مدة التدريب غير مهيأة بعد.');

        $endDate = $application->approved_at->copy()->addMonths($durationMonths)->startOfDay();
        abort_unless(now()->startOfDay()->greaterThanOrEqualTo($endDate), 422, 'لم تنتهِ مدة التدريب بعد.');
    }

    public function applyApplication(Request $request): RedirectResponse
    {
        $this->ensureRole($request, 'student');

        $validated = $request->validate([
            'opportunity_id' => ['required', 'exists:internship_opportunities,id'],
            'skills' => ['nullable', 'string'],
            'motivation' => ['nullable', 'string', 'max:500'],
            'cv' => ['required', 'file', 'mimes:pdf'],
        ]);

        $cvPath = $request->file('cv')->store('cvs', 'public');

        $application = Application::create([
            'student_id' => $request->user()->id,
            'opportunity_id' => $validated['opportunity_id'],
            'skills' => $validated['skills'] ?? null,
            'motivation' => $validated['motivation'] ?? null,
            'cv' => $cvPath,
            'company_status' => 'pending',
            'supervisor_status' => 'pending',
            'final_status' => 'pending',
        ]);

        // Student notification
        $this->notifications->notifyUser(
            userId: $application->student_id,
            title: 'Application Submitted',
            description: 'تم تقديم الطلب بنجاح وهو الآن قيد المراجعة.',
            type: 'success',
            meta: ['category' => 'application']
        );

        // Company notification
        $companyId = (int) optional($application->opportunity)->company_user_id;
        if ($companyId > 0) {
            $this->notifications->notifyUser(
                userId: $companyId,
                title: 'New Application',
                description: 'يوجد طلب تدريب جديد يحتاج مراجعة.',
                type: 'info',
                meta: ['category' => 'application']
            );
        }

        return back()->with('success', 'تم تقديم الطلب بنجاح، وسيتم إشعارك بحالة الطلب قريبًا.');
    }

    public function companyApplicationApprove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole($request, 'company');

        $application = Application::with(['student'])->findOrFail($id);
        $application->company_status = 'approved';
        $this->updateFinalStatus($application);
        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Company Approved Your Application',
            description: 'تمت الموافقة على طلبك من الشركة.',
            type: 'success',
            meta: ['category' => 'application']
        );

        return $this->actionResponse($request, 'تمت الموافقة على الطلب بنجاح.');
    }

    public function companyApplicationReject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole($request, 'company');
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $reason = trim($validated['reason']);

        $application = Application::findOrFail($id);
        $application->company_status = 'rejected';
        $application->final_status = 'rejected';
        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Company Rejected Your Application',
            description: 'تم رفض طلبك من الشركة.',
            type: 'danger',
            meta: ['category' => 'application', 'reason' => $reason]
        );

        return $this->actionResponse($request, 'تم رفض الطلب بنجاح.');
    }

    public function supervisorApplicationApprove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole($request, 'supervisor');

        $application = Application::findOrFail($id);
        $application->supervisor_status = 'approved';
        $this->updateFinalStatus($application);
        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Supervisor Approved Your Application',
            description: 'تمت الموافقة على طلبك من المشرف.',
            type: 'success',
            meta: ['category' => 'application']
        );

        return $this->actionResponse($request, 'تمت الموافقة على الطلب بنجاح.');
    }

    public function supervisorReject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->ensureRole($request, 'supervisor');
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $reason = trim($validated['reason']);

        $application = Application::findOrFail($id);
        $application->supervisor_status = 'rejected';
        $application->final_status = 'rejected';
        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Supervisor Rejected Your Application',
            description: 'تم رفض طلبك من المشرف.',
            type: 'danger',
            meta: ['category' => 'application', 'reason' => $reason]
        );

        return $this->actionResponse($request, 'تم رفض الطلب بنجاح.');
    }

    public function companyCompleteTraining(Request $request, int $id): RedirectResponse
    {
        $this->ensureRole($request, 'company');

        $application = Application::with(['opportunity'])->findOrFail($id);
        abort_unless((int) optional($application->opportunity)->company_user_id === (int) $request->user()->id, 403);
        $this->assertCanCompleteTraining($application);

        $validated = $request->validate([
            'company_final_score' => ['required', 'integer', 'min:0', 'max:100'],
            'company_final_note' => ['required', 'string', 'max:2000'],
        ]);

        $application->company_final_score = $validated['company_final_score'];
        $application->company_final_note = trim($validated['company_final_note']);

        if ($application->company_final_score !== null && $application->supervisor_final_score !== null) {
            $application->final_score = (int) round(($application->company_final_score + $application->supervisor_final_score) / 2);
            $application->training_completed_at = now();
        }

        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Company Final Evaluation Submitted',
            description: 'تم إرسال التقييم النهائي من الشركة.',
            type: 'info',
            meta: ['category' => 'evaluation']
        );

        return back()->with('success', 'تم حفظ تقييم الشركة النهائي بنجاح.');
    }

    public function supervisorCompleteTraining(Request $request, int $id): RedirectResponse
    {
        $this->ensureRole($request, 'supervisor');

        $application = Application::with(['student', 'opportunity'])->findOrFail($id);
        abort_unless(optional($application->student)->supervisor_code === $request->user()->supervisor_code, 403);
        $this->assertCanCompleteTraining($application);

        $validated = $request->validate([
            'supervisor_final_score' => ['required', 'integer', 'min:0', 'max:100'],
            'supervisor_final_note' => ['required', 'string', 'max:2000'],
        ]);

        $application->supervisor_final_score = $validated['supervisor_final_score'];
        $application->supervisor_final_note = trim($validated['supervisor_final_note']);

        if ($application->company_final_score !== null && $application->supervisor_final_score !== null) {
            $application->final_score = (int) round(($application->company_final_score + $application->supervisor_final_score) / 2);
            $application->training_completed_at = now();
        }

        $application->save();

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'Supervisor Final Evaluation Submitted',
            description: 'تم إرسال التقييم النهائي من المشرف.',
            type: 'info',
            meta: ['category' => 'evaluation']
        );

        return back()->with('success', 'تم حفظ تقييم المشرف النهائي بنجاح.');
    }

    public function myApplications(Request $request): RedirectResponse
    {
        $this->ensureRole($request, 'student');
        return redirect()->route('student.applications.index');
    }

    public function companyApplications(Request $request): RedirectResponse
    {
        $this->ensureRole($request, 'company');
        return redirect()->route('company.applicants.index');
    }

    public function supervisorApplications(Request $request): RedirectResponse
    {
        $this->ensureRole($request, 'supervisor');
        return redirect()->route('supervisor.applications.index');
    }

    public function supervisorStudents(Request $request): RedirectResponse
    {
        $this->ensureRole($request, 'supervisor');
        return redirect()->route('supervisor.students.index');
    }

    public function downloadCv(Request $request, Application $application): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $canAccess = match ((string) $user->role) {
            'student' => (int) $application->student_id === (int) $user->id,
            'company' => (int) optional($application->opportunity)->company_user_id === (int) $user->id,
            'supervisor' => optional($application->student)->supervisor_code === $user->supervisor_code,
            'admin' => true,
            default => false,
        };

        abort_unless($canAccess, 403);
        abort_unless($application->cv, 404);
        abort_unless(Storage::disk('public')->exists($application->cv), 404);

        $filePath = Storage::disk('public')->path($application->cv);
        $downloadName = 'cv-' . $application->id . '.' . pathinfo((string) $application->cv, PATHINFO_EXTENSION);

        if ($request->boolean('download')) {
            return response()->download($filePath, $downloadName);
        }

        return response()->file($filePath, [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }
}
