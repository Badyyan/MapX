<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

/**
 * Instagram Business via the Graph API (FR-9): media publishing + insights.
 * Instagram requires an image for feed posts; text-only posts fail with a
 * clear error so the UI can explain the constraint.
 */
class InstagramAdapter extends BaseAdapter
{
    protected string $graph = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        parent::__construct('instagram');
    }

    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::publishPost($connection, $post, $branch);
        }

        if (! $post->image_path) {
            return ['status' => 'failed', 'external_id' => null, 'error' => 'Instagram posts require an image.'];
        }

        $container = Http::post("{$this->graph}/{$connection->external_id}/media", [
            'image_url' => asset('storage/'.$post->image_path),
            'caption' => $post->content,
            'access_token' => $connection->access_token,
        ]);

        if (! $container->successful()) {
            return ['status' => 'failed', 'external_id' => null, 'error' => $container->body()];
        }

        $publish = Http::post("{$this->graph}/{$connection->external_id}/media_publish", [
            'creation_id' => $container->json('id'),
            'access_token' => $connection->access_token,
        ]);

        return [
            'status' => $publish->successful() ? 'published' : 'failed',
            'external_id' => $publish->json('id'),
            'error' => $publish->successful() ? null : $publish->body(),
        ];
    }
}
