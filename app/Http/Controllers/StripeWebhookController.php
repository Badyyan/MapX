<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeGateway;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Webhook;

/**
 * Stripe webhooks keep local billing state truthful even when the customer
 * never returns to the success page: activations, renewals, failures,
 * cancellations (FR-31 feature lock relies on this).
 *
 * The endpoint is public and unauthenticated, and `checkout.session.completed`
 * activates whatever company its metadata names — so an unverified payload is
 * a free subscription for anyone who can POST. It therefore FAILS CLOSED: no
 * signing secret means 401, not "trust the body". The only exception is the
 * test suite, which needs to post raw fixtures; see allowsUnverified().
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeGateway $gateway)
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret && ! $this->allowsUnverified()) {
            return response()->json(['error' => 'Webhook signing secret is not configured'], 401);
        }

        try {
            $event = $secret
                ? Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret)
                : Event::constructFrom(json_decode($request->getContent(), true) ?? []);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid payload or signature'], 400);
        }

        $object = $event->data->object->toArray();

        match ($event->type) {
            'checkout.session.completed' => $gateway->activate(
                (int) ($object['metadata']['company_id'] ?? $object['client_reference_id'] ?? 0),
                $object['metadata']['plan'] ?? 'monthly',
                (string) ($object['customer'] ?? ''),
                (string) ($object['subscription'] ?? ''),
            ),
            'invoice.paid' => $gateway->recordInvoice($object),
            'invoice.payment_failed' => $gateway->markPastDue($object),
            'customer.subscription.deleted' => $gateway->markCanceled($object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * Whether an unsigned payload may be processed. Defaults to "only under
     * PHPUnit", and is a config value rather than a bare runningUnitTests()
     * check so a test can switch it off and prove the production path.
     */
    private function allowsUnverified(): bool
    {
        return (bool) config('services.stripe.allow_unverified_webhooks', app()->runningUnitTests());
    }
}
