<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moyasar has no recurring subscriptions, so MapX charges renewals itself
 * from a saved card token and tracks its own dunning attempts.
 *
 * The token gets a dedicated column rather than reusing gateway_customer_id:
 * that column already holds plaintext Stripe `cus_` values in production, so
 * adding an `encrypted` cast to it would corrupt existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->text('payment_token')->nullable()->after('gateway_subscription_id');
            $table->unsignedTinyInteger('renewal_attempts')->default(0)->after('payment_token');
            $table->timestamp('last_renewal_attempt_at')->nullable()->after('renewal_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_token', 'renewal_attempts', 'last_renewal_attempt_at']);
        });
    }
};
