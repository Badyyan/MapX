<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

/**
 * Facebook Pages via the Graph API (FR-9): page posts + insights.
 */
class FacebookAdapter extends BaseAdapter
{
    protected string $graph = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        parent::__construct('facebook');
    }

    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::publishPost($connection, $post, $branch);
        }

        $response = Http::post("{$this->graph}/{$connection->external_id}/feed", [
            'message' => $post->content,
            'link' => $post->cta_url,
            'access_token' => $connection->access_token,
        ]);

        return [
            'status' => $response->successful() ? 'published' : 'failed',
            'external_id' => $response->json('id'),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }

    public function fetchInsights(PlatformConnection $connection, Branch $branch): array
    {
        if ($this->mock()) {
            return [];
        }

        $response = Http::get("{$this->graph}/{$connection->external_id}/insights", [
            'metric' => 'page_impressions,page_post_engagements',
            'access_token' => $connection->access_token,
        ]);

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }
}
