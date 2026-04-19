<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\JisrSubmission;
use App\Models\JisrTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JisrController extends Controller
{
    private function ensureRole(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'student', 403);
    }

    public function tasks(): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $tasks = JisrTask::query()
            ->orderBy('order_number')
            ->get()
            ->map(function (JisrTask $task) use ($user) {
                $submission = JisrSubmission::query()
                    ->where('jisr_task_id', $task->id)
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $task->id,
                    'order_number' => $task->order_number,
                    'title' => $task->title,
                    'description' => $task->description,
                    'instructions' => $task->instructions,
                    'type' => $task->type,
                    'url' => $task->url,
                    'max_score' => $task->max_score,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'score' => $submission->score,
                        'feedback' => $submission->feedback,
                        'content' => $submission->content,
                        'submitted_at' => optional($submission->submitted_at)->toISOString(),
                        'attachments' => collect($submission->attachments ?? [])->values()->map(function ($attachment) {
                            $path = (string) ($attachment['path'] ?? '');

                            return [
                                'name' => $attachment['name'] ?? basename($path),
                                'path' => $path,
                                'url' => $path !== '' ? Storage::disk('public')->url($path) : null,
                            ];
                        })->values(),
                    ] : null,
                ];
            })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks' => $tasks,
                'user_state' => $this->userStatePayload($user),
            ],
        ]);
    }

    public function submit(Request $request, int $taskId): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $task = JisrTask::query()->findOrFail($taskId);

        $request->validate([
            'submission' => ['nullable', 'string', 'required_without:attachments'],
            'attachments' => ['nullable'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $existingSubmission = JisrSubmission::query()
            ->where('jisr_task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();

        $attachmentPaths = collect($existingSubmission?->attachments ?? [])->values()->all();

        $uploadedFiles = collect($request->allFiles())
            ->flatten()
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values();

        if ($uploadedFiles->isNotEmpty()) {
            foreach ($uploadedFiles as $file) {
                $path = $file->store('jisr_attachments', 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            }
        }

        $submission = JisrSubmission::updateOrCreate(
            [
                'jisr_task_id' => $task->id,
                'user_id' => $user->id,
            ],
            [
                'content' => $request->input('submission'),
                'attachments' => $attachmentPaths,
                'status' => 'pending_review',
                'score' => null,
                'feedback' => 'تم استلام الحل وبانتظار تقييم المشرف.',
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الحل وبانتظار تقييم المشرف.',
            'data' => [
                'submission' => [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'feedback' => $submission->feedback,
                    'attachments' => collect($submission->attachments ?? [])->values()->map(function ($attachment) {
                        $path = (string) ($attachment['path'] ?? '');

                        return [
                            'name' => $attachment['name'] ?? basename($path),
                            'path' => $path,
                            'url' => $path !== '' ? Storage::disk('public')->url($path) : null,
                        ];
                    })->values(),
                ],
                'program_completed' => false,
                'next_path' => null,
                'user_state' => $this->userStatePayload($user->fresh()),
            ],
        ]);
    }

    public function complete(): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $acceptedCount = JisrSubmission::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->count();
        $totalTasks = JisrTask::query()->count();

        if ($acceptedCount < $totalTasks) {
            return response()->json([
                'status' => 'error',
                'message' => 'All Jisr tasks must be completed first.',
            ], 422);
        }

        $user->forceFill([
            'is_in_jisr' => false,
            'skill_test_required' => true,
            'skill_test_passed' => false,
            'jisr_completed_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'next_path' => '/student/skill-test',
                'user_state' => $this->userStatePayload($user->fresh()),
            ],
        ]);
    }

    private function userStatePayload($user): array
    {
        return [
            'skill_test_required' => (bool) $user->skill_test_required,
            'skill_test_passed' => (bool) $user->skill_test_passed,
            'is_in_jisr' => (bool) $user->is_in_jisr,
            'skill_test_completed_at' => optional($user->skill_test_completed_at)->toISOString(),
            'jisr_completed_at' => optional($user->jisr_completed_at)->toISOString(),
        ];
    }
}
