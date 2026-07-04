<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('platform');
            $table->string('metric');
            $table->unsignedInteger('value')->default(0);
            $table->date('date')->index();
            $table->timestamps();
            $table->unique(['branch_id', 'platform', 'metric', 'date']);
        });

        Schema::create('local_rank_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('branch_id')->index();
            $table->string('keyword');
            $table->string('platform')->default('google');
            $table->unsignedSmallInteger('position')->nullable();
            $table->decimal('radius_km', 5, 1)->default(5);
            $table->date('tracked_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('tracked_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->string('keyword');
            $table->decimal('radius_km', 5, 1)->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_keywords');
        Schema::dropIfExists('local_rank_snapshots');
        Schema::dropIfExists('presence_metrics');
    }
};
