<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Str;

/**
 * FR-28..31: 99 SAR / branch / month, 20 % yearly discount, 7-day trial
 * capped at 3 branches, feature lock on failed payment. The gateway is an
 * abstraction point — "manual" marks invoices paid immediately (dev/demo),
 * stripe/hyperpay drivers plug in behind the same interface.
 */
class BillingService
{
    public function monthlyPrice(int $branchCount): float
    {
        return round($branchCount * config('mapx.billing.price_per_branch'), 2);
    }

    public function yearlyPrice(int $branchCount): float
    {
        return round($this->monthlyPrice($branchCount) * 12 * (1 - config('mapx.billing.yearly_discount')), 2);
    }

    /** Whether the company may add another branch under its current plan. */
    public function canAddBranch(Company $company): bool
    {
        $subscription = $company->subscription;
        if (! $subscription) {
            return false;
        }

        if ($subscription->branch_limit === null) {
            return true;
        }

        $current = Branch::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->count();

        return $current < $subscription->branch_limit;
    }

    /** Upgrade from trial (or renew) to a paid plan. */
    public function subscribe(Company $company, string $plan): Invoice
    {
        $branchCount = max(1, Branch::withoutGlobalScope('company')->where('company_id', $company->id)->count());
        $isYearly = $plan === 'yearly';
        $amount = $isYearly ? $this->yearlyPrice($branchCount) : $this->monthlyPrice($branchCount);

        $subscription = $company->subscription ?? new Subscription(['company_id' => $company->id]);
        $subscription->fill([
            'company_id' => $company->id,
            'plan' => $plan,
            'status' => 'active',
            'branch_limit' => null,
            'price_per_branch' => config('mapx.billing.price_per_branch'),
            'currency' => config('mapx.billing.currency'),
            'current_period_start' => now(),
            'current_period_end' => $isYearly ? now()->addYear() : now()->addMonth(),
            'canceled_at' => null,
        ])->save();

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'number' => 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'amount' => $amount,
            'currency' => config('mapx.billing.currency'),
            'branch_count' => $branchCount,
            'period_start' => now()->toDateString(),
            'period_end' => $subscription->current_period_end->toDateString(),
            'status' => 'pending',
            'gateway' => config('mapx.billing.gateway'),
        ]);

        // Manual gateway (dev/demo) settles immediately; real gateways settle
        // via their webhook, which calls markPaid()/markFailed().
        if (config('mapx.billing.gateway') === 'manual') {
            $this->markPaid($invoice, 'manual-'.Str::random(8));
        }

        return $invoice;
    }

    public function markPaid(Invoice $invoice, string $reference): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'gateway_reference' => $reference,
        ]);
    }

    public function markFailed(Invoice $invoice): void
    {
        $invoice->update(['status' => 'failed']);

        $invoice->subscription?->update(['status' => 'past_due']);
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }
}
