<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar — mada, Apple Pay, Visa/Mastercard and STC Pay for Saudi merchants.
 *
 * Stripe does not onboard KSA entities, so this is the driver that actually
 * takes money in MapX's home market.
 *
 * Flow: subscribe -> MapX-branded checkout page (resources/views/billing/
 * checkout.blade.php) hosting Moyasar's embedded form -> customer pays
 * (3-D Secure happens inside the form) -> Moyasar redirects to
 * /billing/success?id=<payment_id> -> confirm() re-fetches the payment from
 * the API and activates the subscription -> the webhook does the same thing
 * for customers who close the tab before returning.
 *
 * Unlike Stripe, Moyasar has no subscription primitive: MapX saves a card
 * token at first payment and charges renewals itself from
 * App\Jobs\ChargeSubscriptionRenewal.
 */
class MoyasarGateway extends BaseGateway
{
    /** Payment states Moyasar considers settled money. */
    private const PAID_STATUSES = ['paid', 'captured'];

    /** Payment states that mean the money will never arrive. */
    private const FAILED_STATUSES = ['failed', 'voided', 'canceled', 'expired', 'abandoned'];

    public function __construct(BillingService $billing, private MoyasarClient $client)
    {
        parent::__construct($billing);
    }

    public function key(): string
    {
        return 'moyasar';
    }

    public function isConfigured(): bool
    {
        return (bool) (config('services.moyasar.publishable_key') && config('services.moyasar.secret_key'));
    }

    /**
     * Moyasar's form is embedded, not hosted, so this points at a MapX route
     * rather than an external page. BillingController redirects with to(),
     * not away(), for exactly this reason.
     */
    public function checkoutUrl(Company $company, string $plan): string
    {
        return route('billing.checkout', ['plan' => $plan]);
    }

    /**
     * Confirm a payment id that arrived on the callback URL. The id is a
     * query-string value and therefore untrusted — everything that matters
     * is read back from the API.
     */
    public function confirm(string $reference): ?Subscription
    {
        try {
            $payment = $this->client->fetchPayment($reference);
        } catch (\Throwable $e) {
            Log::warning('Moyasar payment lookup failed: '.$e->getMessage(), ['payment' => $reference]);

            return null;
        }

        return $payment ? $this->applyPayment($payment) : null;
    }

    /**
     * Apply a Moyasar payment to local state. Shared by the callback and the
     * webhook, and idempotent, because both routinely fire for one payment.
     *
     * @param  array<string, mixed>  $payment
     */
    public function applyPayment(array $payment): ?Subscription
    {
        $status = $payment['status'] ?? null;
        $companyId = (int) ($payment['metadata']['company_id'] ?? 0);
        $plan = in_array($payment['metadata']['plan'] ?? null, ['monthly', 'yearly'], true)
            ? $payment['metadata']['plan']
            : 'monthly';

        if (! $companyId) {
            Log::warning('Moyasar payment has no company_id metadata; ignoring.', ['payment' => $payment['id'] ?? null]);

            return null;
        }

        if (in_array($status, self::FAILED_STATUSES, true)) {
            $this->failPaymentFor($companyId);

            return null;
        }

        if (! in_array($status, self::PAID_STATUSES, true)) {
            return null; // initiated / authorized — nothing settled yet
        }

        // Renewal charges are settled by ChargeSubscriptionRenewal, which
        // knows the branch count it billed. A webhook for one must not
        // re-run activation and reset the billing period a second time.
        if (($payment['metadata']['renewal'] ?? null) === '1') {
            return Subscription::where('company_id', $companyId)->first();
        }

        $company = Company::find($companyId);

        if (! $company || ! $this->amountIsCorrect($payment, $company, $plan)) {
            return null;
        }

        $branchCount = $this->billing->billableBranchCount($company);

        $subscription = $this->activateSubscription(
            companyId: $companyId,
            plan: $plan,
            subscriptionId: $payment['id'] ?? null,
        );

        if (! $subscription) {
            return null;
        }

        if ($token = $this->savedCardToken($payment)) {
            $subscription->update(['payment_token' => $token, 'renewal_attempts' => 0]);
        }

        $this->recordPaidInvoice(
            subscription: $subscription,
            reference: (string) $payment['id'],
            amount: ((int) ($payment['amount'] ?? 0)) / 100,
            currency: $payment['currency'] ?? null,
            branchCount: $branchCount,
            periodStart: $subscription->current_period_start,
            periodEnd: $subscription->current_period_end,
        );

        return $subscription;
    }

    /**
     * The embedded form sets the amount in browser JavaScript, so a customer
     * can edit it in devtools before submitting — unlike Stripe's hosted
     * checkout, where the amount is fixed server-side. Recompute what the
     * plan actually costs and refuse anything else.
     */
    private function amountIsCorrect(array $payment, Company $company, string $plan): bool
    {
        $expected = $this->billing->minorUnits(
            $this->billing->amountFor($plan, $this->billing->billableBranchCount($company))
        );
        $paid = (int) ($payment['amount'] ?? 0);
        $currency = strtoupper((string) ($payment['currency'] ?? ''));

        if ($paid === $expected && $currency === strtoupper((string) config('mapx.billing.currency'))) {
            return true;
        }

        // Also reachable innocently: a branch added between page load and
        // payment changes the price. Either way it must not activate.
        Log::warning('Moyasar payment amount does not match the plan price; refusing to activate.', [
            'payment' => $payment['id'] ?? null,
            'company' => $company->id,
            'plan' => $plan,
            'paid' => $paid,
            'expected' => $expected,
            'currency' => $currency,
        ]);

        return false;
    }

    /**
     * Charge a saved card token — how renewals work, since Moyasar has no
     * recurring subscriptions of its own.
     *
     * @return array<string, mixed>|null the payment, or null when it failed
     */
    public function chargeSavedCard(Subscription $subscription, float $amount, int $branchCount): ?array
    {
        $token = $subscription->payment_token;

        if (! $token) {
            Log::warning('Moyasar renewal skipped: no saved card token.', ['subscription' => $subscription->id]);

            return null;
        }

        try {
            $payment = $this->client->createPayment(
                amountMinor: $this->billing->minorUnits($amount),
                description: $this->renewalDescription($subscription, $branchCount),
                source: ['type' => 'token', 'token' => $token],
                metadata: [
                    'company_id' => (string) $subscription->company_id,
                    'plan' => $subscription->plan,
                    'branch_count' => (string) $branchCount,
                    'renewal' => '1',
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Moyasar renewal charge failed: '.$e->getMessage(), ['subscription' => $subscription->id]);

            return null;
        }

        return in_array($payment['status'] ?? null, self::PAID_STATUSES, true) ? $payment : null;
    }

    /** Settle a successful renewal: record the invoice and push the period out. */
    public function recordRenewal(Subscription $subscription, array $payment, int $branchCount): void
    {
        $recorded = $this->recordPaidInvoice(
            subscription: $subscription,
            reference: (string) $payment['id'],
            amount: ((int) ($payment['amount'] ?? 0)) / 100,
            currency: $payment['currency'] ?? null,
            branchCount: $branchCount,
            periodStart: now(),
            periodEnd: $this->periodEndFor($subscription->plan),
        );

        if ($recorded) {
            $this->extendPeriod($subscription);
            $subscription->update(['renewal_attempts' => 0, 'last_renewal_attempt_at' => now()]);
        }
    }

    /**
     * Count the failed attempt. Only the last one in the ladder locks the
     * account, so a single declined card doesn't cut off a paying customer.
     */
    public function markRenewalFailed(Subscription $subscription): void
    {
        $attempts = $subscription->renewal_attempts + 1;

        $subscription->update([
            'renewal_attempts' => $attempts,
            'last_renewal_attempt_at' => now(),
        ]);

        if ($attempts >= (int) config('mapx.billing.renewal.max_attempts', 3)) {
            $this->markPastDueSubscription($subscription);
        }
    }

    /**
     * Moyasar charges are one-off, so there is nothing to cancel remotely —
     * dropping the saved token is what stops future renewals.
     */
    public function cancel(Subscription $subscription): void
    {
        $subscription->update(['payment_token' => null]);

        parent::cancel($subscription);
    }

    /** @return array<int, string> */
    public function enabledMethods(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.moyasar.methods', 'creditcard'))
        )));
    }

    public function paymentDescription(Company $company, string $plan, int $branchCount): string
    {
        return sprintf(
            'MapX %s plan — %d branch%s (%s)',
            $plan === 'yearly' ? 'yearly' : 'monthly',
            $branchCount,
            $branchCount === 1 ? '' : 'es',
            $company->name,
        );
    }

    private function renewalDescription(Subscription $subscription, int $branchCount): string
    {
        return sprintf(
            'MapX %s renewal — %d branch%s',
            $subscription->plan === 'yearly' ? 'yearly' : 'monthly',
            $branchCount,
            $branchCount === 1 ? '' : 'es',
        );
    }

    /**
     * The token Moyasar returns when the customer ticks "save this card".
     * Stored on subscriptions.gateway_customer_id and replayed for renewals.
     */
    private function savedCardToken(array $payment): ?string
    {
        $source = $payment['source'] ?? [];

        // Field name is the one part of this integration that could not be
        // confirmed against live docs (docs.moyasar.com is unreachable from
        // CI), so both observed spellings are accepted. If neither is
        // present, renewals simply can't run — see markRenewalFailed.
        return $source['token'] ?? $source['saved_card_token'] ?? null;
    }

    private function failPaymentFor(int $companyId): void
    {
        $subscription = Subscription::where('company_id', $companyId)->first();

        // A failed *first* payment must not lock a company that is still
        // inside its trial — only an already-paying subscription goes past_due.
        if ($subscription && $subscription->status === 'active') {
            $this->markPastDueSubscription($subscription);
        }
    }
}
