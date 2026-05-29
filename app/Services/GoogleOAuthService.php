<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Builds and refreshes Google API clients per-user.
 *
 * Persists: refresh_token (long-lived), access token expiry, and the email
 * address of the connected Google account so the UI can show which account
 * is wired up.
 */
class GoogleOAuthService
{
    /** Scopes we ask for at consent time. */
    public const SCOPES = [
        GoogleCalendar::CALENDAR_EVENTS,
        GoogleCalendar::CALENDAR_READONLY,
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    /** Build a base Google\Client using config('services.google'). */
    public function baseClient(): GoogleClient
    {
        $cfg = config('services.google');
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            throw new RuntimeException('Google OAuth client_id/client_secret not configured.');
        }

        $client = new GoogleClient();
        $client->setClientId($cfg['client_id']);
        $client->setClientSecret($cfg['client_secret']);
        $client->setRedirectUri($cfg['redirect_uri']);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force'); // force refresh_token to be returned
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes(self::SCOPES);

        return $client;
    }

    /** URL to send the admin to so they can grant access. */
    public function getAuthUrl(string $state): string
    {
        $client = $this->baseClient();
        $client->setState($state);

        return $client->createAuthUrl();
    }

    /** Exchange a callback code for tokens and persist them on the user. */
    public function handleCallback(User $user, string $code): User
    {
        $client = $this->baseClient();
        $tokens = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($tokens['error'])) {
            throw new RuntimeException('Google token exchange failed: ' . ($tokens['error_description'] ?? $tokens['error']));
        }

        // Discover which Google account this is.
        $client->setAccessToken($tokens);
        $email = null;
        try {
            $oauth = new GoogleOauth2($client);
            $info = $oauth->userinfo->get();
            $email = $info->getEmail();
        } catch (\Throwable $e) {
            Log::warning('Google userinfo lookup failed', ['error' => $e->getMessage()]);
        }

        $user->google_refresh_token = $tokens['refresh_token'] ?? $user->google_refresh_token;
        $user->google_token_expires_at = Carbon::now()->addSeconds((int) ($tokens['expires_in'] ?? 3600));
        $user->google_email = $email;
        $user->google_calendar_id = 'primary';
        $user->save();

        return $user->fresh();
    }

    /**
     * Returns a Google\Client authenticated for the given user.
     * Refreshes the access token if expired.
     */
    public function clientForUser(User $user): GoogleClient
    {
        if (! $user->hasGoogleConnected()) {
            throw new RuntimeException('User has not connected Google.');
        }

        $client = $this->baseClient();

        $needsRefresh = ! $user->google_token_expires_at
            || $user->google_token_expires_at->isPast();

        if ($needsRefresh) {
            $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
            $token = $client->getAccessToken();
            if (isset($token['error'])) {
                throw new RuntimeException('Failed to refresh Google token: ' . ($token['error_description'] ?? $token['error']));
            }
            $user->google_token_expires_at = Carbon::now()->addSeconds((int) ($token['expires_in'] ?? 3600));
            $user->save();
        } else {
            // Use stored refresh path; access token can be re-acquired on demand.
            $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
        }

        return $client;
    }

    /** Forget Google credentials for a user. */
    public function disconnect(User $user): void
    {
        // Best-effort revoke; we ignore failures so disconnect always wins locally.
        try {
            if ($user->google_refresh_token) {
                $client = $this->baseClient();
                $client->revokeToken($user->google_refresh_token);
            }
        } catch (\Throwable $e) {
            Log::warning('Google token revoke failed', ['error' => $e->getMessage()]);
        }

        $user->google_refresh_token = null;
        $user->google_token_expires_at = null;
        $user->google_email = null;
        $user->google_calendar_id = null;
        $user->save();
    }
}
