<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\Review;

/**
 * Contract every platform integration implements (SRS 3.3).
 * Concrete adapters run in mock mode until real API credentials are
 * configured (MAPX_INTEGRATIONS_MOCK=false + platform keys in .env).
 */
interface PlatformAdapter
{
    public function key(): string;

    /** Push branch data to the platform. Returns updated sync meta. */
    public function syncLocation(PlatformConnection $connection, Branch $branch): array;

    /** Pull latest reviews. Returns array of normalized review payloads. */
    public function fetchReviews(PlatformConnection $connection, Branch $branch): array;

    /** Send a reply to a review on the platform. */
    public function replyToReview(PlatformConnection $connection, Review $review, string $content): bool;

    /** Publish a post for a branch. Returns [status, external_id, error]. */
    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array;

    /** Pull insight metrics (calls, routes, clicks…) keyed by metric name. */
    public function fetchInsights(PlatformConnection $connection, Branch $branch): array;
}
