<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Shared, provider-agnostic subscription state transitions.
 *
 * Every driver maps its own webhook/callback payload onto these methods, so
 * the tricky parts — webhook idempotency, the Invoice tenancy scope, period
 * extension — are written once instead of once per gateway.
 *
 * Tenancy note: Subscription has no BelongsToCompany trait, but Invoice does.
 * Webhook and queue contexts have no authenticated company, so every Invoice
 * query here must use withoutGlobalScope('company').
 */
abstract class BaseGateway implements PaymentGateway
{
    public function __construct(protected BillingService $billing) {}

    public function cancel(Subscription $subscription): void
    {
        $this->billing->cancel($subscription);
    }

    /**
     * Move a company onto a paid plan. Idempotent: re-running with the same
     * arguments simply rewrites the same active subscription.
     */
    protected function activateSubscription(
        int $companyId,
        string $plan,
        ?string $customerId = null,
        ?string $subscriptionId = null,
    ): ?Subscription {
        $company = Company::find($companyId);

        if (! $company) {
            return null;
        }

        $subscription = $company->subscription ?? new Subscription(['company_id' => $company->id]);
        $subscription->fill([
            'company_id' => $company->id,
            'plan' => $plan,
            'status' => 'active',
            'branch_limit' => null,           // the trial cap is lifted on payment
            'price_per_branch' => config('mapx.billing.price_per_branch'),
            'currency' => config('mapx.billing.currency'),
            'current_period_start' => now(),
            'current_period_end' => $this->periodEndFor($plan),
            'canceled_at' => null,
            'gateway' => $this->key(),
            // Never blank a stored token/customer just because this particular
            // payload didn't carry one — renewals depend on it surviving.
            'gateway_customer_id' => $customerId ?: $subscription->gateway_customer_id,
            'gateway_subscription_id' => $subscriptionId ?: $subscription->gateway_subscription_id,
        ])->save();

        return $subscription;
    }

    /**
     * Record a settled payment. Returns null when the reference was already
     * recorded — providers retry webhooks, and a retry must not double-bill.
     */
    protected function recordPaidInvoice(
        Subscription $subscription,
        string $reference,
        float $amount,
        ?string $currency = null,
        ?int $branchCount = null,
        ?string $number = null,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null,
    ): ?Invoice {
        if (Invoice::withoutGlobalScope('company')->where('gateway_reference', $reference)->exists()) {
            return null;
        }

        return Invoice::withoutGlobalScope('company')->create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'number' => $number ?: 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'amount' => $amount,
            'currency' => strtoupper($currency ?: config('mapx.billing.currency')),
            'branch_count' => $branchCount ?: 1,
            'period_start' => ($periodStart ?? now())->toDateString(),
            'period_end' => $periodEnd?->toDateString(),
            'status' => 'paid',
            'paid_at' => now(),
            'gateway' => $this->key(),
            'gateway_reference' => $reference,
        ]);
    }

    /** A settled payment reopens the subscription and pushes the period out. */
    protected function extendPeriod(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'current_period_end' => $this->periodEndFor($subscription->plan),
        ]);
    }

    /** FR-31: a failed payment locks platform features until it is settled. */
    protected function markPastDueSubscription(Subscription $subscription): void
    {
        $subscription->update(['status' => 'past_due']);
    }

    protected function markCanceledSubscription(Subscription $subscription): void
    {
        $subscription->update(['status' => 'canceled', 'canceled_at' => now()]);
    }

    /** Look a subscription up from a provider reference, ignoring tenancy. */
    protected function subscriptionByGatewayId(?string $gatewaySubscriptionId): ?Subscription
    {
        if (! $gatewaySubscriptionId) {
            return null;
        }

        return Subscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first();
    }

    protected function periodEndFor(string $plan): Carbon
    {
        return $plan === 'yearly' ? now()->addYear() : now()->addMonth();
    }
}
