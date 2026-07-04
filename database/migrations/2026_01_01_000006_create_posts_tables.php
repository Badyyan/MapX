<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('user_id')->nullable();
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('image_path')->nullable();
            $table->json('platforms');
            $table->json('branch_ids');
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->string('cta_type')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamps();
        });

        Schema::create('post_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('platform');
            $table->string('status')->default('pending');
            $table->string('external_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_statuses');
        Schema::dropIfExists('posts');
    }
};
