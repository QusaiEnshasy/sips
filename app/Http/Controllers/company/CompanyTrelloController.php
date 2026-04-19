<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\InternshipOpportunity;
use App\Models\Task;
use App\Models\TrelloIntegration;
use App\Models\TrelloInternshipLink;
use App\Models\TrelloSyncLog;
use App\Services\TrelloService;
use App\Services\TrelloTaskSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyTrelloController extends Controller
{
    public function __construct(
        private readonly TrelloService $trello,
        private readonly TrelloTaskSyncService $syncService
    )
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
                'integration_id' => $integration?->id,
                'board_id' => $integration?->trello_board_id,
                'board_name' => $integration?->trello_board_name,
                'member_id' => $integration?->trello_member_id,
                'is_active' => (bool) $integration?->is_active,
                'last_synced_at' => optional($integration?->last_synced_at)->toISOString(),
            ],
        ]);
    }

    public function authorizeUrl(Request $request): JsonResponse
    {
        $data = $this->buildOAuthAuthorizePayload($request);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $data = $this->buildOAuthAuthorizePayload($request);

        return redirect()->away($data['authorize_url']);
    }

    private function buildOAuthAuthorizePayload(Request $request): array
    {
        $this->companyUserId();

        $apiKey = (string) config('services.trello.key');
        $apiSecret = (string) config('services.trello.secret');
        abort_if($apiKey === '' || $apiSecret === '', 422, 'Trello API key/secret are missing from the system settings.');

        $callbackUrl = route('company.trello.oauth.finalize');
        $requestToken = $this->requestOAuthToken($callbackUrl);

        $request->session()->put("trello_oauth_secret.{$requestToken['oauth_token']}", $requestToken['oauth_token_secret']);

        $authorizeUrl = 'https://trello.com/1/OAuthAuthorizeToken?' . http_build_query([
            'oauth_token' => $requestToken['oauth_token'],
            'name' => 'TrainEd Company Trello Integration',
            'scope' => 'read,write,account',
            'expiration' => 'never',
        ]);

        return [
            'authorize_url' => $authorizeUrl,
            'return_url' => $callbackUrl,
            'oauth_token' => $requestToken['oauth_token'],
        ];
    }

    public function completePinAuthorization(Request $request): JsonResponse
    {
        $companyId = $this->companyUserId();
        $validated = $request->validate([
            'oauth_verifier' => ['required', 'string', 'max:255'],
        ]);

        $oauthToken = (string) $request->session()->pull('trello_oauth_pin_token', '');
        $tokenSecret = $oauthToken !== ''
            ? (string) $request->session()->pull("trello_oauth_secret.{$oauthToken}", '')
            : '';

        abort_if($oauthToken === '' || $tokenSecret === '', 422, 'Trello authorization session expired. Start the connection again.');

        $accessToken = $this->exchangeOAuthToken($oauthToken, $tokenSecret, trim($validated['oauth_verifier']));
        $temporaryIntegration = new TrelloIntegration([
            'company_user_id' => $companyId,
            'trello_token' => $accessToken['oauth_token'],
        ]);

        $profile = $this->trello->getMemberProfile($temporaryIntegration);
        abort_if(empty($profile['id']), 422, 'Failed to verify Trello authorization.');

        $companyEmail = strtolower((string) (Auth::user()?->email ?? ''));
        $trelloEmail = strtolower((string) ($profile['email'] ?? ''));

        if ($companyEmail !== '' && $trelloEmail !== '' && $companyEmail !== $trelloEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trello account email must match the company account email.',
            ], 422);
        }

        $integration = TrelloIntegration::query()->updateOrCreate(
            ['company_user_id' => $companyId],
            [
                'trello_api_key' => null,
                'trello_token' => $accessToken['oauth_token'],
                'trello_member_id' => (string) $profile['id'],
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Trello connected successfully.',
            'data' => [
                'integration_id' => $integration->id,
                'member_id' => $integration->trello_member_id,
            ],
        ]);
    }

    public function finalizeOAuth(Request $request)
    {
        $companyId = $this->companyUserId();
        $oauthToken = (string) $request->query('oauth_token');
        $oauthVerifier = (string) $request->query('oauth_verifier');
        $oauthDenied = (string) $request->query('oauth_problem');

        Log::info('Trello OAuth callback received.', [
            'company_user_id' => $companyId,
            'has_oauth_token' => $oauthToken !== '',
            'has_oauth_verifier' => $oauthVerifier !== '',
            'oauth_problem' => $oauthDenied ?: null,
        ]);

        if ($oauthDenied !== '') {
            return redirect('/company/trello-settings?trello_error=' . urlencode($oauthDenied));
        }

        if ($oauthToken === '' || $oauthVerifier === '') {
            return redirect('/company/trello-settings?trello_error=missing_oauth_data');
        }

        $tokenSecret = (string) $request->session()->pull("trello_oauth_secret.{$oauthToken}", '');
        if ($tokenSecret === '') {
            Log::warning('Trello OAuth session secret missing.', [
                'company_user_id' => $companyId,
            ]);

            return redirect('/company/trello-settings?trello_error=session_expired');
        }

        try {
            $accessToken = $this->exchangeOAuthToken($oauthToken, $tokenSecret, $oauthVerifier);
            $temporaryIntegration = new TrelloIntegration([
                'company_user_id' => $companyId,
                'trello_token' => $accessToken['oauth_token'],
            ]);

            $profile = $this->trello->getMemberProfile($temporaryIntegration);
            if (empty($profile['id'])) {
                return redirect('/company/trello-settings?trello_error=profile_failed');
            }

            $companyEmail = strtolower((string) (Auth::user()?->email ?? ''));
            $trelloEmail = strtolower((string) ($profile['email'] ?? ''));

            if ($companyEmail !== '' && $trelloEmail !== '' && $companyEmail !== $trelloEmail) {
                Log::warning('Trello OAuth email mismatch.', [
                    'company_user_id' => $companyId,
                    'company_email' => $companyEmail,
                    'trello_email' => $trelloEmail,
                ]);

                return redirect('/company/trello-settings?trello_error=email_mismatch');
            }

            TrelloIntegration::query()->updateOrCreate(
                ['company_user_id' => $companyId],
                [
                    'trello_api_key' => null,
                    'trello_token' => $accessToken['oauth_token'],
                    'trello_member_id' => (string) $profile['id'],
                    'is_active' => true,
                ]
            );

            return redirect('/company/trello-settings?trello_connected=1');
        } catch (\Throwable $e) {
            Log::error('Trello OAuth finalize failed.', [
                'company_user_id' => $companyId,
                'message' => $e->getMessage(),
            ]);

            return redirect('/company/trello-settings?trello_error=oauth_failed');
        }
    }

    public function completeAuthorization(Request $request): JsonResponse
    {
        $companyId = $this->companyUserId();
        $validated = $request->validate([
            'trello_token' => ['required', 'string', 'max:512'],
        ]);

        $token = trim($validated['trello_token']);
        $temporaryIntegration = new TrelloIntegration([
            'company_user_id' => $companyId,
            'trello_token' => $token,
        ]);

        $profile = $this->trello->getMemberProfile($temporaryIntegration);
        abort_if(empty($profile['id']), 422, 'Failed to verify Trello authorization.');

        $companyEmail = strtolower((string) (Auth::user()?->email ?? ''));
        $trelloEmail = strtolower((string) ($profile['email'] ?? ''));

        if ($companyEmail !== '' && $trelloEmail !== '' && $companyEmail !== $trelloEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trello account email must match the company account email.',
            ], 422);
        }

        $integration = TrelloIntegration::query()->updateOrCreate(
            ['company_user_id' => $companyId],
            [
                'trello_api_key' => null,
                'trello_token' => $token,
                'trello_member_id' => (string) $profile['id'],
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Trello connected successfully.',
            'data' => [
                'integration_id' => $integration->id,
                'member_id' => $integration->trello_member_id,
                'username' => $profile['username'] ?? null,
                'full_name' => $profile['fullName'] ?? null,
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

        $apiKey = trim((string) ($validated['trello_api_key'] ?? ''));
        if ($apiKey === '' || str_contains($apiKey, '@')) {
            // The shared app API key lives in .env; companies only need their own token.
            $apiKey = null;
        }

        $integration = TrelloIntegration::query()->updateOrCreate(
            ['company_user_id' => $companyId],
            [
                'trello_api_key' => $apiKey,
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
            'data' => [
                'member_id' => $integration->trello_member_id,
            ],
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

        $boardUrl = null;
        if ($integration->trello_board_id) {
            try {
                $board = $this->trello->getBoard((string) $integration->trello_board_id, $integration);
                $boardUrl = (string) ($board['url'] ?? '');
            } catch (\Throwable) {
                $boardUrl = '';
            }

            if ($boardUrl === '') {
                $boardUrl = 'https://trello.com/b/' . $integration->trello_board_id;
            }
        }

        $payload = $integration->internshipLinks->map(function (TrelloInternshipLink $link) use ($integration, $boardUrl) {
            return [
                'id' => $link->id,
                'internship_id' => $link->opportunity_id,
                'internship_title' => $link->opportunity?->title,
                'board_id' => $integration->trello_board_id,
                'board_name' => $integration->trello_board_name,
                'board_url' => $boardUrl,
                'list_id' => $link->trello_list_id,
                'list_name' => $link->trello_list_name,
                'last_sync' => optional($link->last_synced_at)->toISOString(),
                'sync_status' => $link->sync_status,
                'sync_url' => route('company.trello.sync', ['internshipId' => $link->opportunity_id]),
                'latest_log' => $link->syncLogs()->latest()->first()?->only([
                    'id',
                    'trigger',
                    'status',
                    'created_count',
                    'updated_count',
                    'skipped_count',
                    'message',
                    'started_at',
                    'finished_at',
                ]),
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
                'sync_status' => 'idle',
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

        $link->update(['sync_status' => 'syncing']);

        try {
            $result = $this->syncService->syncInternshipLink($link);
        } catch (\Throwable $e) {
            $link->update(['sync_status' => 'failed']);
            $this->markLatestStartedLogAsFailed($link, $e->getMessage());
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
            $integration->forceFill([
                'trello_board_id' => null,
                'trello_board_name' => null,
                'trello_member_id' => null,
                'webhook_id' => null,
                'is_active' => false,
            ])->save();
            $integration->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Trello disconnected.']);
    }

    public function unlinkInternship(int $internshipId): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        $link = TrelloInternshipLink::query()
            ->where('trello_integration_id', $integration->id)
            ->where('opportunity_id', $internshipId)
            ->firstOrFail();

        Task::query()
            ->where('trello_integration_id', $integration->id)
            ->whereIn('application_id', function ($query) use ($internshipId) {
                $query->select('id')
                    ->from('applications')
                    ->where('opportunity_id', $internshipId);
            })
            ->where('source', 'trello')
            ->update([
                'trello_integration_id' => null,
                'trello_list_id' => null,
                'source' => 'manual',
                'trello_last_synced_at' => now(),
            ]);

        $link->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Internship link disconnected successfully.',
        ]);
    }

    public function syncLogs(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->first();

        if (! $integration) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $logs = TrelloSyncLog::query()
            ->with('internshipLink.opportunity:id,title')
            ->where('trello_integration_id', $integration->id)
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (TrelloSyncLog $log) => [
                'id' => $log->id,
                'program' => $log->internshipLink?->opportunity?->title,
                'trigger' => $log->trigger,
                'status' => $log->status,
                'created' => $log->created_count,
                'updated' => $log->updated_count,
                'skipped' => $log->skipped_count,
                'message' => $log->message,
                'started_at' => optional($log->started_at)->toISOString(),
                'finished_at' => optional($log->finished_at)->toISOString(),
            ])
            ->values();

        return response()->json(['status' => 'success', 'data' => $logs]);
    }

    public function enableWebhook(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        abort_unless($integration->trello_board_id, 422, 'Select a Trello board before enabling webhook sync.');

        if ($integration->webhook_id) {
            return response()->json([
                'status' => 'success',
                'message' => 'Webhook is already enabled.',
                'data' => ['webhook_id' => $integration->webhook_id],
            ]);
        }

        $callbackUrl = route('trello.webhook', ['integration' => $integration->id]);

        if (Str::contains($callbackUrl, ['127.0.0.1', 'localhost'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook needs a public APP_URL. Keep using manual Sync on localhost, or expose the app with a public URL.',
                'data' => ['callback_url' => $callbackUrl],
            ], 422);
        }

        $webhook = $this->trello->createWebhook($callbackUrl, (string) $integration->trello_board_id, $integration);

        if (empty($webhook['id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trello did not return a webhook id.',
            ], 422);
        }

        $integration->update(['webhook_id' => (string) $webhook['id']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook enabled successfully.',
            'data' => ['webhook_id' => $integration->webhook_id],
        ]);
    }

    public function disableWebhook(): JsonResponse
    {
        $companyId = $this->companyUserId();
        $integration = TrelloIntegration::query()->where('company_user_id', $companyId)->firstOrFail();

        if ($integration->webhook_id) {
            $this->trello->deleteWebhook((string) $integration->webhook_id, $integration);
            $integration->update(['webhook_id' => null]);
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook disabled successfully.']);
    }

    public function webhookHead(TrelloIntegration $integration)
    {
        return response('', 200);
    }

    public function webhook(Request $request, TrelloIntegration $integration): JsonResponse
    {
        $integration->load('internshipLinks');

        foreach ($integration->internshipLinks as $link) {
            try {
                $this->syncService->syncInternshipLink($link, 'webhook');
            } catch (\Throwable $e) {
                $link->update(['sync_status' => 'failed']);
                $this->markLatestStartedLogAsFailed($link, $e->getMessage());
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function markLatestStartedLogAsFailed(TrelloInternshipLink $link, string $message): void
    {
        TrelloSyncLog::query()
            ->where('trello_internship_link_id', $link->id)
            ->where('status', 'started')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed',
                'message' => $message,
                'finished_at' => now(),
            ]);
    }

    private function requestOAuthToken(string $callbackUrl): array
    {
        $response = $this->signedOAuthRequest(
            'POST',
            'https://trello.com/1/OAuthGetRequestToken',
            ['oauth_callback' => $callbackUrl]
        );

        abort_if(empty($response['oauth_token']) || empty($response['oauth_token_secret']), 422, 'Trello did not return a request token.');

        return $response;
    }

    private function exchangeOAuthToken(string $oauthToken, string $tokenSecret, string $oauthVerifier): array
    {
        $response = $this->signedOAuthRequest(
            'POST',
            'https://trello.com/1/OAuthGetAccessToken',
            [
                'oauth_token' => $oauthToken,
                'oauth_verifier' => $oauthVerifier,
            ],
            $tokenSecret
        );

        abort_if(empty($response['oauth_token']), 422, 'Trello did not return an access token.');

        return $response;
    }

    private function signedOAuthRequest(string $method, string $url, array $params = [], string $tokenSecret = ''): array
    {
        $oauthParams = array_merge([
            'oauth_consumer_key' => (string) config('services.trello.key'),
            'oauth_nonce' => Str::random(32),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_version' => '1.0',
        ], $params);

        $signatureParams = $oauthParams;
        ksort($signatureParams);

        $baseParts = [
            strtoupper($method),
            $url,
            http_build_query($signatureParams, '', '&', PHP_QUERY_RFC3986),
        ];

        $baseString = implode('&', array_map('rawurlencode', $baseParts));
        $signingKey = rawurlencode((string) config('services.trello.secret')) . '&' . rawurlencode($tokenSecret);
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $response = Http::asForm()
            ->timeout(20)
            ->withHeaders([
                'Authorization' => 'OAuth ' . collect($oauthParams)
                    ->map(fn ($value, $key) => rawurlencode($key) . '="' . rawurlencode((string) $value) . '"')
                    ->implode(', '),
            ])
            ->send(strtoupper($method), $url);

        $response->throw();

        parse_str($response->body(), $parsed);

        return $parsed;
    }
}
