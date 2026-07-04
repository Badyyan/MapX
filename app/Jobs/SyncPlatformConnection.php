<?php

namespace App\Jobs;

use App\Models\PlatformConnection;
use App\Services\PlatformManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * FR-7: push branch data to a platform. Runs on the queue (SQS in
 * production per SRS 4.3) so external API latency never blocks requests.
 */
class SyncPlatformConnection implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public PlatformConnection $connection)
    {
    }

    public function handle(PlatformManager $platforms): void
    {
        $connection = $this->connection->fresh();
        $branch = $connection->branch()->withoutGlobalScope('company')->first();

        if (! $branch) {
            return;
        }

        $connection->update(['sync_status' => 'syncing']);

        try {
            $result = $platforms->adapter($connection->platform)->syncLocation($connection, $branch);

            $connection->update([
                'sync_status' => $result['sync_status'],
                'data_completeness' => $result['data_completeness'],
                'external_id' => $result['external_id'] ?? $connection->external_id,
                'last_synced_at' => now(),
                'status' => 'connected',
            ]);
        } catch (\Throwable $e) {
            $connection->update(['sync_status' => 'error', 'meta' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }
}
