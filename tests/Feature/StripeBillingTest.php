<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\StripeGateway;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );
    }

    public function test_sandbox_subscribe_when_stripe_not_configured(): void
    {
        config(['services.stripe.secret' => null]);

        $this->actingAs($this->admin)
            ->post('/billing/subscribe', ['plan' => 'monthly'])
            ->assertRedirect(route('billing.index'));

        $this->assertSame('active', $this->admin->company->fresh()->subscription->status);
    }

    public function test_webhook_activates_subscription_from_checkout_session(): void
    {
        // No webhook secret configured -> handler parses without signature
        // (tests only; production always sets STRIPE_WEBHOOK_SECRET).
        config(['services.stripe.webhook_secret' => null]);

        $payload = [
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'object' => 'checkout.session',
                'client_reference_id' => (string) $this->admin->company_id,
                'customer' => 'cus_123',
                'subscription' => 'sub_123',
                'metadata' => ['company_id' => (string) $this->admin->company_id, 'plan' => 'yearly'],
            ]],
        ];

        $this->postJson('/webhooks/stripe', $payload)->assertOk();

        $subscription = $this->admin->company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);
        $this->assertSame('yearly', $subscription->plan);
        $this->assertSame('sub_123', $subscription->gateway_subscription_id);
        $this->assertNull($subscription->branch_limit); // trial cap lifted
    }

    public function test_webhook_payment_failed_locks_subscription(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $this->admin->company->subscription->update([
            'status' => 'active',
            'gateway' => 'stripe',
            'gateway_subscription_id' => 'sub_123',
        ]);

        $this->postJson('/webhooks/stripe', [
            'id' => 'evt_2',
            'object' => 'event',
            'type' => 'invoice.payment_failed',
            'data' => ['object' => ['object' => 'invoice', 'subscription' => 'sub_123']],
        ])->assertOk();

        $this->assertSame('past_due', Subscription::find($this->admin->company->subscription->id)->status);
    }

    public function test_webhook_invoice_paid_records_invoice_idempotently(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $this->admin->company->subscription->update([
            'status' => 'active',
            'plan' => 'monthly',
            'gateway' => 'stripe',
            'gateway_subscription_id' => 'sub_123',
        ]);

        $payload = [
            'id' => 'evt_3',
            'object' => 'event',
            'type' => 'invoice.paid',
            'data' => ['object' => [
                'object' => 'invoice',
                'id' => 'in_123',
                'number' => 'ACME-0001',
                'amount_paid' => 29700,
                'currency' => 'sar',
                'subscription' => 'sub_123',
                'lines' => ['data' => [['quantity' => 3]]],
            ]],
        ];

        $this->postJson('/webhooks/stripe', $payload)->assertOk();
        $this->postJson('/webhooks/stripe', $payload)->assertOk(); // retry

        $this->assertSame(1, Invoice::withoutGlobalScope('company')->where('gateway_reference', 'in_123')->count());
        $invoice = Invoice::withoutGlobalScope('company')->where('gateway_reference', 'in_123')->first();
        $this->assertSame(297.0, $invoice->amount);
        $this->assertSame(3, $invoice->branch_count);
    }

    public function test_gateway_configured_detection(): void
    {
        // isConfigured() is an instance method now that drivers implement the
        // PaymentGateway contract — PHP interfaces can't declare statics.
        $gateway = app(StripeGateway::class);

        config(['services.stripe.secret' => null]);
        $this->assertFalse($gateway->isConfigured());

        config(['services.stripe.secret' => 'sk_test_x']);
        $this->assertTrue($gateway->isConfigured());
    }
}
