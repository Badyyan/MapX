<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->q}%")
                ->orWhere('city', 'like', "%{$request->q}%")
                ->orWhere('address', 'like', "%{$request->q}%")))
            ->withCount('reviews')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.form', ['branch' => new Branch()]);
    }

    public function store(Request $request, BillingService $billing)
    {
        // FR-30: enforce the trial branch limit.
        if (! $billing->canAddBranch($request->user()->company)) {
            return back()->with('error', __('Branch limit reached for your current plan. Upgrade to add more branches.'));
        }

        $branch = Branch::create($this->validated($request));
        AuditLog::record('branch.created', $branch);

        return redirect()->route('branches.show', $branch)->with('success', __('Branch created.'));
    }

    public function show(Branch $branch)
    {
        $branch->load('connections');

        return view('branches.show', [
            'branch' => $branch,
            'recentReviews' => $branch->reviews()->latest('review_date')->limit(5)->get(),
            'platforms' => config('mapx.platforms'),
        ]);
    }

    public function edit(Branch $branch)
    {
        return view('branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request));
        AuditLog::record('branch.updated', $branch);

        // Data changed: flag connected platforms as out of sync (FR-7).
        $branch->connections()->where('sync_status', 'synced')->update(['sync_status' => 'out_of_sync']);

        return redirect()->route('branches.show', $branch)->with('success', __('Branch updated.'));
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        AuditLog::record('branch.deleted', $branch);

        return redirect()->route('branches.index')->with('success', __('Branch deleted.'));
    }

    /** FR-6: bulk import via CSV. */
    public function import(Request $request, BillingService $billing)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);

        if (! in_array('name', $header, true)) {
            return back()->with('error', __('CSV must contain a "name" column.'));
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (! $billing->canAddBranch($request->user()->company)) {
                $skipped++;

                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            if (empty($data['name'])) {
                $skipped++;

                continue;
            }

            Branch::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? 'SA',
                'lat' => is_numeric($data['lat'] ?? null) ? $data['lat'] : null,
                'lng' => is_numeric($data['lng'] ?? null) ? $data['lng'] : null,
                'phone' => $data['phone'] ?? null,
                'whatsapp_number' => $data['whatsapp'] ?? $data['whatsapp_number'] ?? null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'categories' => isset($data['categories']) && $data['categories'] !== ''
                    ? array_map('trim', explode(';', $data['categories']))
                    : null,
            ]);
            $imported++;
        }

        fclose($handle);
        AuditLog::record('branch.imported', null, ['imported' => $imported, 'skipped' => $skipped]);

        return back()->with('success', __(':imported branches imported, :skipped skipped.', [
            'imported' => $imported, 'skipped' => $skipped,
        ]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'categories' => ['nullable', 'string', 'max:500'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'hours' => ['nullable', 'array'],
            'hours.*.open' => ['nullable', 'string', 'max:5'],
            'hours.*.close' => ['nullable', 'string', 'max:5'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:4096'],
        ]);

        if (isset($data['categories'])) {
            $data['categories'] = array_values(array_filter(array_map('trim', explode(',', $data['categories']))));
        }

        if ($request->hasFile('images')) {
            $data['images'] = collect($request->file('images'))
                ->map(fn ($file) => $file->store('branches', 'public'))
                ->all();
        } else {
            unset($data['images']);
        }

        if (isset($data['hours'])) {
            $data['hours'] = collect($data['hours'])
                ->filter(fn ($h) => ! empty($h['open']) && ! empty($h['close']))
                ->all() ?: null;
        }

        return $data;
    }
}
