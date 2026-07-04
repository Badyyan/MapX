<?php

namespace App\Http\Controllers;

use App\Jobs\TrackLocalRanks;
use App\Models\Branch;
use App\Models\LocalRankSnapshot;
use App\Models\TrackedKeyword;
use App\Services\RankTracker;
use Illuminate\Http\Request;

class RankTrackerController extends Controller
{
    public function index(Request $request, RankTracker $tracker)
    {
        $branches = Branch::orderBy('name')->get();
        $branchId = $request->integer('branch_id') ?: $branches->first()?->id;

        $latest = LocalRankSnapshot::with('branch')
            ->whereIn('tracked_at', [LocalRankSnapshot::max('tracked_at')])
            ->orderBy('keyword')
            ->get();

        return view('rank.index', [
            'keywords' => TrackedKeyword::orderBy('keyword')->get(),
            'branches' => $branches,
            'branchId' => $branchId,
            'latest' => $latest,
            'series' => $branchId ? $tracker->series($branchId, 30) : ['labels' => [], 'series' => []],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:100'],
            'radius_km' => ['required', 'numeric', 'between:0.5,100'],
        ]);

        TrackedKeyword::firstOrCreate(
            ['company_id' => $request->user()->company_id, 'keyword' => mb_strtolower(trim($data['keyword']))],
            ['radius_km' => $data['radius_km']],
        );

        TrackLocalRanks::dispatch($request->user()->company_id);

        return back()->with('success', __('Keyword added. First snapshot is being collected.'));
    }

    public function destroy(TrackedKeyword $keyword)
    {
        $keyword->delete();

        return back()->with('success', __('Keyword removed.'));
    }

    public function refresh(Request $request)
    {
        TrackLocalRanks::dispatch($request->user()->company_id);

        return back()->with('success', __('Rank tracking refresh queued.'));
    }
}
