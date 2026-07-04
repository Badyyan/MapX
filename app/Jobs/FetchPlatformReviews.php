<?php

namespace App\Jobs;

use App\Models\PlatformConnection;
use App\Models\Review;
use App\Services\AiService;
use App\Services\AutoReplyEngine;
use App\Services\PlatformManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * FR-12/FR-13: ingest reviews from a platform, classify sentiment, extract
 * topics, then hand each new review to the auto-reply engine.
 */
class FetchPlatformReviews implements ShouldQueue
{
    use Queueable;

    public function __construct(public PlatformConnection $connection)
    {
    }

    public function handle(PlatformManager $platforms, AiService $ai, AutoReplyEngine $autoReply): void
    {
        $connection = $this->connection->fresh();
        $branch = $connection->branch()->withoutGlobalScope('company')->first();

        if (! $branch) {
            return;
        }

        $payloads = $platforms->adapter($connection->platform)->fetchReviews($connection, $branch);

        foreach ($payloads as $payload) {
            $review = Review::withoutGlobalScope('company')->firstOrNew([
                'platform' => $connection->platform,
                'external_id' => $payload['external_id'],
            ]);

            $isNew = ! $review->exists;

            $review->fill([
                'company_id' => $connection->company_id,
                'branch_id' => $branch->id,
                'author_name' => $payload['author_name'] ?? null,
                'author_avatar' => $payload['author_avatar'] ?? null,
                'rating' => $payload['rating'] ?? null,
                'content' => $payload['content'] ?? null,
                'review_date' => $payload['review_date'] ?? now(),
            ]);

            if ($isNew) {
                $review->sentiment = $ai->classifySentiment($review->content, $review->rating);
                $review->topics = $ai->extractTopics($review->content);
                $review->language = preg_match('/\p{Arabic}/u', $review->content ?? '') ? 'ar' : 'en';
            }

            $review->save();

            if ($isNew) {
                $autoReply->process($review);
            }
        }
    }
}
