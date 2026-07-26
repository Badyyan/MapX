<?php

namespace Tests\Feature;

use App\Jobs\ChargeSubscriptionRenewal;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Moyasar is the gateway that actually takes money in Saudi Arabia (mada,
 * Apple Pay), so these cover the two things Stripe's hosted checkout gave us
 * for free and the embedded form does not: server-side amount verification,
 * and renewals that MapX has to drive itself.
 */
class MoyasarBillingTest extends TestCase
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

        config([
            'mapx.billing.gateway' => 'moyasar',
            'services.moyasar.publishable_key' => 'pk_test_x',
            'services.moyasar.secret_key' => 'sk_test_x',
            'services.moyasar.webhook_secret' => null,
        ]);
    }

    /** One branch on the monthly plan: 99.00 SAR, i.e. 9900 halalas. */
    private function paidPayment(array $overrides = []): array
    {
        return array_replace([
            'id' => 'pay_123',
            'status' => 'paid',
            'amount' => 9900,
            'currency' => 'SAR',
            'metadata' => [
                'company_id' => (string) $this->admin->company_id,
                'plan' => 'monthly',
                'branch_count' => '1',
            ],
            'source' => ['type' => 'creditcard', 'token' => 'token_abc'],
        ], $overrides);
    }

    public function test_subscribe_redirects_to_the_embedded_checkout(): void
    {
        $this->actingAs($this->admin)
            ->post('/billing/subscribe', ['plan' => 'monthly'])
            ->assertRedirect(route('billing.checkout', ['plan' => 'monthly']));

        // Nothing is activated until the payment is confirmed.
        $this->assertSame('trialing', $this->admin->company->fresh()->subscription->status);
    }

    public function test_checkout_exposes_the_publishable_key_but_never_the_secret(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('billing.checkout', ['plan' => 'monthly']))
            ->assertOk();

        $response->assertSee('pk_test_x');
        $response->assertSee('9900', false);   // amount in halalas
        $response->assertDontSee('sk_test_x'); // the secret key must never reach the browser

        // The form and its stylesheet load from Moyasar's CDN — the @push
        // targets need matching @stack hooks in the layout or the card fields
        // silently never render.
        $response->assertSee('moyasar.umd.js', false);
        $response->assertSee('moyasar.css', false);
    }

    public function test_checkout_stays_reachable_while_the_subscription_is_locked(): void
    {
        // FR-31: a locked account must still be able to pay its way out.
        $this->admin->company->subscription->update([
            'status' => 'locked',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect(route('billing.index'));
        $this->actingAs($this->admin)
            ->get(route('billing.checkout', ['plan' => 'monthly']))
            ->assertOk();
    }

    public function test_callback_reverifies_the_payment_with_moyasar_before_activating(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment())]);

        $this->actingAs($this->admin)
            ->get(route('billing.success', ['id' => 'pay_123']))
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('success');

        $subscription = $this->admin->company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);
        $this->assertSame('monthly', $subscription->plan);
        $this->assertNull($subscription->branch_limit);          // trial cap lifted
        $this->assertSame('token_abc', $subscription->payment_token); // saved for renewals

        $invoice = Invoice::withoutGlobalScope('company')->where('gateway_reference', 'pay_123')->first();
        $this->assertNotNull($invoice);
        $this->assertSame(99.0, $invoice->amount);
        $this->assertSame('moyasar', $invoice->gateway);

        // The id from the query string is never trusted on its own.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/payments/pay_123'));
    }

    public function test_callback_ignores_a_payment_that_did_not_succeed(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment(['status' => 'failed']))]);

        $this->actingAs($this->admin)
            ->get(route('billing.success', ['id' => 'pay_123']))
            ->assertSessionHas('error');

        $this->assertSame('trialing', $this->admin->company->fresh()->subscription->status);
    }

    public function test_a_tampered_amount_is_refused(): void
    {
        // The embedded form sets the amount in browser JS, so a customer can
        // edit it before submitting. 1.00 SAR must not buy a subscription.
        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment(['amount' => 100]))]);

        $this->actingAs($this->admin)
            ->get(route('billing.success', ['id' => 'pay_123']))
            ->assertSessionHas('error');

        $this->assertSame('trialing', $this->admin->company->fresh()->subscription->status);
        $this->assertSame(0, Invoice::withoutGlobalScope('company')->count());
    }

    public function test_amount_is_checked_against_the_current_branch_count(): void
    {
        Branch::create(['company_id' => $this->admin->company_id, 'name' => 'B1']);
        Branch::create(['company_id' => $this->admin->company_id, 'name' => 'B2']);

        // 2 branches monthly = 198.00 SAR; the one-branch price is now wrong.
        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment(['amount' => 19800]))]);

        $this->actingAs($this->admin)->get(route('billing.success', ['id' => 'pay_123']));

        $subscription = $this->admin->company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);
        $this->assertSame(198.0, Invoice::withoutGlobalScope('company')->first()->amount);
        $this->assertSame(2, Invoice::withoutGlobalScope('company')->first()->branch_count);
    }

    public function test_webhook_records_the_invoice_idempotently(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment())]);

        $payload = ['type' => 'payment_paid', 'data' => ['id' => 'pay_123']];

        $this->postJson('/webhooks/moyasar', $payload)->assertOk();
        $this->postJson('/webhooks/moyasar', $payload)->assertOk(); // retry

        $this->assertSame(1, Invoice::withoutGlobalScope('company')->where('gateway_reference', 'pay_123')->count());
        $this->assertSame('active', $this->admin->company->fresh()->subscription->status);
    }

    public function test_webhook_with_a_wrong_secret_token_is_rejected(): void
    {
        config(['services.moyasar.webhook_secret' => 'whsec_real']);

        $this->postJson('/webhooks/moyasar', [
            'type' => 'payment_paid',
            'secret_token' => 'whsec_guess',
            'data' => ['id' => 'pay_123'],
        ])->assertStatus(401);

        $this->assertSame('trialing', $this->admin->company->fresh()->subscription->status);
        Http::assertNothingSent();
    }

    public function test_webhook_failure_marks_a_paying_subscription_past_due(): void
    {
        $this->admin->company->subscription->update(['status' => 'active', 'gateway' => 'moyasar']);

        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment(['status' => 'failed']))]);

        $this->postJson('/webhooks/moyasar', ['type' => 'payment_failed', 'data' => ['id' => 'pay_123']])
            ->assertOk();

        $this->assertSame('past_due', $this->admin->company->fresh()->subscription->status);
    }

    public function test_renewal_charges_the_saved_token_and_extends_the_period(): void
    {
        $subscription = $this->activeMoyasarSubscription();

        Http::fake(['api.moyasar.com/*' => Http::response($this->paidPayment([
            'id' => 'pay_renew_1',
            'metadata' => ['company_id' => (string) $this->admin->company_id, 'plan' => 'monthly', 'renewal' => '1'],
        ]))]);

        app()->call([new ChargeSubscriptionRenewal($subscription->id), 'handle']);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->current_period_end->isFuture());
        $this->assertSame(0, $subscription->renewal_attempts);
        $this->assertSame(99.0, Invoice::withoutGlobalScope('company')->where('gateway_reference', 'pay_renew_1')->first()->amount);

        // The saved token is what gets charged — no card data is re-collected.
        Http::assertSent(fn ($request) => $request['source']['type'] === 'token'
            && $request['source']['token'] === 'token_abc'
            && $request['amount'] === 9900);
    }

    public function test_renewal_failures_count_up_before_locking_the_account(): void
    {
        config(['mapx.billing.renewal.max_attempts' => 2]);

        $subscription = $this->activeMoyasarSubscription();

        Http::fake(['api.moyasar.com/*' => Http::response(['message' => 'Declined'], 400)]);

        app()->call([new ChargeSubscriptionRenewal($subscription->id), 'handle']);

        // First decline must not cut off a paying customer.
        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertSame(1, $subscription->renewal_attempts);

        $subscription->update(['last_renewal_attempt_at' => now()->subDays(2)]);
        app()->call([new ChargeSubscriptionRenewal($subscription->id), 'handle']);

        $subscription->refresh();
        $this->assertSame('past_due', $subscription->status);
        $this->assertSame(2, $subscription->renewal_attempts);
    }

    public function test_canceling_drops_the_saved_card_so_nothing_is_charged_again(): void
    {
        $subscription = $this->activeMoyasarSubscription();

        $this->actingAs($this->admin)->post('/billing/cancel')->assertRedirect();

        $subscription->refresh();
        $this->assertSame('canceled', $subscription->status);
        $this->assertNull($subscription->payment_token);
        Http::assertNothingSent(); // Moyasar charges are one-off; nothing to cancel remotely
    }

    public function test_moyasar_selected_without_keys_falls_back_to_sandbox_billing(): void
    {
        config(['services.moyasar.publishable_key' => null, 'services.moyasar.secret_key' => null]);

        $this->actingAs($this->admin)
            ->post('/billing/subscribe', ['plan' => 'monthly'])
            ->assertRedirect(route('billing.index'));

        $this->assertSame('active', $this->admin->company->fresh()->subscription->status);
        $this->assertSame('paid', Invoice::withoutGlobalScope('company')->first()->status);
        $this->actingAs($this->admin)->get('/billing')->assertSee('Sandbox billing', false);
    }

    private function activeMoyasarSubscription(): Subscription
    {
        $subscription = $this->admin->company->subscription;

        $subscription->update([
            'status' => 'active',
            'plan' => 'monthly',
            'branch_limit' => null,
            'gateway' => 'moyasar',
            'payment_token' => 'token_abc',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);

        return $subscription;
    }
}
