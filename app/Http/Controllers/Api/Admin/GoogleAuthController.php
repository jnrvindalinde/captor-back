<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Admin-side Google OAuth flow.
 *
 * - GET /api/admin/google/connect   (auth) -> { authUrl }
 * - GET /api/admin/google/callback  (no auth) -> redirects to FE settings page
 * - POST /api/admin/google/disconnect (auth)
 * - GET /api/admin/google/status    (auth) -> { connected, email }
 *
 * The callback runs without Sanctum because Google itself hits it.  We carry
 * the user identity in a short-lived signed `state` payload.
 */
class GoogleAuthController extends Controller
{
    public function __construct(private GoogleOAuthService $oauth) {}

    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();

        $state = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'nonce'   => bin2hex(random_bytes(8)),
            'ts'      => now()->timestamp,
        ]));

        return response()->json([
            'auth_url' => $this->oauth->getAuthUrl($state),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $feBase = rtrim(config('services.booking.public_base_url', 'http://localhost:3000'), '/');
        $returnTo = $feBase . '/admin/settings/google';

        if ($request->filled('error')) {
            return redirect($returnTo . '?status=error&reason=' . urlencode($request->string('error')));
        }

        $code = $request->string('code')->toString();
        $stateRaw = $request->string('state')->toString();

        try {
            $state = json_decode(Crypt::decryptString($stateRaw), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return redirect($returnTo . '?status=error&reason=bad_state');
        }

        // 10 minute state TTL.
        if (! isset($state['ts']) || abs(now()->timestamp - (int) $state['ts']) > 600) {
            return redirect($returnTo . '?status=error&reason=state_expired');
        }

        $user = User::find($state['user_id'] ?? null);
        if (! $user) {
            return redirect($returnTo . '?status=error&reason=user_not_found');
        }

        try {
            $this->oauth->handleCallback($user, $code);
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect($returnTo . '?status=error&reason=' . urlencode($e->getMessage()));
        }

        return redirect($returnTo . '?status=connected');
    }

    public function disconnect(Request $request): JsonResponse
    {
        $this->oauth->disconnect($request->user());
        return response()->json(['connected' => false]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'connected' => $user->hasGoogleConnected(),
            'email'     => $user->google_email,
            'expires_at' => $user->google_token_expires_at,
        ]);
    }
}
