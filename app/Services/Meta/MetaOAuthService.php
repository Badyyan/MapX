<?php

namespace App\Services\Meta;

use App\Models\PlatformConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meta (Facebook Pages + Instagram Business) OAuth.
 *
 * The company-level connection (platform=facebook, branch_id=null) stores a
 * long-lived user token; per-branch page connections store the page token in
 * external credentials. Instagram publishing rides on the page's linked
 * instagram_business_account.
 */
class MetaOAuthService
{
    public const SCOPES = 'pages_show_list,pages_read_engagement,pages_manage_posts,pages_manage_engagement,instagram_basic,instagram_content_publish';

    private function graph(): string
    {
        return 'https://graph.facebook.com/'.config('services.meta.graph_version');
    }

    public function isConfigured(): bool
    {
        return (bool) (config('services.meta.app_id') && config('services.meta.app_secret'));
    }

    public function redirectUrl(string $state): string
    {
        return 'https://www.facebook.com/'.config('services.meta.graph_version').'/dialog/oauth?'.http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => route('integrations.meta.callback'),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);
    }

    /** Exchange the code, then upgrade to a long-lived (~60 day) token. */
    public function exchangeCode(string $code): array
    {
        $shortLived = Http::get($this->graph().'/oauth/access_token', [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => route('integrations.meta.callback'),
            'code' => $code,
        ]);

        if (! $shortLived->successful()) {
            throw new RuntimeException('Meta token exchange failed: '.$shortLived->body());
        }

        $longLived = Http::get($this->graph().'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $shortLived->json('access_token'),
        ]);

        return $longLived->successful() ? $longLived->json() : $shortLived->json();
    }

    /**
     * Pages the user manages, each with its own page access token and any
     * linked Instagram business account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pages(string $userToken): array
    {
        $response = Http::get($this->graph().'/me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            'access_token' => $userToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to list Facebook pages: '.$response->body());
        }

        return $response->json('data', []);
    }

    public function companyConnection(int $companyId): ?PlatformConnection
    {
        return PlatformConnection::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('platform', 'facebook')
            ->whereNull('branch_id')
            ->first();
    }
}
