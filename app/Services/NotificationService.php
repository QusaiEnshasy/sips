<?php

namespace App\Services;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function notifyUser(
        int $userId,
        string $title,
        string $description,
        string $type = 'info',
        array $meta = []
    ): void {
        try {
            if (! Schema::hasTable('user_notifications')) {
                return;
            }

            UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable) {
            // Notification must never break the main flow.
        }
    }

    public function notifyMany(
        array $userIds,
        string $title,
        string $description,
        string $type = 'info',
        array $meta = []
    ): void {
        $unique = collect($userIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        foreach ($unique as $id) {
            $this->notifyUser($id, $title, $description, $type, $meta);
        }
    }
}

