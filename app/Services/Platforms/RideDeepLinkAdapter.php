<?php

namespace App\Services\Platforms;

use App\Models\Branch;
use App\Models\PlatformConnection;

/**
 * Uber / Careem / Bolt (FR-10): these platforms do not allow listing edits,
 * so the "integration" is universal deep-link generation per branch.
 */
class RideDeepLinkAdapter extends BaseAdapter
{
    public function syncLocation(PlatformConnection $connection, Branch $branch): array
    {
        $link = match ($this->platform) {
            'uber' => $branch->uberDeepLink(),
            'careem' => $branch->careemDeepLink(),
            'bolt' => $branch->boltDeepLink(),
            default => null,
        };

        return [
            'sync_status' => $link ? 'synced' : 'error',
            'data_completeness' => $link ? 100 : 0,
            'external_id' => $link,
        ];
    }
}
