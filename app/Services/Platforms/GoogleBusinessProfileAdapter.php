<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\Review;
use Illuminate\Support\Facades\Http;

/**
 * Google Business Profile (FR-7): locations, posts, reviews, insights.
 *
 * Real mode uses the My Business APIs:
 *  - mybusinessbusinessinformation.googleapis.com (locations)
 *  - mybusiness.googleapis.com/v4 (reviews + replies)
 *  - businessprofileperformance.googleapis.com (insights)
 * OAuth2 tokens are stored encrypted on the PlatformConnection.
 */
class GoogleBusinessProfileAdapter extends BaseAdapter
{
    public function __construct()
    {
        parent::__construct('google');
    }

    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::syncLocation($connection, $branch);
        }

        $payload = [
            'title' => $branch->name,
            'profile' => ['description' => $branch->description],
            'storefrontAddress' => [
                'addressLines' => [$branch->address],
                'locality' => $branch->city,
                'regionCode' => $branch->country,
            ],
            'latlng' => ['latitude' => $branch->lat, 'longitude' => $branch->lng],
            'phoneNumbers' => ['primaryPhone' => $branch->phone],
            'websiteUri' => $branch->website,
            'regularHours' => $this->mapHours($branch->hours),
        ];

        $response = Http::withToken($connection->access_token)
            ->patch("https://mybusinessbusinessinformation.googleapis.com/v1/{$connection->external_id}", $payload);

        return [
            'sync_status' => $response->successful() ? 'synced' : 'error',
            'data_completeness' => $branch->completenessScore(),
            'external_id' => $connection->external_id,
        ];
    }

    public function fetchReviews(PlatformConnection $connection, Branch $branch): array
    {
        if ($this->mock()) {
            return [];
        }

        $response = Http::withToken($connection->access_token)
            ->get("https://mybusiness.googleapis.com/v4/{$connection->external_id}/reviews");

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('reviews', []))->map(fn ($r) => [
            'external_id' => $r['reviewId'],
            'author_name' => $r['reviewer']['displayName'] ?? null,
            'author_avatar' => $r['reviewer']['profilePhotoUrl'] ?? null,
            'rating' => ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5][$r['starRating']] ?? null,
            'content' => $r['comment'] ?? null,
            'review_date' => $r['createTime'] ?? null,
        ])->all();
    }

    public function replyToReview(PlatformConnection $connection, Review $review, string $content): bool
    {
        if ($this->mock()) {
            return true;
        }

        return Http::withToken($connection->access_token)
            ->put("https://mybusiness.googleapis.com/v4/{$connection->external_id}/reviews/{$review->external_id}/reply", [
                'comment' => $content,
            ])->successful();
    }

    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::publishPost($connection, $post, $branch);
        }

        $response = Http::withToken($connection->access_token)
            ->post("https://mybusiness.googleapis.com/v4/{$connection->external_id}/localPosts", [
                'summary' => $post->content,
                'languageCode' => app()->getLocale(),
                'callToAction' => $post->cta_url ? ['actionType' => 'LEARN_MORE', 'url' => $post->cta_url] : null,
            ]);

        return [
            'status' => $response->successful() ? 'published' : 'failed',
            'external_id' => $response->json('name'),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }

    public function fetchInsights(PlatformConnection $connection, Branch $branch): array
    {
        if ($this->mock()) {
            return [];
        }

        $response = Http::withToken($connection->access_token)
            ->get("https://businessprofileperformance.googleapis.com/v1/{$connection->external_id}:fetchMultiDailyMetricsTimeSeries", [
                'dailyMetrics' => ['CALL_CLICKS', 'BUSINESS_DIRECTION_REQUESTS', 'WEBSITE_CLICKS'],
            ]);

        return $response->successful() ? ($response->json('multiDailyMetricTimeSeries') ?? []) : [];
    }

    private function mapHours(?array $hours): ?array
    {
        if (! $hours) {
            return null;
        }

        $periods = [];
        foreach ($hours as $day => $range) {
            if (! empty($range['open']) && ! empty($range['close'])) {
                $periods[] = [
                    'openDay' => strtoupper($day),
                    'closeDay' => strtoupper($day),
                    'openTime' => $range['open'],
                    'closeTime' => $range['close'],
                ];
            }
        }

        return ['periods' => $periods];
    }
}
