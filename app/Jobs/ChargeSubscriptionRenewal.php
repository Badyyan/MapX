<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\Billing\MoyasarGateway;
use App\Services\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Renews a Moyasar subscription by charging the saved card token.
 *
 * Stripe runs its own recurring schedule; Moyasar has no subscription
 * primitive, so MapX is the scheduler. Dispatched daily from
 * routes/console.php for subscriptions whose period has ended.
 *
 * Timing matters: the hourly `billing-lifecycle` schedule flips an active
 * subscription to past_due once it is 3 days past current_period_end, so the
 * retry ladder below has to finish inside that grace window.
 */
class ChargeSubscriptionRenewal implements ShouldQueue
{
    use Queueable;

    /** Attempts are cheap, but each one is a real card charge — keep it small. */
    public int $tries = 1;

    public function __construct(public int $subscriptionId) {}

    public function handle(MoyasarGateway $gateway, BillingService $billing): void
    {
        $subscription = Subscription::find($this->subscriptionId);

        if (! $this->isRenewable($subscription)) {
            return;
        }

        $company = $subscription->company;

        if (! $company) {
            return;
        }

        $branchCount = $billing->billableBranchCount($company);
        $amount = $billing->amountFor($subscription->plan, $branchCount);

        $payment = $gateway->chargeSavedCard($subscription, $amount, $branchCount);

        if (! $payment) {
            Log::warning('Moyasar renewal failed; subscription marked past due.', [
                'subscription' => $subscription->id,
                'company' => $subscription->company_id,
            ]);

            $gateway->markRenewalFailed($subscription);

            return;
        }

        // recordRenewal is idempotent on the payment id, so a duplicate
        // dispatch cannot bill the same charge twice.
        $gateway->recordRenewal($subscription, $payment, $branchCount);
    }

    private function isRenewable(?Subscription $subscription): bool
    {
        return $subscription
            && $subscription->gateway === 'moyasar'
            && $subscription->canceled_at === null
            && $subscription->payment_token
            && in_array($subscription->status, ['active', 'past_due'], true)
            && $subscription->current_period_end
            && $subscription->current_period_end->isPast()
            && $subscription->renewal_attempts < (int) config('mapx.billing.renewal.max_attempts', 3);
    }
}
