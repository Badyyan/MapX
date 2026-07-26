<?php

namespace App\Services\Billing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client for the Moyasar Payments API.
 *
 * Moyasar publishes no official PHP SDK, so this wraps the REST API with the
 * Http facade — the same approach as App\Services\Google\GoogleOAuthService,
 * and the reason the driver is fully testable with Http::fake().
 *
 * This class is the ONLY place that touches Moyasar's wire format: base URL,
 * HTTP Basic auth (secret key as username, empty password) and minor-unit
 * amounts. If Moyasar changes a field name, it changes here and nowhere else.
 *
 * @see https://docs.moyasar.com/api/payments/01-create-payment/
 */
class MoyasarClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.moyasar.secret_key');
    }

    /**
     * Charge a payment source. Amounts are in the smallest currency unit —
     * halalas for SAR, so 99.00 SAR is 9900.
     *
     * @param  array<string, mixed>  $source
     * @param  array<string, string>  $metadata
     * @return array<string, mixed>
     */
    public function createPayment(
        int $amountMinor,
        string $description,
        array $source,
        array $metadata = [],
        ?string $callbackUrl = null,
        ?string $currency = null,
    ): array {
        $payload = array_filter([
            'amount' => $amountMinor,
            'currency' => $currency ?: config('mapx.billing.currency', 'SAR'),
            'description' => $description,
            'callback_url' => $callbackUrl,
            'source' => $source,
            'metadata' => $metadata ?: null,
        ], fn ($value) => $value !== null);

        return $this->request()->post('/payments', $payload)->throw()->json();
    }

    /**
     * Re-read a payment from Moyasar. Every state change in the app goes
     * through this: callback query strings and webhook bodies are attacker-
     * controllable, the API response is not.
     *
     * @return array<string, mixed>|null
     */
    public function fetchPayment(string $paymentId): ?array
    {
        $response = $this->request()->get("/payments/{$paymentId}");

        if ($response->status() === 404) {
            return null;
        }

        return $response->throw()->json();
    }

    private function request(): PendingRequest
    {
        $secret = config('services.moyasar.secret_key');

        if (! $secret) {
            throw new RuntimeException('Moyasar secret key is not configured.');
        }

        return Http::baseUrl(rtrim(config('services.moyasar.base_url'), '/'))
            ->withBasicAuth($secret, '')   // secret key as username, empty password
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 200, throw: false);
    }
}
