<?php

namespace App\Services\Google;

use App\Models\PlatformConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google OAuth 2.0 for the Business Profile APIs.
 *
 * The company-level connection (platform=google, branch_id=null) holds the
 * encrypted access/refresh tokens; per-branch connections reference it.
 * Scope business.manage covers locations, posts, reviews and insights.
 */
class GoogleOAuthService
{
    public const SCOPE = 'https://www.googleapis.com/auth/business.manage';

    public function isConfigured(): bool
    {
        return (bool) (config('services.google.client_id') && config('services.google.client_secret'));
    }

    public function redirectUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('integrations.google.callback'),
            'response_type' => 'code',
            'scope' => self::SCOPE.' openid email',
            'access_type' => 'offline',   // we need a refresh token for background sync
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /** Exchange the authorization code for tokens. */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('integrations.google.callback'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google token exchange failed: '.$response->body());
        }

        return $response->json();
    }

    /** The Google account's email, for display on the integrations page. */
    public function userEmail(string $accessToken): ?string
    {
        return Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->json('email');
    }

    /**
     * Return a valid access token for the company connection, refreshing
     * (and persisting) it if expired.
     */
    public function tokenFor(PlatformConnection $connection): string
    {
        if ($connection->token_expires_at?->subMinutes(2)->isFuture()) {
            return $connection->access_token;
        }

        if (! $connection->refresh_token) {
            throw new RuntimeException('Google connection has no refresh token; reconnect required.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $connection->update(['status' => 'error', 'sync_status' => 'error']);
            throw new RuntimeException('Google token refresh failed: '.$response->body());
        }

        $connection->update([
            'access_token' => $response->json('access_token'),
            'token_expires_at' => now()->addSeconds($response->json('expires_in', 3600)),
            'status' => 'connected',
        ]);

        return $response->json('access_token');
    }

    /** The company-level Google connection for a company, if any. */
    public function companyConnection(int $companyId): ?PlatformConnection
    {
        return PlatformConnection::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('platform', 'google')
            ->whereNull('branch_id')
            ->first();
    }
}
