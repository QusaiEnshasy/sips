<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Task;
use App\Models\TrelloIntegration;
use App\Models\TrelloInternshipLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TrelloService
{
    private string $baseUrl = 'https://api.trello.com/1';

    private function resolveCredentials(?TrelloIntegration $integration = null): array
    {
        if ($integration) {
            return [
                'key' => (string) ($integration->trello_api_key ?: config('services.trello.key')),
                'token' => (string) $integration->trello_token,
            ];
        }

        return [
            'key' => (string) config('services.trello.key'),
            'token' => (string) config('services.trello.token'),
        ];
    }

    private function params(array $credentials, array $extra = []): array
    {
        return array_merge([
            'key' => $credentials['key'] ?? '',
            'token' => $credentials['token'] ?? '',
        ], $extra);
    }

    public function isConfigured(?TrelloIntegration $integration = null): bool
    {
        $credentials = $this->resolveCredentials($integration);

        return ($credentials['key'] ?? '') !== '' && ($credentials['token'] ?? '') !== '';
    }

    public function getBoards(?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration)) {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::get("{$this->baseUrl}/members/me/boards", $this->params($credentials))->json() ?: [];
    }

    public function getLists(string $boardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $boardId === '') {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::get("{$this->baseUrl}/boards/{$boardId}/lists", $this->params($credentials))->json() ?: [];
    }

    public function createCard(string $listId, string $name, string $desc = '', ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $listId === '') {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::post("{$this->baseUrl}/cards", $this->params($credentials, [
            'idList' => $listId,
            'name' => $name,
            'desc' => $desc,
        ]))->json() ?: [];
    }

    public function getCards(string $listId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $listId === '') {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::get("{$this->baseUrl}/lists/{$listId}/cards", $this->params($credentials))->json() ?: [];
    }

    public function updateCard(string $cardId, array $data, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $cardId === '') {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::put("{$this->baseUrl}/cards/{$cardId}", $this->params($credentials, $data))->json() ?: [];
    }

    public function moveCard(string $cardId, string $listId, ?TrelloIntegration $integration = null): array
    {
        return $this->updateCard($cardId, ['idList' => $listId], $integration);
    }

    public function deleteCard(string $cardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $cardId === '') {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::delete("{$this->baseUrl}/cards/{$cardId}", $this->params($credentials))->json() ?: [];
    }

    public function getMemberProfile(?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration)) {
            return [];
        }

        $credentials = $this->resolveCredentials($integration);

        return Http::get("{$this->baseUrl}/members/me", $this->params($credentials, [
            'fields' => 'id,username,fullName,email',
        ]))->json() ?: [];
    }

    public function syncOpportunityCards(TrelloInternshipLink $link): array
    {
        $link->loadMissing(['integration.company', 'opportunity']);

        $integration = $link->integration;
        if (! $integration || ! $this->isConfigured($integration)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $cards = $this->getCards((string) $link->trello_list_id, $integration);
        $applications = Application::with('student')
            ->where('opportunity_id', $link->opportunity_id)
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get();

        if ($applications->isEmpty()) {
            return ['created' => 0, 'updated' => 0, 'skipped' => count($cards)];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            foreach ($cards as $card) {
                $cardId = (string) ($card['id'] ?? '');
                $cardName = trim((string) ($card['name'] ?? ''));
                if ($cardId === '' || $cardName === '') {
                    $skipped++;
                    continue;
                }

                $task = Task::query()->firstOrNew([
                    'application_id' => $application->id,
                    'trello_card_id' => $cardId,
                ]);

                $isNew = ! $task->exists;
                $task->fill([
                    'company_user_id' => $integration->company_user_id,
                    'trello_integration_id' => $integration->id,
                    'created_by' => $task->created_by ?: $integration->company_user_id,
                    'trello_list_id' => (string) ($card['idList'] ?? $link->trello_list_id),
                    'source' => 'trello',
                    'title' => Str::limit($cardName, 255, ''),
                    'details' => (string) ($card['desc'] ?? ''),
                    'status' => $this->mapTrelloListToStatus((string) ($card['idList'] ?? $link->trello_list_id), $link),
                    'label' => $task->label,
                    'assigned_user' => $application->student?->name,
                    'trello_last_synced_at' => now(),
                    'order' => $task->order ?: ((Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1),
                ]);
                $task->save();

                $task->assignedStudents()->syncWithoutDetaching([(int) $application->student_id]);

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        }

        $link->update([
            'last_synced_at' => now(),
            'sync_status' => 'ناجح',
        ]);
        $integration->update(['last_synced_at' => now()]);

        return compact('created', 'updated', 'skipped');
    }

    private function mapTrelloListToStatus(string $listId, TrelloInternshipLink $link): string
    {
        if ($listId === $link->trello_list_id) {
            return 'todo';
        }

        return 'todo';
    }
}
