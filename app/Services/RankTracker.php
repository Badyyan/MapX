<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\LocalRankSnapshot;
use App\Models\TrackedKeyword;

/**
 * FR-26: local rank tracking for keywords within a radius.
 * Real mode plugs a SERP/places provider into fetchPosition(); mock mode
 * simulates a plausible random walk so trend charts work end-to-end.
 */
class RankTracker
{
    public function trackCompany(int $companyId): int
    {
        $keywords = TrackedKeyword::withoutGlobalScope('company')
            ->where('company_id', $companyId)->where('is_active', true)->get();
        $branches = Branch::withoutGlobalScope('company')
            ->where('company_id', $companyId)->get();

        $count = 0;
        foreach ($keywords as $keyword) {
            foreach ($branches as $branch) {
                LocalRankSnapshot::withoutGlobalScope('company')->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'branch_id' => $branch->id,
                        'keyword' => $keyword->keyword,
                        'platform' => 'google',
                        'tracked_at' => now()->toDateString(),
                    ],
                    [
                        'position' => $this->fetchPosition($branch, $keyword),
                        'radius_km' => $keyword->radius_km,
                    ],
                );
                $count++;
            }
        }

        return $count;
    }

    private function fetchPosition(Branch $branch, TrackedKeyword $keyword): ?int
    {
        if (config('mapx.integrations.mock', true)) {
            // Random walk from the previous snapshot keeps demo trends realistic.
            $previous = LocalRankSnapshot::withoutGlobalScope('company')
                ->where('branch_id', $branch->id)
                ->where('keyword', $keyword->keyword)
                ->orderByDesc('tracked_at')
                ->value('position') ?? random_int(5, 20);

            return max(1, min(30, $previous + random_int(-2, 2)));
        }

        // Real mode: call a local SERP provider (DataForSEO, SerpApi, …) here.
        return null;
    }

    /** Position history per keyword for charting. */
    public function series(int $branchId, int $days = 30): array
    {
        $snapshots = LocalRankSnapshot::where('branch_id', $branchId)
            ->where('tracked_at', '>=', now()->subDays($days)->toDateString())
            ->orderBy('tracked_at')
            ->get();

        $labels = $snapshots->pluck('tracked_at')->map(fn ($d) => $d->format('M d'))->unique()->values();

        return [
            'labels' => $labels->all(),
            'series' => $snapshots->groupBy('keyword')->map(
                fn ($group) => $group->pluck('position')->all()
            )->all(),
        ];
    }
}
