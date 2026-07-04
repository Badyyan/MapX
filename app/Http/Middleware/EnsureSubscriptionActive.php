<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-31: lock features when the subscription is expired or payment failed.
 * Billing, profile and logout remain reachable so the customer can pay.
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;
        $subscription = $company?->subscription;

        if ($subscription && $subscription->isLocked()) {
            return redirect()->route('billing.index')
                ->with('error', __('Your subscription is inactive. Please renew to unlock the platform.'));
        }

        return $next($request);
    }
}
