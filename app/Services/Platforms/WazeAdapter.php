<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;

/**
 * Waze (FR-10 constraint: no listing edits) — deep links + visibility only.
 */
class WazeAdapter extends BaseAdapter
{
    public function __construct()
    {
        parent::__construct('waze');
    }

    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        // Waze offers no write API; a connection simply records the deep link.
        return [
            'sync_status' => $branch->lat ? 'synced' : 'error',
            'data_completeness' => $branch->lat ? 100 : 0,
            'external_id' => $branch->wazeDeepLink(),
        ];
    }
}
