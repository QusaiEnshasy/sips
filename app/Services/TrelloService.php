<?php

namespace App\Services;

use App\Models\TrelloIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

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

    private function client()
    {
        return Http::acceptJson()
            ->timeout(20)
            ->retry(2, 250, throw: false);
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

    /**
     * @throws RequestException
     */
    private function get(string $path, array $params = [], ?TrelloIntegration $integration = null): array
    {
        $credentials = $this->resolveCredentials($integration);

        return $this->client()
            ->get("{$this->baseUrl}{$path}", $this->params($credentials, $params))
            ->throw()
            ->json() ?: [];
    }

    /**
     * @throws RequestException
     */
    private function post(string $path, array $params = [], ?TrelloIntegration $integration = null): array
    {
        $credentials = $this->resolveCredentials($integration);

        return $this->client()
            ->post("{$this->baseUrl}{$path}", $this->params($credentials, $params))
            ->throw()
            ->json() ?: [];
    }

    /**
     * @throws RequestException
     */
    private function put(string $path, array $params = [], ?TrelloIntegration $integration = null): array
    {
        $credentials = $this->resolveCredentials($integration);

        return $this->client()
            ->put("{$this->baseUrl}{$path}", $this->params($credentials, $params))
            ->throw()
            ->json() ?: [];
    }

    /**
     * @throws RequestException
     */
    private function delete(string $path, array $params = [], ?TrelloIntegration $integration = null): array
    {
        $credentials = $this->resolveCredentials($integration);

        return $this->client()
            ->delete("{$this->baseUrl}{$path}", $this->params($credentials, $params))
            ->throw()
            ->json() ?: [];
    }

    public function getBoards(?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration)) {
            return [];
        }

        return $this->get('/members/me/boards', [
            'fields' => 'id,name,desc,prefs,url',
        ], $integration);
    }

    public function getLists(string $boardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $boardId === '') {
            return [];
        }

        return $this->get("/boards/{$boardId}/lists", [
            'fields' => 'id,name,closed,pos',
        ], $integration);
    }

    public function getBoard(string $boardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $boardId === '') {
            return [];
        }

        return $this->get("/boards/{$boardId}", [
            'fields' => 'id,name,desc,url',
        ], $integration);
    }

    public function getBoardCards(string $boardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $boardId === '') {
            return [];
        }

        return $this->get("/boards/{$boardId}/cards", [
            'fields' => 'id,name,desc,idList,due,dueComplete,closed,labels,shortUrl,dateLastActivity',
            'filter' => 'open',
        ], $integration);
    }

    public function getCard(string $cardId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $cardId === '') {
            return [];
        }

        return $this->get("/cards/{$cardId}", [
            'fields' => 'id,name,desc,idList,due,dueComplete,closed,labels,shortUrl,dateLastActivity',
        ], $integration);
    }

    public function createCard(string $listId, string $name, string $desc = '', ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $listId === '') {
            return [];
        }

        return $this->post('/cards', [
            'idList' => $listId,
            'name' => $name,
            'desc' => $desc,
        ], $integration);
    }

    public function getCards(string $listId, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $listId === '') {
            return [];
        }

        return $this->get("/lists/{$listId}/cards", [
            'fields' => 'id,name,desc,idList,due,dueComplete,closed,labels,shortUrl,dateLastActivity',
            'filter' => 'open',
        ], $integration);
    }

    public function updateCard(string $cardId, array $data, ?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration) || $cardId === '') {
            return [];
        }

        return $this->put("/cards/{$cardId}", $data, $integration);
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

        return $this->delete("/cards/{$cardId}", [], $integration);
    }

    public function getMemberProfile(?TrelloIntegration $integration = null): array
    {
        if (! $this->isConfigured($integration)) {
            return [];
        }

        return $this->get('/members/me', [
            'fields' => 'id,username,fullName,email',
        ], $integration);
    }
}
