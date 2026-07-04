<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->nullable()->index();
            $table->string('platform');
            $table->string('status')->default('disconnected');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('external_id')->nullable();
            $table->string('sync_status')->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedTinyInteger('data_completeness')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'branch_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_connections');
    }
};
