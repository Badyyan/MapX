<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics)
    {
        $branchId = $request->integer('branch_id') ?: null;

        return view('analytics.index', [
            'branches' => Branch::orderBy('name')->get(),
            'branchId' => $branchId,
            'healthScore' => $analytics->presenceHealthScore(),
            'reviewKpis' => $analytics->reviewKpis(),
            'ratingTrend' => $analytics->ratingTrend(6, $branchId),
            'sentiment' => $analytics->sentimentDistribution($branchId),
            'ratingDistribution' => $analytics->ratingDistribution(),
            'topics' => $analytics->topTopics(),
            'actions' => $analytics->actionsSeries(30, $branchId),
            'actionTotals' => $analytics->actionTotals(30),
            'qrFunnel' => $analytics->qrFunnel(),
        ]);
    }
}
