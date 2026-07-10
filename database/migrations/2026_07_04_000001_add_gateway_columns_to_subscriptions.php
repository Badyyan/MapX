<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('canceled_at');
            $table->string('gateway_customer_id')->nullable()->after('gateway');
            $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id');
            $table->index('gateway_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['gateway_subscription_id']);
            $table->dropColumn(['gateway', 'gateway_customer_id', 'gateway_subscription_id']);
        });
    }
};
