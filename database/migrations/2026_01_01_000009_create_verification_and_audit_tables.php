<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->timestamps();
        });

        Schema::create('verification_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->index();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_support')->default(false);
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('verification_messages');
        Schema::dropIfExists('verification_requests');
    }
};
