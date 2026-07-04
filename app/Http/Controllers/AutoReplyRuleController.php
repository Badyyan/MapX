<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AutoReplyRule;
use Illuminate\Http\Request;

class AutoReplyRuleController extends Controller
{
    public function index()
    {
        return view('auto-rules.index', [
            'rules' => AutoReplyRule::orderByDesc('priority')->get(),
        ]);
    }

    public function create()
    {
        return view('auto-rules.form', ['rule' => new AutoReplyRule(['min_rating' => 4, 'max_rating' => 5, 'require_approval' => true])]);
    }

    public function store(Request $request)
    {
        $rule = AutoReplyRule::create($this->validated($request));
        AuditLog::record('auto_rule.created', $rule);

        return redirect()->route('auto-rules.index')->with('success', __('Auto-reply rule created.'));
    }

    public function edit(AutoReplyRule $autoRule)
    {
        return view('auto-rules.form', ['rule' => $autoRule]);
    }

    public function update(Request $request, AutoReplyRule $autoRule)
    {
        $autoRule->update($this->validated($request));
        AuditLog::record('auto_rule.updated', $autoRule);

        return redirect()->route('auto-rules.index')->with('success', __('Auto-reply rule updated.'));
    }

    public function destroy(AutoReplyRule $autoRule)
    {
        $autoRule->delete();
        AuditLog::record('auto_rule.deleted', $autoRule);

        return back()->with('success', __('Auto-reply rule deleted.'));
    }

    public function toggle(AutoReplyRule $autoRule)
    {
        $autoRule->update(['is_active' => ! $autoRule->is_active]);

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'min_rating' => ['required', 'integer', 'between:1,5'],
            'max_rating' => ['required', 'integer', 'between:1,5', 'gte:min_rating'],
            'sentiment' => ['nullable', 'in:positive,neutral,negative'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string'],
            'language' => ['nullable', 'in:en,ar'],
            'delay_minutes' => ['required', 'integer', 'between:0,10080'],
            'require_approval' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'between:0,100'],
            'templates' => ['required', 'array', 'min:1'],
            'templates.*' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['keywords'] = isset($data['keywords']) && $data['keywords'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $data['keywords']))))
            : null;
        $data['templates'] = array_values(array_filter($data['templates']));
        $data['require_approval'] = $request->boolean('require_approval');
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
