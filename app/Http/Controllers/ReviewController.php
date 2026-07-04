<?php

namespace App\Http\Controllers;

use App\Jobs\SendReviewReply;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Services\AiService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** FR: unified reviews inbox with filters (BRD 5.3). */
    public function index(Request $request)
    {
        $reviews = Review::with(['branch', 'replies'])
            ->when($request->filled('platform'), fn ($q) => $q->where('platform', $request->platform))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('rating'), fn ($q) => $q->whereBetween('rating', [(int) $request->rating, $request->rating + 0.9]))
            ->when($request->filled('sentiment'), fn ($q) => $q->where('sentiment', $request->sentiment))
            ->when($request->filled('language'), fn ($q) => $q->where('language', $request->language))
            ->when($request->filled('replied'), fn ($q) => $q->where('is_replied', $request->replied === 'yes'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('content', 'like', "%{$request->q}%")
                ->orWhere('author_name', 'like', "%{$request->q}%")))
            ->latest('review_date')
            ->paginate(15)
            ->withQueryString();

        return view('reviews.index', [
            'reviews' => $reviews,
            'branches' => Branch::orderBy('name')->get(),
            'platforms' => config('mapx.platforms'),
            'pendingApprovals' => ReviewReply::where('status', 'pending_approval')
                ->whereHas('review')
                ->count(),
        ]);
    }

    /** Pending auto-replies awaiting approval (FR-17). */
    public function approvals()
    {
        $pending = ReviewReply::with('review.branch')
            ->where('status', 'pending_approval')
            ->whereHas('review')
            ->latest()
            ->paginate(15);

        return view('reviews.approvals', compact('pending'));
    }

    public function reply(Request $request, Review $review)
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:4000']]);

        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'source' => $request->boolean('ai_assisted') ? 'ai' : 'manual',
            'status' => 'queued',
        ]);

        SendReviewReply::dispatch($reply);
        AuditLog::record('review.replied', $review);

        return back()->with('success', __('Reply queued for delivery.'));
    }

    /** FR-15: AI suggestion endpoint (returns JSON for the inbox UI). */
    public function suggest(Review $review, AiService $ai)
    {
        return response()->json([
            'suggestion' => $ai->suggestReviewReply($review, request('tone', 'professional')),
        ]);
    }

    public function approveReply(ReviewReply $reply)
    {
        abort_unless($reply->review && $reply->review->company_id === auth()->user()->company_id, 404);

        $reply->update(['status' => 'queued']);
        SendReviewReply::dispatch($reply);
        AuditLog::record('review.reply_approved', $reply->review);

        return back()->with('success', __('Reply approved and queued.'));
    }

    public function rejectReply(ReviewReply $reply)
    {
        abort_unless($reply->review && $reply->review->company_id === auth()->user()->company_id, 404);

        $reply->delete();

        return back()->with('success', __('Suggested reply discarded.'));
    }
}
