<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TrelloService
{
    protected string $key;
    protected string $token;
    protected string $baseUrl = 'https://api.trello.com/1';

    public function __construct()
    {
        $this->key = (string) config('services.trello.key');
        $this->token = (string) config('services.trello.token');
    }

    private function params(array $extra = []): array
    {
        return array_merge([
            'key' => $this->key,
            'token' => $this->token,
        ], $extra);
    }

    public function isConfigured(): bool
    {
        return $this->key !== '' && $this->token !== '';
    }

    public function getBoards(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Http::get("{$this->baseUrl}/members/me/boards", $this->params())->json();
    }

    public function getLists(string $boardId): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Http::get("{$this->baseUrl}/boards/{$boardId}/lists", $this->params())->json();
    }

    public function createCard(string $listId, string $name, string $desc = ''): array
    {
        if (! $this->isConfigured() || $listId === '') {
            return [];
        }

        return Http::post("{$this->baseUrl}/cards", $this->params([
            'idList' => $listId,
            'name' => $name,
            'desc' => $desc,
        ]))->json();
    }

    public function getCards(string $listId): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Http::get("{$this->baseUrl}/lists/{$listId}/cards", $this->params())->json();
    }

    public function updateCard(string $cardId, array $data): array
    {
        if (! $this->isConfigured() || $cardId === '') {
            return [];
        }

        return Http::put("{$this->baseUrl}/cards/{$cardId}", $this->params($data))->json();
    }

    public function moveCard(string $cardId, string $listId): array
    {
        return $this->updateCard($cardId, ['idList' => $listId]);
    }

    public function deleteCard(string $cardId): array
    {
        if (! $this->isConfigured() || $cardId === '') {
            return [];
        }

        return Http::delete("{$this->baseUrl}/cards/{$cardId}", $this->params())->json();
    }
}
