<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;

/**
 * Sandbox billing: no money changes hands.
 *
 * This is what runs in local/demo installs and whenever the configured driver
 * is missing credentials. It is a real driver rather than an `else` branch so
 * the rest of the app never has to special-case "no gateway", and the UI can
 * say so honestly (see the banner in resources/views/billing/index.blade.php).
 */
class ManualGateway extends BaseGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function isConfigured(): bool
    {
        return true; // always available; it just doesn't charge anything
    }

    /**
     * There is no checkout to send anyone to — BillingController settles the
     * subscription inline instead and never calls this.
     */
    public function checkoutUrl(Company $company, string $plan): string
    {
        return route('billing.index');
    }

    public function confirm(string $reference): ?Subscription
    {
        return null;
    }
}
