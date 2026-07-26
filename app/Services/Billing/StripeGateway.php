<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Stripe Checkout for subscriptions (FR-28..31 in real mode).
 *
 * Flow: subscribe -> hosted Stripe Checkout (card entry, 3DS verification)
 * -> webhook `checkout.session.completed` activates the local subscription
 * -> `invoice.paid` records renewals -> `invoice.payment_failed` marks
 * past_due (feature lock) -> `customer.subscription.deleted` cancels.
 *
 * Stripe owns the recurring schedule, so there is no local renewal job here
 * (unlike MoyasarGateway, which has to drive renewals itself).
 *
 * The recordInvoice()/markPastDue()/markCanceled() methods take raw
 * Stripe-shaped arrays on purpose: they are the mapping layer between
 * Stripe's payload and the normalized transitions in BaseGateway, and are
 * called only from StripeWebhookController.
 */
class StripeGateway extends BaseGateway
{
    private ?StripeClient $stripe = null;

    /** Lazy client: webhook state updates don't need API access at all. */
    private function stripe(): StripeClient
    {
        return $this->stripe ??= new StripeClient(config('services.stripe.secret'));
    }

    public function key(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    /** Create a hosted Checkout session and return its redirect URL. */
    public function checkoutUrl(Company $company, string $plan): string
    {
        $branchCount = $this->billing->billableBranchCount($company);
        $isYearly = $plan === 'yearly';

        // Stripe multiplies unit_amount by quantity, so the unit here is one
        // branch for one period — not the whole-order total.
        $unitAmount = $this->billing->minorUnits(
            $this->billing->amountFor($plan, 1)
        );

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

    /**
     * Activate the local subscription after a completed Checkout session.
     * The session id arrives on the success URL and is untrusted, so the
     * session is re-fetched from Stripe before anything is activated.
     */
    public function confirm(string $reference): ?Subscription
    {
        $session = $this->stripe()->checkout->sessions->retrieve($reference);

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
        return $this->activateSubscription($companyId, $plan, $customerId, $subscriptionId);
    }

    /** Record a paid Stripe invoice locally (initial + renewals). */
    public function recordInvoice(array $stripeInvoice): void
    {
        $subscription = $this->subscriptionByGatewayId($this->invoiceSubscriptionId($stripeInvoice));

        if (! $subscription) {
            return;
        }

        $recorded = $this->recordPaidInvoice(
            subscription: $subscription,
            reference: $stripeInvoice['id'],
            amount: ($stripeInvoice['amount_paid'] ?? 0) / 100,
            currency: $stripeInvoice['currency'] ?? null,
            branchCount: (int) ($stripeInvoice['lines']['data'][0]['quantity'] ?? 1),
            number: $stripeInvoice['number'] ?? null,
            periodStart: isset($stripeInvoice['period_start']) ? Carbon::createFromTimestamp($stripeInvoice['period_start']) : null,
            periodEnd: isset($stripeInvoice['period_end']) ? Carbon::createFromTimestamp($stripeInvoice['period_end']) : null,
        );

        if (! $recorded) {
            return; // webhook retries must stay idempotent
        }

        // A paid renewal extends the current period.
        $this->extendPeriod($subscription);
    }

    public function markPastDue(array $stripeInvoice): void
    {
        $subscription = $this->subscriptionByGatewayId($this->invoiceSubscriptionId($stripeInvoice));

        $subscription && $this->markPastDueSubscription($subscription);
    }

    public function markCanceled(array $stripeSubscription): void
    {
        $subscription = $this->subscriptionByGatewayId($stripeSubscription['id'] ?? null);

        $subscription && $this->markCanceledSubscription($subscription);
    }

    /**
     * Cancel at Stripe too — a local-only cancel would keep charging the card.
     * Remote failure never blocks the local cancel; the webhook reconciles.
     */
    public function cancel(Subscription $subscription): void
    {
        if ($this->isConfigured() && $subscription->gateway_subscription_id) {
            try {
                $this->stripe()->subscriptions->cancel($subscription->gateway_subscription_id);
            } catch (\Throwable $e) {
                Log::warning('Stripe subscription cancel failed: '.$e->getMessage());
            }
        }

        parent::cancel($subscription);
    }

    private function invoiceSubscriptionId(array $stripeInvoice): ?string
    {
        // Basil-era API moved `subscription` under `parent.subscription_details`.
        return $stripeInvoice['subscription']
            ?? $stripeInvoice['parent']['subscription_details']['subscription']
            ?? null;
    }
}
