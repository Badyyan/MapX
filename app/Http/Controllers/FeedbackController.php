<?php

namespace App\Http\Controllers;

use App\Models\QrCampaign;
use App\Models\QrFeedback;
use Illuminate\Http\Request;

/**
 * Public QR feedback flow (FR-18/19/20). No auth: customers land here by
 * scanning the QR at the point of sale. Ratings at/above the campaign
 * threshold are redirected to the public review page; lower ratings stay
 * internal so problems reach the business without hurting the map rating.
 */
class FeedbackController extends Controller
{
    public function show(string $slug)
    {
        $campaign = QrCampaign::withoutGlobalScope('company')
            ->where('slug', $slug)->where('is_active', true)
            ->with('branch')
            ->firstOrFail();

        $campaign->increment('scans_count');

        return view('feedback.show', compact('campaign'));
    }

    public function rate(Request $request, string $slug)
    {
        $campaign = QrCampaign::withoutGlobalScope('company')
            ->where('slug', $slug)->where('is_active', true)
            ->with('branch')
            ->firstOrFail();

        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:'.$campaign->rating_scale]]);

        if ($campaign->isPositive((int) $data['rating'])) {
            $campaign->increment('positive_count');

            return redirect()->away($campaign->redirectUrl() ?? route('feedback.thanks', $slug));
        }

        return view('feedback.form', ['campaign' => $campaign, 'rating' => (int) $data['rating']]);
    }

    public function store(Request $request, string $slug)
    {
        $campaign = QrCampaign::withoutGlobalScope('company')
            ->where('slug', $slug)->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:'.$campaign->rating_scale],
            'comment' => ['nullable', 'string', 'max:2000'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $campaign->increment('negative_count');

        QrFeedback::withoutGlobalScope('company')->create([
            ...$data,
            'qr_campaign_id' => $campaign->id,
            'branch_id' => $campaign->branch_id,
            'company_id' => $campaign->company_id,
        ]);

        return redirect()->route('feedback.thanks', $slug);
    }

    public function thanks(string $slug)
    {
        $campaign = QrCampaign::withoutGlobalScope('company')
            ->where('slug', $slug)->with('branch')->firstOrFail();

        return view('feedback.thanks', compact('campaign'));
    }
}
