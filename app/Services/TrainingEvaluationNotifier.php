<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;

class TrainingEvaluationNotifier
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function notifyIfTrainingEnded(Application $application): void
    {
        $application->loadMissing(['student', 'opportunity']);

        if ($application->evaluation_request_notified_at || $application->training_completed_at) {
            return;
        }

        if ($application->final_status !== 'approved' || ! $application->approved_at) {
            return;
        }

        $durationMonths = (int) ($application->opportunity?->duration ?? 0);
        if ($durationMonths <= 0) {
            return;
        }

        $endDate = $application->approved_at->copy()->addMonths($durationMonths)->startOfDay();
        if (now()->startOfDay()->lt($endDate)) {
            return;
        }

        $recipients = [];

        $companyId = (int) ($application->opportunity?->company_user_id ?? 0);
        if ($companyId > 0) {
            $recipients[] = $companyId;
        }

        $supervisor = $application->student?->supervisor_code
            ? User::query()
                ->where('role', 'supervisor')
                ->where('supervisor_code', $application->student->supervisor_code)
                ->first()
            : null;

        if ($supervisor) {
            $recipients[] = (int) $supervisor->id;
        }

        if (empty($recipients)) {
            return;
        }

        $studentName = $application->student?->name ?: 'الطالب';
        $programTitle = $application->opportunity?->title ?: 'برنامج التدريب';

        $this->notifications->notifyMany(
            userIds: $recipients,
            title: 'انتهت مدة تدريب طالب',
            description: 'انتهت مدة تدريب ' . $studentName . ' في ' . $programTitle . '. يرجى إدخال التقييم النهائي.',
            type: 'warning',
            meta: [
                'category' => 'evaluation',
                'application_id' => $application->id,
                'student_id' => $application->student_id,
                'program_title' => $programTitle,
            ]
        );

        $application->forceFill([
            'evaluation_request_notified_at' => now(),
        ])->save();
    }
}
