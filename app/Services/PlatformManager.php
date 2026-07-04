<?php

namespace App\Services;

use App\Services\Platforms\PlatformAdapter;
use App\Services\Platforms\RideDeepLinkAdapter;
use InvalidArgumentException;

class PlatformManager
{
    /** @var array<string, PlatformAdapter> */
    private array $adapters = [];

    public function adapter(string $platform): PlatformAdapter
    {
        if (isset($this->adapters[$platform])) {
            return $this->adapters[$platform];
        }

        $driver = config("mapx.platforms.{$platform}.driver");

        if (! $driver) {
            throw new InvalidArgumentException("Unknown platform [{$platform}]");
        }

        return $this->adapters[$platform] = $driver === RideDeepLinkAdapter::class
            ? new RideDeepLinkAdapter($platform)
            : app($driver);
    }

    /** @return array<string, array> */
    public function catalog(): array
    {
        return config('mapx.platforms');
    }
}
