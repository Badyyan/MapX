<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\Review;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Support\Facades\Http;

/**
 * Google Business Profile (FR-7): locations, posts, reviews, insights.
 *
 * Auth: the company-level connection (branch_id=null) holds the OAuth
 * tokens; per-branch connections carry the location resource name in
 * external_id ("locations/{id}") and the owning account in meta.account
 * ("accounts/{id}"). Reviews/posts still use the v4 API, business info the
 * v1 Business Information API, metrics the Business Profile Performance API.
 */
class GoogleBusinessProfileAdapter extends BaseAdapter
{
    public function __construct(private GoogleOAuthService $oauth)
    {
        parent::__construct('google');
    }

    private function token(PlatformConnection $connection): string
    {
        $companyConnection = $this->oauth->companyConnection($connection->company_id);

        if (! $companyConnection) {
            throw new \RuntimeException('Google is not connected for this company.');
        }

        return $this->oauth->tokenFor($companyConnection);
    }

    /** v4 resource path: accounts/{acc}/locations/{loc} */
    private function v4Path(PlatformConnection $connection): string
    {
        $account = $connection->meta['account'] ?? null;
        $location = str_replace('locations/', '', (string) $connection->external_id);

        return "{$account}/locations/{$location}";
    }

    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::syncLocation($connection, $branch);
        }

        $payload = array_filter([
            'title' => $branch->name,
            'phoneNumbers' => $branch->phone ? ['primaryPhone' => $branch->phone] : null,
            'websiteUri' => $branch->website,
            'regularHours' => $this->mapHours($branch->hours),
        ]);

        $response = Http::withToken($this->token($connection))
            ->patch("https://mybusinessbusinessinformation.googleapis.com/v1/{$connection->external_id}", array_merge($payload, [
                'updateMask' => implode(',', array_keys($payload)),
            ]));

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

        $response = Http::withToken($this->token($connection))
            ->get('https://mybusiness.googleapis.com/v4/'.$this->v4Path($connection).'/reviews');

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('reviews', []))->map(fn ($review) => [
            'external_id' => $review['reviewId'],
            'author_name' => $review['reviewer']['displayName'] ?? null,
            'author_avatar' => $review['reviewer']['profilePhotoUrl'] ?? null,
            'rating' => ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5][$review['starRating']] ?? null,
            'content' => $review['comment'] ?? null,
            'review_date' => $review['createTime'] ?? null,
        ])->all();
    }

    public function replyToReview(PlatformConnection $connection, Review $review, string $content): bool
    {
        if ($this->mock()) {
            return true;
        }

        return Http::withToken($this->token($connection))
            ->put('https://mybusiness.googleapis.com/v4/'.$this->v4Path($connection)."/reviews/{$review->external_id}/reply", [
                'comment' => $content,
            ])->successful();
    }

    public function publishPost(PlatformConnection $connection, Post $post, Branch $branch): array
    {
        if ($this->mock()) {
            return parent::publishPost($connection, $post, $branch);
        }

        $response = Http::withToken($this->token($connection))
            ->post('https://mybusiness.googleapis.com/v4/'.$this->v4Path($connection).'/localPosts', array_filter([
                'summary' => $post->content,
                'languageCode' => app()->getLocale(),
                'topicType' => 'STANDARD',
                'callToAction' => $post->cta_url ? ['actionType' => 'LEARN_MORE', 'url' => $post->cta_url] : null,
            ]));

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

        $response = Http::withToken($this->token($connection))
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
                    'openTime' => $this->toTimeOfDay($range['open']),
                    'closeTime' => $this->toTimeOfDay($range['close']),
                ];
            }
        }

        return ['periods' => $periods];
    }

    private function toTimeOfDay(string $time): array
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ['hours' => (int) $hours, 'minutes' => (int) $minutes];
    }
}
