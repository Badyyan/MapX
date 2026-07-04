<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\QrCampaign;
use App\Models\QrFeedback;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCampaignController extends Controller
{
    public function index()
    {
        return view('qr.index', [
            'campaigns' => QrCampaign::with('branch')->withCount('feedback')->latest()->get(),
            'branches' => Branch::orderBy('name')->get(),
            'feedback' => QrFeedback::with(['branch', 'campaign'])->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'rating_scale' => ['required', 'in:5,10'],
            'threshold' => ['required', 'integer', 'min:1'],
            'positive_redirect_url' => ['nullable', 'url', 'max:500'],
        ]);

        if ($data['threshold'] > $data['rating_scale']) {
            return back()->withInput()->with('error', __('Threshold cannot exceed the rating scale.'));
        }

        $campaign = QrCampaign::create([...$data, 'slug' => Str::lower(Str::random(10))]);
        AuditLog::record('qr.created', $campaign);

        return back()->with('success', __('QR campaign created.'));
    }

    public function svg(QrCampaign $qr, QrCodeService $qrCode)
    {
        return response($qrCode->svg($qr->publicUrl(), 600))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="qr-'.$qr->slug.'.svg"');
    }

    public function toggle(QrCampaign $qr)
    {
        $qr->update(['is_active' => ! $qr->is_active]);

        return back();
    }

    public function destroy(QrCampaign $qr)
    {
        $qr->delete();
        AuditLog::record('qr.deleted', $qr);

        return back()->with('success', __('QR campaign deleted.'));
    }

    public function updateFeedbackStatus(Request $request, QrFeedback $feedback)
    {
        $request->validate(['status' => ['required', 'in:new,in_progress,resolved']]);
        $feedback->update(['status' => $request->status]);

        return back()->with('success', __('Feedback status updated.'));
    }
}
