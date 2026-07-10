<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeGateway;
use Illuminate\Http\Request;
use Stripe\Webhook;

/**
 * Stripe webhooks keep local billing state truthful even when the customer
 * never returns to the success page: activations, renewals, failures,
 * cancellations (FR-31 feature lock relies on this).
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeGateway $gateway)
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = $secret
                ? Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret)
                : \Stripe\Event::constructFrom(json_decode($request->getContent(), true) ?? []);
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
}
