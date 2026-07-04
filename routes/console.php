<?php

use App\Jobs\FetchPlatformReviews;
use App\Jobs\PublishPost;
use App\Jobs\TrackLocalRanks;
use App\Models\Company;
use App\Models\PlatformConnection;
use App\Models\Post;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-12: pull reviews for every connected platform daily (webhooks cover
// real-time where the platform supports them).
Schedule::call(function () {
    PlatformConnection::withoutGlobalScope('company')
        ->where('status', 'connected')
        ->each(fn ($connection) => FetchPlatformReviews::dispatch($connection));
})->dailyAt('03:00')->name('fetch-reviews')->onOneServer();

// FR-23: release scheduled posts whose time has come.
Schedule::call(function () {
    Post::withoutGlobalScope('company')
        ->where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->each(fn ($post) => PublishPost::dispatch($post));
})->everyMinute()->name('publish-scheduled-posts')->onOneServer();

// FR-26: daily local rank snapshots per company.
Schedule::call(function () {
    Company::query()->pluck('id')
        ->each(fn ($id) => TrackLocalRanks::dispatch($id));
})->dailyAt('04:00')->name('track-local-ranks')->onOneServer();

// FR-31: expire trials and lock past-due subscriptions.
Schedule::call(function () {
    \App\Models\Subscription::where('status', 'trialing')
        ->where('trial_ends_at', '<', now())
        ->update(['status' => 'locked']);

    \App\Models\Subscription::where('status', 'active')
        ->where('current_period_end', '<', now()->subDays(3))
        ->update(['status' => 'past_due']);
})->hourly()->name('billing-lifecycle')->onOneServer();
