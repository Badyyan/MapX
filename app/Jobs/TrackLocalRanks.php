<?php

namespace App\Jobs;

use App\Services\RankTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackLocalRanks implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $companyId)
    {
    }

    public function handle(RankTracker $tracker): void
    {
        $tracker->trackCompany($this->companyId);
    }
}
