<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AutoReplyRuleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QrCampaignController;
use App\Http\Controllers\RankTrackerController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

/* Public QR feedback flow — customers land here from a QR code. */
Route::prefix('f')->name('feedback.')->group(function () {
    Route::get('/{slug}', [FeedbackController::class, 'show'])->name('show');
    Route::post('/{slug}/rate', [FeedbackController::class, 'rate'])->name('rate');
    Route::post('/{slug}/feedback', [FeedbackController::class, 'store'])->name('store');
    Route::get('/{slug}/thanks', [FeedbackController::class, 'thanks'])->name('thanks');
});

/* Guest auth */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/locale/{locale}', [AuthController::class, 'switchLocale'])->name('locale.switch');

    /* Billing stays reachable when the subscription is locked (FR-31). */
    Route::middleware('perm:billing.manage')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    });

    Route::middleware('subscription')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /* Branches */
        Route::middleware('perm:branches.manage')->group(function () {
            Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
            Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
            Route::post('/branches/import', [BranchController::class, 'import'])->name('branches.import');
            Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
            Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
        });
        Route::middleware('perm:branches.view')->group(function () {
            Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
            Route::get('/branches/{branch}', [BranchController::class, 'show'])->whereNumber('branch')->name('branches.show');
        });

        /* Integrations */
        Route::get('/integrations', [IntegrationController::class, 'index'])
            ->middleware('perm:integrations.view')->name('integrations.index');
        Route::middleware('perm:integrations.manage')->group(function () {
            Route::post('/integrations/connect', [IntegrationController::class, 'connect'])->name('integrations.connect');
            Route::post('/integrations/sync-all', [IntegrationController::class, 'syncAll'])->name('integrations.sync-all');
            Route::post('/integrations/{connection}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
            Route::delete('/integrations/{connection}', [IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
        });

        /* Reviews */
        Route::middleware('perm:reviews.view')->group(function () {
            Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::get('/reviews/approvals', [ReviewController::class, 'approvals'])->name('reviews.approvals');
        });
        Route::middleware('perm:reviews.reply')->group(function () {
            Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');
            Route::get('/reviews/{review}/suggest', [ReviewController::class, 'suggest'])->name('reviews.suggest');
            Route::post('/replies/{reply}/approve', [ReviewController::class, 'approveReply'])->name('replies.approve');
            Route::delete('/replies/{reply}', [ReviewController::class, 'rejectReply'])->name('replies.reject');
        });

        /* Auto-reply rules */
        Route::middleware('perm:auto_rules.manage')->group(function () {
            Route::get('/auto-rules', [AutoReplyRuleController::class, 'index'])->name('auto-rules.index');
            Route::get('/auto-rules/create', [AutoReplyRuleController::class, 'create'])->name('auto-rules.create');
            Route::post('/auto-rules', [AutoReplyRuleController::class, 'store'])->name('auto-rules.store');
            Route::get('/auto-rules/{autoRule}/edit', [AutoReplyRuleController::class, 'edit'])->name('auto-rules.edit');
            Route::put('/auto-rules/{autoRule}', [AutoReplyRuleController::class, 'update'])->name('auto-rules.update');
            Route::delete('/auto-rules/{autoRule}', [AutoReplyRuleController::class, 'destroy'])->name('auto-rules.destroy');
            Route::post('/auto-rules/{autoRule}/toggle', [AutoReplyRuleController::class, 'toggle'])->name('auto-rules.toggle');
        });

        /* QR campaigns + internal feedback */
        Route::get('/qr', [QrCampaignController::class, 'index'])->middleware('perm:qr.view')->name('qr.index');
        Route::middleware('perm:qr.manage')->group(function () {
            Route::post('/qr', [QrCampaignController::class, 'store'])->name('qr.store');
            Route::get('/qr/{qr}/svg', [QrCampaignController::class, 'svg'])->name('qr.svg');
            Route::post('/qr/{qr}/toggle', [QrCampaignController::class, 'toggle'])->name('qr.toggle');
            Route::delete('/qr/{qr}', [QrCampaignController::class, 'destroy'])->name('qr.destroy');
            Route::put('/qr-feedback/{feedback}', [QrCampaignController::class, 'updateFeedbackStatus'])->name('qr.feedback.status');
        });

        /* Posts */
        Route::middleware('perm:posts.manage')->group(function () {
            Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
            Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
            Route::post('/posts/draft', [PostController::class, 'draft'])->name('posts.draft');
            Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
            Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        });
        Route::middleware('perm:posts.view')->group(function () {
            Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
            Route::get('/posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('posts.show');
        });

        /* Analytics + rank tracking */
        Route::get('/analytics', [AnalyticsController::class, 'index'])
            ->middleware('perm:analytics.view')->name('analytics.index');
        Route::get('/rank', [RankTrackerController::class, 'index'])
            ->middleware('perm:rank.view')->name('rank.index');
        Route::middleware('perm:rank.manage')->group(function () {
            Route::post('/rank/keywords', [RankTrackerController::class, 'store'])->name('rank.keywords.store');
            Route::delete('/rank/keywords/{keyword}', [RankTrackerController::class, 'destroy'])->name('rank.keywords.destroy');
            Route::post('/rank/refresh', [RankTrackerController::class, 'refresh'])->name('rank.refresh');
        });

        /* Verification assistance */
        Route::middleware('perm:verification.manage')->group(function () {
            Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
            Route::post('/verification', [VerificationController::class, 'store'])->name('verification.store');
            Route::post('/verification/{verification}/message', [VerificationController::class, 'message'])->name('verification.message');
        });

        /* Team + roles */
        Route::middleware('perm:users.manage')->group(function () {
            Route::get('/team', [UserController::class, 'index'])->name('users.index');
            Route::post('/team', [UserController::class, 'store'])->name('users.store');
            Route::put('/team/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/team/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        Route::middleware('perm:roles.manage')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        /* Audit + settings */
        Route::get('/audit', [AuditLogController::class, 'index'])
            ->middleware('perm:audit.view')->name('audit.index');
        Route::middleware('perm:billing.manage')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
});
