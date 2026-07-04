<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;

/**
 * Snap Map (FR-11): read-only presence detection. Snap exposes no public
 * listings API, so presence is inferred statistically / via 3rd-party data.
 */
class SnapMapAdapter extends BaseAdapter
{
    public function __construct()
    {
        parent::__construct('snap');
    }

    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        return [
            'sync_status' => 'synced',
            'data_completeness' => $branch->lat ? 100 : 0,
            'external_id' => null,
        ];
    }
}
