<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('platform');
            $table->string('external_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_avatar')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->text('content')->nullable();
            $table->string('language', 5)->nullable();
            $table->string('sentiment')->default('unknown');
            $table->json('topics')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->boolean('is_replied')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'platform']);
            $table->index(['company_id', 'rating']);
            $table->unique(['platform', 'external_id']);
        });

        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->index();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('auto_reply_rule_id')->nullable();
            $table->text('content');
            $table->string('source')->default('manual');
            $table->string('status')->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('min_rating')->default(1);
            $table->unsignedTinyInteger('max_rating')->default(5);
            $table->string('sentiment')->nullable();
            $table->json('keywords')->nullable();
            $table->json('platforms')->nullable();
            $table->string('language', 5)->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->boolean('require_approval')->default(true);
            $table->json('templates');
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rules');
        Schema::dropIfExists('review_replies');
        Schema::dropIfExists('reviews');
    }
};
