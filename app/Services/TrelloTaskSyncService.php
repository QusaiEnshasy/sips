<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Task;
use App\Models\TrelloInternshipLink;
use App\Models\TrelloSyncLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrelloTaskSyncService
{
    public function __construct(private readonly TrelloService $trello)
    {
    }

    public function syncInternshipLink(TrelloInternshipLink $link, string $trigger = 'manual'): array
    {
        $link->loadMissing(['integration.company', 'opportunity.companyUser']);

        $integration = $link->integration;
        if (! $integration || ! $this->trello->isConfigured($integration)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $log = TrelloSyncLog::query()->create([
            'trello_integration_id' => $integration->id,
            'trello_internship_link_id' => $link->id,
            'opportunity_id' => $link->opportunity_id,
            'trigger' => $trigger,
            'status' => 'started',
            'started_at' => now(),
        ]);

        $boardId = (string) $integration->trello_board_id;
        if ($boardId === '') {
            return $this->finishLog($log, ['created' => 0, 'updated' => 0, 'skipped' => 0], 'failed', 'No Trello board is linked.');
        }

        $applications = Application::with(['student', 'opportunity'])
            ->where('opportunity_id', $link->opportunity_id)
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get();

        if ($applications->isEmpty()) {
            return $this->finishLog($log, ['created' => 0, 'updated' => 0, 'skipped' => 0], 'success', 'No active approved students for this program.');
        }

        $existingCardIds = Task::query()
            ->whereIn('application_id', $applications->pluck('id'))
            ->where('source', 'trello')
            ->whereNotNull('trello_card_id')
            ->pluck('trello_card_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $lists = collect($this->trello->getLists($boardId, $integration))
            ->keyBy(fn (array $list) => (string) ($list['id'] ?? ''));

        $cards = collect($this->trello->getBoardCards($boardId, $integration))
            ->filter(function (array $card) use ($link, $existingCardIds) {
                $cardId = (string) ($card['id'] ?? '');
                $listId = (string) ($card['idList'] ?? '');

                if ($cardId === '') {
                    return false;
                }

                return $listId === (string) $link->trello_list_id || in_array($cardId, $existingCardIds, true);
            })
            ->values();

        if ($cards->isEmpty()) {
            $link->update([
                'last_synced_at' => now(),
                'sync_status' => 'idle',
            ]);
            $integration->update(['last_synced_at' => now()]);

            return $this->finishLog($log, ['created' => 0, 'updated' => 0, 'skipped' => 0], 'success', 'No matching Trello cards found.');
        }

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $assignmentDetails = [];

        DB::transaction(function () use ($applications, $cards, $lists, $integration, $link, &$counts, &$assignmentDetails) {
            foreach ($cards as $card) {
                $targetApplications = $this->resolveTargetApplications($card, $applications);

                if ($targetApplications->isEmpty()) {
                    $counts['skipped']++;
                    $assignmentDetails[] = [
                        'card' => $card['name'] ?? $card['id'] ?? null,
                        'reason' => 'missing or invalid student marker',
                    ];
                    continue;
                }

                foreach ($targetApplications as $application) {
                    $cardId = (string) ($card['id'] ?? '');
                    $cardName = trim((string) ($card['name'] ?? ''));
                    if ($cardId === '' || $cardName === '') {
                        $counts['skipped']++;
                        continue;
                    }

                    $listId = (string) ($card['idList'] ?? $link->trello_list_id);
                    $task = Task::query()->firstOrNew([
                        'application_id' => $application->id,
                        'trello_card_id' => $cardId,
                    ]);

                    $isNew = ! $task->exists;
                    $task->fill([
                        'company_user_id' => $integration->company_user_id,
                        'trello_integration_id' => $integration->id,
                        'created_by' => $task->created_by ?: $integration->company_user_id,
                        'trello_list_id' => $listId,
                        'source' => 'trello',
                        'title' => Str::limit($cardName, 255, ''),
                        'details' => (string) ($card['desc'] ?? ''),
                        'status' => $this->mapCardStatus($card, $listId, $link, $lists),
                        'assigned_user' => $application->student?->name,
                        'due_date' => ! empty($card['due']) ? date('Y-m-d', strtotime((string) $card['due'])) : $task->due_date,
                        'trello_last_synced_at' => now(),
                        'order' => $task->order ?: ((int) Task::query()
                            ->where('application_id', $application->id)
                            ->where('status', 'todo')
                            ->max('order') + 1),
                    ]);
                    $task->save();

                    if ($application->student_id) {
                        $task->assignedStudents()->syncWithoutDetaching([(int) $application->student_id]);
                    }

                    $assignmentDetails[] = [
                        'card' => $cardName,
                        'student_id' => $application->student_id,
                        'student_name' => $application->student?->name,
                        'student_email' => $application->student?->email,
                        'university_id' => $application->student?->university_id,
                    ];

                    $counts[$isNew ? 'created' : 'updated']++;
                }
            }

            $link->update([
                'last_synced_at' => now(),
                'sync_status' => 'success',
            ]);

            $integration->update(['last_synced_at' => now()]);
        });

        return $this->finishLog($log, $counts, 'success', 'Trello sync completed.', [
            'assignments' => $assignmentDetails,
        ]);
    }

    private function resolveTargetApplications(array $card, Collection $applications): Collection
    {
        $markers = $this->extractStudentMarkers($card);

        if ($markers->isEmpty()) {
            return collect();
        }

        return $applications->filter(function (Application $application) use ($markers) {
            $student = $application->student;
            if (! $student instanceof User) {
                return false;
            }

            $candidates = collect([
                $student->id,
                $student->email,
                $student->university_id,
                $student->name,
            ])->filter()
                ->map(fn ($value) => Str::lower(trim((string) $value)));

            return $markers->intersect($candidates)->isNotEmpty();
        })->values();
    }

    private function extractStudentMarkers(array $card): Collection
    {
        $text = Str::lower(trim(((string) ($card['name'] ?? '')) . "\n" . ((string) ($card['desc'] ?? ''))));
        if ($text === '') {
            return collect();
        }

        $markers = collect();

        preg_match_all('/(?:student|students|student_id|university_id|email)\s*[:=]\s*([^\n\r]+)/iu', $text, $matches);
        foreach ($matches[1] ?? [] as $rawValue) {
            collect(preg_split('/[,،;|\s]+/u', (string) $rawValue))
                ->map(fn ($value) => trim($value, " \t\n\r\0\x0B[](){}<>"))
                ->filter()
                ->each(fn ($value) => $markers->push($value));
        }

        preg_match_all('/[\w.\-+]+@[\w.\-]+\.[a-z]{2,}/iu', $text, $emailMatches);
        foreach ($emailMatches[0] ?? [] as $email) {
            $markers->push($email);
        }

        return $markers
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values();
    }

    private function finishLog(TrelloSyncLog $log, array $counts, string $status, ?string $message = null, array $details = []): array
    {
        $log->update([
            'status' => $status,
            'created_count' => (int) ($counts['created'] ?? 0),
            'updated_count' => (int) ($counts['updated'] ?? 0),
            'skipped_count' => (int) ($counts['skipped'] ?? 0),
            'message' => $message,
            'details' => $details ?: null,
            'finished_at' => now(),
        ]);

        return array_merge($counts, [
            'status' => $status,
            'message' => $message,
            'log_id' => $log->id,
        ]);
    }

    private function mapCardStatus(array $card, string $listId, TrelloInternshipLink $link, Collection $lists): string
    {
        if ((bool) ($card['closed'] ?? false) || (bool) ($card['dueComplete'] ?? false)) {
            return 'done';
        }

        if ($listId === (string) $link->trello_list_id) {
            return 'todo';
        }

        $listName = Str::lower((string) ($lists->get($listId)['name'] ?? ''));

        if (Str::contains($listName, ['done', 'complete', 'completed', 'finished', 'منجز', 'مكتمل', 'تم'])) {
            return 'done';
        }

        if (Str::contains($listName, ['progress', 'doing', 'working', 'review', 'قيد', 'تنفيذ', 'مراجعة'])) {
            return 'progress';
        }

        return 'todo';
    }
}
