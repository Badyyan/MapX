<?php

namespace App\Http\Controllers;

use App\Services\Billing\MoyasarClient;
use App\Services\Billing\MoyasarGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar webhooks keep billing truthful when the customer never returns to
 * the success page — closed tab, dead battery, 3-D Secure taking too long.
 *
 * Two independent defences, because a webhook URL is public:
 *
 *  1. The shared secret Moyasar puts in the body (`secret_token`, set in the
 *     merchant dashboard) is compared with hash_equals. Unlike the Stripe
 *     handler, this one FAILS CLOSED — a missing or wrong secret is a 401 in
 *     every environment except the test suite.
 *  2. The payment is re-read from the Moyasar API by id before any state
 *     changes, so even a perfectly forged body cannot invent a paid payment.
 *
 * @see https://docs.moyasar.com/api/other/webhooks/webhook-reference/
 */
class MoyasarWebhookController extends Controller
{
    public function handle(Request $request, MoyasarGateway $gateway, MoyasarClient $client)
    {
        if (! $this->verified($request)) {
            return response()->json(['error' => 'Invalid secret token'], 401);
        }

        $payload = $request->json()->all();
        $paymentId = $payload['data']['id'] ?? $payload['id'] ?? null;

        if (! $paymentId) {
            return response()->json(['error' => 'No payment id'], 400);
        }

        try {
            $payment = $client->fetchPayment((string) $paymentId);
        } catch (\Throwable $e) {
            Log::warning('Moyasar webhook lookup failed: '.$e->getMessage(), ['payment' => $paymentId]);

            // 5xx asks Moyasar to retry; the transient failure is ours.
            return response()->json(['error' => 'Lookup failed'], 502);
        }

        if ($payment) {
            $gateway->applyPayment($payment);
        }

        return response()->json(['received' => true]);
    }

    private function verified(Request $request): bool
    {
        $secret = config('services.moyasar.webhook_secret');

        if (! $secret) {
            // No secret configured: only the test suite may pass unverified.
            // Production without a secret is a misconfiguration, not a mode.
            return $this->allowsUnverified();
        }

        $token = $request->json('secret_token')
            ?? $request->header('X-Moyasar-Secret-Token', '');

        return is_string($token) && hash_equals($secret, $token);
    }

    /**
     * Whether an unsigned payload may be processed. Defaults to "only under
     * PHPUnit", and is a config value rather than a bare runningUnitTests()
     * check so a test can switch it off and prove the production path.
     */
    private function allowsUnverified(): bool
    {
        return (bool) config('services.moyasar.allow_unverified_webhooks', app()->runningUnitTests());
    }
}
