<?php

namespace App\Providers;

use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One manager per request: it memoises driver instances, which are
        // stateless, while still re-reading config on every active() call.
        $this->app->singleton(PaymentGatewayManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
