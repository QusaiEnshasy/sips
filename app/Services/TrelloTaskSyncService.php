<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Task;
use App\Models\TrelloInternshipLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrelloTaskSyncService
{
    public function __construct(private readonly TrelloService $trello)
    {
    }

    public function syncInternshipLink(TrelloInternshipLink $link): array
    {
        $link->loadMissing(['integration.company', 'opportunity.companyUser']);

        $integration = $link->integration;
        if (! $integration || ! $this->trello->isConfigured($integration)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $boardId = (string) $integration->trello_board_id;
        if ($boardId === '') {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $applications = Application::with(['student', 'opportunity'])
            ->where('opportunity_id', $link->opportunity_id)
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get();

        if ($applications->isEmpty()) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
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

            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($applications, $cards, $lists, $integration, $link, &$counts) {
            foreach ($applications as $application) {
                foreach ($cards as $card) {
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

                    $counts[$isNew ? 'created' : 'updated']++;
                }
            }

            $link->update([
                'last_synced_at' => now(),
                'sync_status' => 'success',
            ]);

            $integration->update(['last_synced_at' => now()]);
        });

        return $counts;
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
