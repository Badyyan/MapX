<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\Review;
use App\Services\AnalyticsService;

class DashboardController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('dashboard', [
            'healthScore' => $analytics->presenceHealthScore(),
            'reviewKpis' => $analytics->reviewKpis(),
            'qrFunnel' => $analytics->qrFunnel(),
            'actionTotals' => $analytics->actionTotals(30),
            'ratingTrend' => $analytics->ratingTrend(6),
            'sentiment' => $analytics->sentimentDistribution(),
            'branchCount' => Branch::count(),
            'syncedConnections' => PlatformConnection::where('sync_status', 'synced')->count(),
            'totalConnections' => PlatformConnection::count(),
            'recentReviews' => Review::with('branch')->latest('review_date')->limit(6)->get(),
            'scheduledPosts' => Post::where('status', 'scheduled')->orderBy('scheduled_at')->limit(5)->get(),
            'subscription' => auth()->user()->company?->subscription,
        ]);
    }
}
