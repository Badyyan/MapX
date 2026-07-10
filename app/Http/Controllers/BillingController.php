<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Billing\StripeGateway;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request, BillingService $billing)
    {
        $company = $request->user()->company;
        $branchCount = max(1, Branch::count());

        return view('billing.index', [
            'subscription' => $company->subscription,
            'invoices' => Invoice::latest()->paginate(10),
            'branchCount' => $branchCount,
            'monthlyPrice' => $billing->monthlyPrice($branchCount),
            'yearlyPrice' => $billing->yearlyPrice($branchCount),
            'pricePerBranch' => config('mapx.billing.price_per_branch'),
            'currency' => config('mapx.billing.currency'),
            'gatewayConfigured' => StripeGateway::isConfigured(),
        ]);
    }

    public function subscribe(Request $request, BillingService $billing)
    {
        $request->validate(['plan' => ['required', 'in:monthly,yearly']]);

        // Real gateway: send the customer to Stripe's hosted, 3DS-verified
        // checkout. Local state is only activated by the webhook / success
        // callback once payment is actually confirmed.
        if (StripeGateway::isConfigured()) {
            $url = app(StripeGateway::class)->checkoutUrl($request->user()->company, $request->plan);

            AuditLog::record('billing.checkout_started', null, ['plan' => $request->plan]);

            return redirect()->away($url);
        }

        // Sandbox fallback (no gateway configured): activate immediately but
        // say so, loudly, in the UI.
        $invoice = $billing->subscribe($request->user()->company, $request->plan);
        AuditLog::record('billing.subscribed_sandbox', $invoice, ['plan' => $request->plan]);

        return redirect()->route('billing.index')
            ->with('success', __('Sandbox subscription activated (no payment gateway configured). Invoice :number recorded.', ['number' => $invoice->number]));
    }

    /** Stripe Checkout success return. */
    public function success(Request $request)
    {
        if ($request->query('session_id') && StripeGateway::isConfigured()) {
            $subscription = app(StripeGateway::class)->activateFromSession($request->query('session_id'));

            if ($subscription) {
                AuditLog::record('billing.subscribed', $subscription, ['plan' => $subscription->plan]);

                return redirect()->route('billing.index')
                    ->with('success', __('Payment confirmed — your :plan subscription is active.', ['plan' => __(ucfirst($subscription->plan))]));
            }
        }

        return redirect()->route('billing.index')
            ->with('error', __('Payment could not be confirmed. If you were charged, it will activate automatically within a minute.'));
    }

    public function cancel(Request $request, BillingService $billing)
    {
        $subscription = $request->user()->company->subscription;

        abort_unless($subscription, 404);

        $billing->cancel($subscription);
        AuditLog::record('billing.canceled', $subscription);

        return back()->with('success', __('Subscription canceled.'));
    }
}
