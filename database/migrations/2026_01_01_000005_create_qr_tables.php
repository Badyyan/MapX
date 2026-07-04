<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('rating_scale')->default(5);
            $table->unsignedTinyInteger('threshold')->default(4);
            $table->string('positive_redirect_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('scans_count')->default(0);
            $table->unsignedInteger('positive_count')->default(0);
            $table->unsignedInteger('negative_count')->default(0);
            $table->timestamps();
        });

        Schema::create('qr_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_campaign_id')->index();
            $table->foreignId('branch_id')->index();
            $table->foreignId('company_id')->index();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_feedback');
        Schema::dropIfExists('qr_campaigns');
    }
};
