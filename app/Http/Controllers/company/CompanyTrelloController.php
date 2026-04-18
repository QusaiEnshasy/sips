<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\InternshipOpportunity;
use App\Models\TrelloIntegration;
use App\Models\TrelloInternshipLink;
use App\Services\TrelloService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyTrelloController extends Controller
{
    public function __construct(private readonly TrelloService $trello)
    {
    }

    private function companyUserId(): int
    {
        abort_unless(Auth::check() && Auth::user()->role === 'company', 403);

        return (int) Auth::id();
    }

    public function settings(Request $request): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_trello' => (bool) $integration,
                'board_id' => $integration?->trello_board_id,
                'board_name' => $integration?->trello_board_name,
                'member_id' => $integration?->trello_member_id,
                'is_active' => (bool) $integration?->is_active,
                'last_synced_at' => optional($integration?->last_synced_at)->toISOString(),
            ],
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $companyId = $this->companyUserId();
        $validated = $request->validate([
            'trello_api_key' => ['nullable', 'string', 'max:255'],
            'trello_token' => ['required', 'string', 'max:255'],
        ]);

        $integration = TrelloIntegration::query()->updateOrCreate(
            ['company_user_id' => $companyId],
            [
                'trello_api_key' => $validated['trello_api_key'] ?? null,
                'trello_token' => trim($validated['trello_token']),
                'is_active' => true,
            ]
        );

        $profile = $this->trello->getMemberProfile($integration);
        $companyEmail = strtolower((string) (Auth::user()?->email ?? ''));
        $trelloEmail = strtolower((string) ($profile['email'] ?? ''));

        if ($companyEmail !== '' && $trelloEmail !== '' && $companyEmail !== $trelloEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trello account email must match the company account email.',
            ], 422);
        }

        if (! empty($profile['id'])) {
            $integration->trello_member_id = (string) $profile['id'];
            $integration->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trello settings saved successfully.',
        ]);
    }

    public function testConnection(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();
        abort_unless($this->trello->isConfigured($integration), 422, 'Trello credentials are missing.');

        $profile = $this->trello->getMemberProfile($integration);
        abort_if(empty($profile['id']), 422, 'Failed to verify Trello credentials.');

        return response()->json([
            'status' => 'success',
            'data' => [
                'member_id' => $profile['id'],
                'username' => $profile['username'] ?? null,
                'full_name' => $profile['fullName'] ?? null,
            ],
        ]);
    }

    public function boards(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $this->trello->getBoards($integration),
        ]);
    }

    public function lists(Request $request, string $boardId): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $this->trello->getLists($boardId, $integration),
        ]);
    }

    public function integrations(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()
            ->with('internshipLinks.opportunity')
            ->where('company_user_id', $companyId)
            ->first();

        if (! $integration) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $payload = $integration->internshipLinks->map(function (TrelloInternshipLink $link) use ($integration) {
            return [
                'id' => $link->id,
                'internship_id' => $link->opportunity_id,
                'internship_title' => $link->opportunity?->title,
                'board_id' => $integration->trello_board_id,
                'board_name' => $integration->trello_board_name,
                'list_id' => $link->trello_list_id,
                'list_name' => $link->trello_list_name,
                'last_sync' => optional($link->last_synced_at)->toISOString(),
                'sync_status' => $link->sync_status,
            ];
        })->values();

        return response()->json(['status' => 'success', 'data' => $payload]);
    }

    public function connectBoard(Request $request, int $internshipId): JsonResponse
    {
        $companyId = $this->companyUserId();
        $validated = $request->validate([
            'board_id' => ['required', 'string', 'max:255'],
            'board_name' => ['nullable', 'string', 'max:255'],
            'list_id' => ['required', 'string', 'max:255'],
            'list_name' => ['nullable', 'string', 'max:255'],
        ]);

        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();
        $opportunity = InternshipOpportunity::query()
            ->where('company_user_id', $companyId)
            ->findOrFail($internshipId);

        $integration->update([
            'trello_board_id' => $validated['board_id'],
            'trello_board_name' => $validated['board_name'] ?? $integration->trello_board_name,
            'is_active' => true,
        ]);

        TrelloInternshipLink::query()->updateOrCreate(
            [
                'trello_integration_id' => $integration->id,
                'opportunity_id' => $opportunity->id,
            ],
            [
                'trello_list_id' => $validated['list_id'],
                'trello_list_name' => $validated['list_name'] ?? null,
                'sync_status' => 'ناجح',
            ]
        );

        return response()->json(['status' => 'success', 'message' => 'Board linked successfully.']);
    }

    public function syncInternship(Request $request, int $internshipId): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        $link = TrelloInternshipLink::query()
            ->where('trello_integration_id', $integration->id)
            ->where('opportunity_id', $internshipId)
            ->firstOrFail();

        $link->update(['sync_status' => 'قيد_المزامنة']);

        try {
            $result = $this->trello->syncOpportunityCards($link);
        } catch (\Throwable $e) {
            $link->update(['sync_status' => 'فشل']);
            throw $e;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trello sync completed.',
            'data' => $result,
        ]);
    }

    public function disconnect(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->first();

        if ($integration) {
            $integration->internshipLinks()->delete();
            $integration->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Trello disconnected.']);
    }
}
