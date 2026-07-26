<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Resolves the active payment driver, mirroring App\Services\PlatformManager.
 *
 * Resolution rules — chosen so no existing install changes behaviour:
 *
 *  1. An explicit MAPX_BILLING_GATEWAY naming a *configured* driver wins.
 *  2. An explicit name whose credentials are missing falls back to `manual`
 *     and logs a warning, so a typo or a half-finished setup degrades to
 *     honest sandbox billing instead of a 500 at the checkout button.
 *  3. `auto` (the default) picks the first configured real driver, else
 *     `manual`. This reproduces the old hardcoded "Stripe if a secret is
 *     present" behaviour for installs that never set MAPX_BILLING_GATEWAY.
 */
class PaymentGatewayManager
{
    /** Probe order for `auto`. */
    private const AUTO_ORDER = ['moyasar', 'stripe'];

    /** @var array<string, PaymentGateway> */
    private array $drivers = [];

    public function driver(string $name): PaymentGateway
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $class = config("mapx.billing.drivers.{$name}");

        if (! $class) {
            throw new InvalidArgumentException("Unknown billing gateway [{$name}]");
        }

        return $this->drivers[$name] = app($class);
    }

    /**
     * The driver that should handle checkout right now. Resolved on every
     * call — only the driver *instances* are memoised — because credentials
     * arrive from config that tests (and `php artisan config:clear`) change
     * between calls.
     */
    public function active(): PaymentGateway
    {
        return $this->resolve();
    }

    /** @return array<int, string> */
    public function available(): array
    {
        return array_keys(config('mapx.billing.drivers', []));
    }

    private function resolve(): PaymentGateway
    {
        $configured = (string) config('mapx.billing.gateway', 'auto');

        if ($configured === 'auto' || $configured === '') {
            foreach (self::AUTO_ORDER as $name) {
                if (config("mapx.billing.drivers.{$name}") && $this->driver($name)->isConfigured()) {
                    return $this->driver($name);
                }
            }

            return $this->driver('manual');
        }

        if (! config("mapx.billing.drivers.{$configured}")) {
            Log::warning("MAPX_BILLING_GATEWAY names an unknown driver [{$configured}]; falling back to sandbox billing.");

            return $this->driver('manual');
        }

        $driver = $this->driver($configured);

        if (! $driver->isConfigured()) {
            Log::warning("Billing gateway [{$configured}] is selected but not configured; falling back to sandbox billing.");

            return $this->driver('manual');
        }

        return $driver;
    }
}
