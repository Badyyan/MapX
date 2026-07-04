<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\Review;
use Illuminate\Support\Str;

/**
 * Shared mock behaviour. Each concrete adapter overrides the real API calls
 * and falls back to these deterministic mock responses while
 * config('mapx.integrations.mock') is true, so the whole product can be
 * exercised end-to-end without external credentials.
 */
abstract class BaseAdapter implements PlatformAdapter
{
    public function __construct(protected string $platform)
    {
    }

    public function key(): string
    {
        return $this->platform;
    }

    protected function mock(): bool
    {
        return (bool) config('mapx.integrations.mock', true);
    }

    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        return [
            'sync_status' => 'synced',
            'data_completeness' => $branch->completenessScore(),
            'external_id' => $connection->external_id ?: $this->platform.'_'.Str::random(12),
        ];
    }

    public function fetchReviews(PlatformConnection $connection, Branch $branch): array
    {
        return []; // mock mode: demo reviews come from the seeder
    }

    public function replyToReview(PlatformConnection $connection, Review $review, string $content): bool
    {
        return true; // mock mode: pretend the platform accepted the reply
    }

    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array
    {
        return [
            'status' => 'published',
            'external_id' => $this->platform.'_post_'.Str::random(10),
            'error' => null,
        ];
    }

    public function fetchInsights(PlatformConnection $connection, Branch $branch): array
    {
        return [];
    }
}
