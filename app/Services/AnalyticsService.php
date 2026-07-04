<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\PresenceMetric;
use App\Models\QrCampaign;
use App\Models\Review;
use Illuminate\Support\Collection;

/**
 * FR-25/FR-26/FR-27: presence health, rating trends, sentiment
 * distribution, topics, GBP actions and QR funnel numbers.
 * All queries are tenant-scoped through the models' global scopes.
 */
class AnalyticsService
{
    /** Overall presence health score 0-100 for the current company. */
    public function presenceHealthScore(): int
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            return 0;
        }

        // Completeness of branch data (50 %), connection sync health (30 %),
        // verification coverage (20 %).
        $completeness = $branches->avg(fn (Branch $b) => $b->completenessScore());

        $connections = PlatformConnection::all();
        $syncHealth = $connections->isEmpty() ? 0
            : $connections->where('sync_status', 'synced')->count() / $connections->count() * 100;

        $verified = $branches->where('verification_status', 'verified')->count() / $branches->count() * 100;

        return (int) round($completeness * 0.5 + $syncHealth * 0.3 + $verified * 0.2);
    }

    /** Average rating per month for the last N months. */
    public function ratingTrend(int $months = 6, ?int $branchId = null): array
    {
        $rows = Review::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('review_date', '>=', now()->subMonths($months)->startOfMonth())
            ->get(['rating', 'review_date'])
            ->groupBy(fn (Review $review) => $review->review_date?->format('Y-m'))
            ->map(fn (Collection $group) => round($group->avg('rating'), 2))
            ->sortKeys();

        return ['labels' => $rows->keys()->all(), 'values' => $rows->values()->all()];
    }

    public function sentimentDistribution(?int $branchId = null): array
    {
        return Review::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('sentiment, count(*) as total')
            ->groupBy('sentiment')
            ->pluck('total', 'sentiment')
            ->all();
    }

    public function ratingDistribution(): array
    {
        return Review::selectRaw('cast(rating as integer) as stars, count(*) as total')
            ->whereNotNull('rating')
            ->groupBy('stars')
            ->orderBy('stars')
            ->pluck('total', 'stars')
            ->all();
    }

    /** Top topics across reviews (already extracted per review). */
    public function topTopics(int $limit = 12): array
    {
        return Review::whereNotNull('topics')
            ->pluck('topics')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->all();
    }

    /** Daily GBP action metrics (calls / routes / website clicks). */
    public function actionsSeries(int $days = 30, ?int $branchId = null): array
    {
        $rows = PresenceMetric::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->get()
            ->groupBy('metric')
            ->map(fn (Collection $group) => $group->groupBy(fn ($m) => $m->date->format('Y-m-d'))
                ->map(fn (Collection $day) => $day->sum('value'))
                ->sortKeys());

        $labels = collect(range($days - 1, 0))
            ->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        return [
            'labels' => $labels->all(),
            'series' => $rows->map(fn ($byDay) => $labels->map(fn ($d) => $byDay[$d] ?? 0)->all())->all(),
        ];
    }

    public function actionTotals(int $days = 30): array
    {
        return PresenceMetric::where('date', '>=', now()->subDays($days)->toDateString())
            ->selectRaw('metric, sum(value) as total')
            ->groupBy('metric')
            ->pluck('total', 'metric')
            ->all();
    }

    public function qrFunnel(): array
    {
        $campaigns = QrCampaign::all();
        $scans = $campaigns->sum('scans_count');
        $positive = $campaigns->sum('positive_count');
        $negative = $campaigns->sum('negative_count');

        return [
            'scans' => $scans,
            'positive' => $positive,
            'negative' => $negative,
            'conversion' => $scans > 0 ? round(($positive + $negative) / $scans * 100, 1) : 0,
        ];
    }

    public function reviewKpis(): array
    {
        $total = Review::count();

        return [
            'total' => $total,
            'average' => $total ? round(Review::avg('rating'), 2) : null,
            'positive' => Review::where('sentiment', 'positive')->count(),
            'negative' => Review::where('sentiment', 'negative')->count(),
            'unanswered' => Review::where('is_replied', false)->count(),
        ];
    }
}
