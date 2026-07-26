<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Support\Str;

/**
 * FR-28..31: 99 SAR / branch / month, 20 % yearly discount, 7-day trial
 * capped at 3 branches, feature lock on failed payment.
 *
 * This is the single source of truth for what a plan costs; drivers under
 * App\Services\Billing ask it rather than recomputing the maths. "manual"
 * marks invoices paid immediately (dev/demo); real drivers settle through
 * their own checkout and webhook.
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

    /** What one billing period of `$plan` costs for `$branchCount` branches. */
    public function amountFor(string $plan, int $branchCount): float
    {
        return $plan === 'yearly'
            ? $this->yearlyPrice($branchCount)
            : $this->monthlyPrice($branchCount);
    }

    /** Minor currency units (halalas for SAR) — what card gateways charge in. */
    public function minorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /** Billable branches for a company: never zero, so a new account can pay. */
    public function billableBranchCount(Company $company): int
    {
        return max(1, Branch::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->count());
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
        $branchCount = $this->billableBranchCount($company);
        $isYearly = $plan === 'yearly';
        $amount = $this->amountFor($plan, $branchCount);
        $gateway = app(PaymentGatewayManager::class)->active();

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
            'gateway' => $gateway->key(),
        ]);

        // Manual gateway (dev/demo) settles immediately; real gateways settle
        // via their checkout callback and webhook. Note this asks the manager
        // for the *resolved* driver, not the raw config value — MAPX_BILLING_
        // GATEWAY may name a driver whose credentials are missing.
        if ($gateway->key() === 'manual') {
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
