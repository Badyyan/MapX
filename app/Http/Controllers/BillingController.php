<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Invoice;
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
        ]);
    }

    public function subscribe(Request $request, BillingService $billing)
    {
        $request->validate(['plan' => ['required', 'in:monthly,yearly']]);

        $invoice = $billing->subscribe($request->user()->company, $request->plan);
        AuditLog::record('billing.subscribed', $invoice, ['plan' => $request->plan]);

        return redirect()->route('billing.index')
            ->with('success', __('Subscription activated. Invoice :number issued.', ['number' => $invoice->number]));
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
