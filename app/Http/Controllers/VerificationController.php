<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\VerificationMessage;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;

/**
 * FR-32..34: verification assistance — status per branch, document upload
 * and a message thread with the support team.
 */
class VerificationController extends Controller
{
    public function index()
    {
        return view('verification.index', [
            'branches' => Branch::orderBy('name')->get(),
            'requests' => VerificationRequest::with(['branch', 'messages'])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        $documents = collect($request->file('documents') ?? [])
            ->map(fn ($file) => [
                'path' => $file->store('verification', 'public'),
                'name' => $file->getClientOriginalName(),
            ])->all();

        $verification = VerificationRequest::create([
            'branch_id' => $data['branch_id'],
            'notes' => $data['notes'] ?? null,
            'documents' => $documents ?: null,
            'status' => 'pending',
        ]);

        Branch::find($data['branch_id'])->update(['verification_status' => 'pending']);
        AuditLog::record('verification.requested', $verification);

        return back()->with('success', __('Verification request submitted. Our team will review it shortly.'));
    }

    public function message(Request $request, VerificationRequest $verification)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        VerificationMessage::create([
            'verification_request_id' => $verification->id,
            'user_id' => $request->user()->id,
            'is_support' => false,
            'message' => $data['message'],
        ]);

        return back()->with('success', __('Message sent to support.'));
    }
}
