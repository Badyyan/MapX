<?php

namespace Tests\Feature;

use App\Services\Billing\ManualGateway;
use App\Services\Billing\MoyasarGateway;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Billing\StripeGateway;
use Tests\TestCase;

/**
 * Driver resolution is the piece that decides whether real money moves, so
 * every rule in PaymentGatewayManager gets a test — including the ones that
 * exist purely to stop an existing install from changing behaviour.
 */
class PaymentGatewayManagerTest extends TestCase
{
    private function manager(): PaymentGatewayManager
    {
        return app(PaymentGatewayManager::class);
    }

    private function clearCredentials(): void
    {
        config([
            'services.stripe.secret' => null,
            'services.moyasar.publishable_key' => null,
            'services.moyasar.secret_key' => null,
        ]);
    }

    public function test_auto_falls_back_to_sandbox_when_nothing_is_configured(): void
    {
        $this->clearCredentials();
        config(['mapx.billing.gateway' => 'auto']);

        $this->assertInstanceOf(ManualGateway::class, $this->manager()->active());
    }

    public function test_auto_picks_stripe_when_only_stripe_has_keys(): void
    {
        // Reproduces today's behaviour for installs that never set
        // MAPX_BILLING_GATEWAY but do have STRIPE_SECRET.
        $this->clearCredentials();
        config(['mapx.billing.gateway' => 'auto', 'services.stripe.secret' => 'sk_test_x']);

        $this->assertInstanceOf(StripeGateway::class, $this->manager()->active());
    }

    public function test_auto_prefers_moyasar_when_both_are_configured(): void
    {
        // MapX sells into Saudi Arabia, where mada and Apple Pay matter more
        // than international cards.
        config([
            'mapx.billing.gateway' => 'auto',
            'services.stripe.secret' => 'sk_test_x',
            'services.moyasar.publishable_key' => 'pk_test_x',
            'services.moyasar.secret_key' => 'sk_test_x',
        ]);

        $this->assertInstanceOf(MoyasarGateway::class, $this->manager()->active());
    }

    public function test_an_explicit_driver_wins_over_auto_detection(): void
    {
        config([
            'mapx.billing.gateway' => 'stripe',
            'services.stripe.secret' => 'sk_test_x',
            'services.moyasar.publishable_key' => 'pk_test_x',
            'services.moyasar.secret_key' => 'sk_test_x',
        ]);

        $this->assertInstanceOf(StripeGateway::class, $this->manager()->active());
    }

    public function test_an_explicit_driver_without_credentials_degrades_to_sandbox(): void
    {
        // A half-finished setup must produce honest sandbox billing, not a
        // 500 at the subscribe button.
        $this->clearCredentials();
        config(['mapx.billing.gateway' => 'moyasar']);

        $this->assertInstanceOf(ManualGateway::class, $this->manager()->active());
    }

    public function test_an_unknown_driver_name_degrades_to_sandbox(): void
    {
        $this->clearCredentials();
        config(['mapx.billing.gateway' => 'hyperpay']); // the old, never-implemented value

        $this->assertInstanceOf(ManualGateway::class, $this->manager()->active());
    }

    public function test_driver_returns_the_real_driver_regardless_of_credentials(): void
    {
        // Webhooks must reach the driver that sent them even when the active
        // gateway has since changed or lost its keys.
        $this->clearCredentials();

        $this->assertInstanceOf(MoyasarGateway::class, $this->manager()->driver('moyasar'));
        $this->assertInstanceOf(StripeGateway::class, $this->manager()->driver('stripe'));
    }
}
