<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Billing\MoyasarGateway;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function index(Request $request, BillingService $billing)
    {
        $company = $request->user()->company;
        $branchCount = max(1, Branch::count());
        $gateway = $this->gateways->active();

        return view('billing.index', [
            'subscription' => $company->subscription,
            'invoices' => Invoice::latest()->paginate(10),
            'branchCount' => $branchCount,
            'monthlyPrice' => $billing->monthlyPrice($branchCount),
            'yearlyPrice' => $billing->yearlyPrice($branchCount),
            'pricePerBranch' => config('mapx.billing.price_per_branch'),
            'currency' => config('mapx.billing.currency'),
            'gatewayConfigured' => $gateway->key() !== 'manual',
            'gatewayKey' => $gateway->key(),
        ]);
    }

    public function subscribe(Request $request, BillingService $billing)
    {
        $request->validate(['plan' => ['required', 'in:monthly,yearly']]);

        $gateway = $this->gateways->active();

        // Real gateway: send the customer to a 3DS-verified checkout — Stripe's
        // hosted page, or MapX's own embedded Moyasar form. Local state is only
        // activated once the payment is confirmed server-side.
        if ($gateway->key() !== 'manual') {
            $url = $gateway->checkoutUrl($request->user()->company, $request->plan);

            AuditLog::record('billing.checkout_started', null, [
                'plan' => $request->plan,
                'gateway' => $gateway->key(),
            ]);

            return redirect()->to($url);
        }

        // Sandbox fallback (no gateway configured): activate immediately but
        // say so, loudly, in the UI.
        $invoice = $billing->subscribe($request->user()->company, $request->plan);
        AuditLog::record('billing.subscribed_sandbox', $invoice, ['plan' => $request->plan]);

        return redirect()->route('billing.index')
            ->with('success', __('Sandbox subscription activated (no payment gateway configured). Invoice :number recorded.', ['number' => $invoice->number]));
    }

    /**
     * MapX-branded checkout for gateways with an embedded form (Moyasar:
     * mada, Apple Pay, credit cards). Kept outside the `subscription`
     * middleware group so a locked account can still pay (FR-31).
     */
    public function checkout(Request $request, BillingService $billing)
    {
        $request->validate(['plan' => ['required', 'in:monthly,yearly']]);

        $gateway = $this->gateways->active();

        if (! $gateway instanceof MoyasarGateway) {
            return redirect()->route('billing.index');
        }

        $company = $request->user()->company;
        $branchCount = $billing->billableBranchCount($company);
        $amount = $billing->amountFor($request->plan, $branchCount);

        return view('billing.checkout', [
            'plan' => $request->plan,
            'branchCount' => $branchCount,
            'amount' => $amount,
            'amountMinor' => $billing->minorUnits($amount),
            'currency' => config('mapx.billing.currency'),
            'pricePerBranch' => config('mapx.billing.price_per_branch'),
            'publishableKey' => config('services.moyasar.publishable_key'),
            'methods' => $gateway->enabledMethods(),
            'description' => $gateway->paymentDescription($company, $request->plan, $branchCount),
            'callbackUrl' => route('billing.success'),
            'metadata' => [
                'company_id' => (string) $company->id,
                'plan' => $request->plan,
                'branch_count' => (string) $branchCount,
            ],
        ]);
    }

    /**
     * Checkout return. Stripe sends `session_id`, Moyasar sends `id`; both are
     * untrusted query strings, so the driver re-fetches the payment before
     * anything is activated.
     */
    public function success(Request $request)
    {
        $gateway = $this->gateways->active();
        $reference = $request->query('session_id') ?: $request->query('id');

        if ($reference && $gateway->key() !== 'manual') {
            $subscription = $gateway->confirm((string) $reference);

            if ($subscription) {
                AuditLog::record('billing.subscribed', $subscription, [
                    'plan' => $subscription->plan,
                    'gateway' => $gateway->key(),
                ]);

                return redirect()->route('billing.index')
                    ->with('success', __('Payment confirmed — your :plan subscription is active.', ['plan' => __(ucfirst($subscription->plan))]));
            }
        }

        return redirect()->route('billing.index')
            ->with('error', __('Payment could not be confirmed. If you were charged, it will activate automatically within a minute.'));
    }

    public function cancel(Request $request)
    {
        $subscription = $request->user()->company->subscription;

        abort_unless($subscription, 404);

        // Cancel through the driver that owns the subscription, not the one
        // that happens to be active now — otherwise switching gateways would
        // leave the old provider charging the card.
        $this->gateways->driver($subscription->gateway ?: 'manual')->cancel($subscription);

        AuditLog::record('billing.canceled', $subscription);

        return back()->with('success', __('Subscription canceled.'));
    }
}
