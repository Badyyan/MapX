<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->where('company_id', $request->user()->company_id)
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
