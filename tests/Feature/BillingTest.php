<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
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

    public function test_pricing_follows_brd(): void
    {
        $billing = app(BillingService::class);

        // FR-28: 99 SAR per branch monthly, 20 % discount yearly.
        $this->assertSame(297.0, $billing->monthlyPrice(3));
        $this->assertSame(round(297 * 12 * 0.8, 2), $billing->yearlyPrice(3));
    }

    public function test_subscribing_activates_plan_and_issues_paid_invoice(): void
    {
        Branch::create(['company_id' => $this->admin->company_id, 'name' => 'B1']);
        Branch::create(['company_id' => $this->admin->company_id, 'name' => 'B2']);

        $this->actingAs($this->admin)
            ->post('/billing/subscribe', ['plan' => 'monthly'])
            ->assertRedirect(route('billing.index'));

        $subscription = $this->admin->company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->branch_limit);

        $invoice = $this->admin->company->invoices()->first();
        $this->assertSame('paid', $invoice->status); // manual gateway settles immediately
        $this->assertSame(198.0, $invoice->amount);
    }

    public function test_locked_subscription_blocks_features_but_not_billing(): void
    {
        // FR-31: features lock when the subscription lapses.
        $this->admin->company->subscription->update([
            'status' => 'locked',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect(route('billing.index'));
        $this->actingAs($this->admin)->get('/billing')->assertOk();
    }
}
