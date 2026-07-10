<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * Stripe Checkout for subscriptions (FR-28..31 in real mode).
 *
 * Flow: subscribe -> hosted Stripe Checkout (card entry, 3DS verification)
 * -> webhook `checkout.session.completed` activates the local subscription
 * -> `invoice.paid` records renewals -> `invoice.payment_failed` marks
 * past_due (feature lock) -> `customer.subscription.deleted` cancels.
 */
class StripeGateway
{
    private ?StripeClient $stripe = null;

    /** Lazy client: webhook state updates don't need API access at all. */
    private function stripe(): StripeClient
    {
        return $this->stripe ??= new StripeClient(config('services.stripe.secret'));
    }

    public static function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    /** Create a hosted Checkout session and return its redirect URL. */
    public function checkoutUrl(Company $company, string $plan): string
    {
        $branchCount = max(1, Branch::withoutGlobalScope('company')->where('company_id', $company->id)->count());
        $isYearly = $plan === 'yearly';
        $pricePerBranch = (float) config('mapx.billing.price_per_branch');

        $unitAmount = $isYearly
            ? (int) round($pricePerBranch * 12 * (1 - config('mapx.billing.yearly_discount')) * 100)
            : (int) round($pricePerBranch * 100);

        $session = $this->stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            'client_reference_id' => (string) $company->id,
            'customer_email' => $company->email ?: $company->users()->first()?->email,
            'line_items' => [[
                'quantity' => $branchCount,
                'price_data' => [
                    'currency' => strtolower(config('mapx.billing.currency', 'SAR')),
                    'unit_amount' => $unitAmount,
                    'recurring' => ['interval' => $isYearly ? 'year' : 'month'],
                    'product_data' => [
                        'name' => 'MapX — '.($isYearly ? 'Yearly' : 'Monthly').' plan (per branch)',
                    ],
                ],
            ]],
            'metadata' => ['company_id' => $company->id, 'plan' => $plan],
            'subscription_data' => [
                'metadata' => ['company_id' => $company->id, 'plan' => $plan],
            ],
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.index'),
        ]);

        return $session->url;
    }

    /** Activate the local subscription after a completed Checkout session. */
    public function activateFromSession(string $sessionId): ?Subscription
    {
        $session = $this->stripe()->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
            return null;
        }

        return $this->activate(
            (int) ($session->metadata['company_id'] ?? $session->client_reference_id),
            $session->metadata['plan'] ?? 'monthly',
            (string) $session->customer,
            (string) $session->subscription,
        );
    }

    public function activate(int $companyId, string $plan, string $customerId, string $subscriptionId): ?Subscription
    {
        $company = Company::find($companyId);

        if (! $company) {
            return null;
        }

        $subscription = $company->subscription ?? new Subscription(['company_id' => $company->id]);
        $subscription->fill([
            'company_id' => $company->id,
            'plan' => $plan,
            'status' => 'active',
            'branch_limit' => null,
            'price_per_branch' => config('mapx.billing.price_per_branch'),
            'currency' => config('mapx.billing.currency'),
            'current_period_start' => now(),
            'current_period_end' => $plan === 'yearly' ? now()->addYear() : now()->addMonth(),
            'canceled_at' => null,
            'gateway' => 'stripe',
            'gateway_customer_id' => $customerId,
            'gateway_subscription_id' => $subscriptionId,
        ])->save();

        return $subscription;
    }

    /** Record a paid Stripe invoice locally (initial + renewals). */
    public function recordInvoice(array $stripeInvoice): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $this->invoiceSubscriptionId($stripeInvoice))->first();

        if (! $subscription) {
            return;
        }

        if (Invoice::withoutGlobalScope('company')->where('gateway_reference', $stripeInvoice['id'])->exists()) {
            return; // webhook retries must stay idempotent
        }

        Invoice::withoutGlobalScope('company')->create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'number' => $stripeInvoice['number'] ?? ('INV-'.now()->format('Ym').'-'.strtoupper(Str::random(6))),
            'amount' => ($stripeInvoice['amount_paid'] ?? 0) / 100,
            'currency' => strtoupper($stripeInvoice['currency'] ?? config('mapx.billing.currency')),
            'branch_count' => (int) ($stripeInvoice['lines']['data'][0]['quantity'] ?? 1),
            'period_start' => isset($stripeInvoice['period_start']) ? date('Y-m-d', $stripeInvoice['period_start']) : now()->toDateString(),
            'period_end' => isset($stripeInvoice['period_end']) ? date('Y-m-d', $stripeInvoice['period_end']) : null,
            'status' => 'paid',
            'paid_at' => now(),
            'gateway' => 'stripe',
            'gateway_reference' => $stripeInvoice['id'],
        ]);

        // A paid renewal extends the current period.
        $subscription->update([
            'status' => 'active',
            'current_period_end' => $subscription->plan === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);
    }

    public function markPastDue(array $stripeInvoice): void
    {
        Subscription::where('gateway_subscription_id', $this->invoiceSubscriptionId($stripeInvoice))
            ->update(['status' => 'past_due']);
    }

    public function markCanceled(array $stripeSubscription): void
    {
        Subscription::where('gateway_subscription_id', $stripeSubscription['id'] ?? null)
            ->update(['status' => 'canceled', 'canceled_at' => now()]);
    }

    private function invoiceSubscriptionId(array $stripeInvoice): ?string
    {
        // Basil-era API moved `subscription` under `parent.subscription_details`.
        return $stripeInvoice['subscription']
            ?? $stripeInvoice['parent']['subscription_details']['subscription']
            ?? null;
    }
}
