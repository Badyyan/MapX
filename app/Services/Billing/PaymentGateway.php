<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;

/**
 * The checkout contract every billing driver implements (FR-28..31).
 *
 * Deliberately free of any provider's payload shape: webhook bodies stay in
 * the driver that understands them and are mapped onto the normalized state
 * transitions in BaseGateway. Forcing one provider's array shape onto the
 * others is what made the previous "abstraction" fictional.
 */
interface PaymentGateway
{
    /** Driver key as used by MAPX_BILLING_GATEWAY and stamped on invoices. */
    public function key(): string;

    /** Whether this driver has the credentials it needs to charge money. */
    public function isConfigured(): bool;

    /**
     * Where to send the customer to pay. May be an external hosted checkout
     * (Stripe) or an internal MapX route (Moyasar's embedded form), so the
     * caller must use redirect()->to(), not redirect()->away().
     */
    public function checkoutUrl(Company $company, string $plan): string;

    /**
     * Confirm a payment after the customer returns. The reference comes from
     * the query string and is therefore untrusted: implementations must
     * re-fetch the payment from the provider before activating anything.
     */
    public function confirm(string $reference): ?Subscription;

    /** Stop future charges. Local state is always updated; remote is best-effort. */
    public function cancel(Subscription $subscription): void;
}
