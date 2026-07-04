@extends('layouts.app')

@section('title', __('Local rank tracker'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h2 class="font-semibold">{{ __('Position history (lower is better)') }}</h2>
                    <form method="GET" class="flex items-center gap-2">
                        <select name="branch_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @if(empty($series['labels']))
                    <p class="text-sm text-slate-500">{{ __('No snapshots yet. Add keywords and run a refresh.') }}</p>
                @else
                    @php
                        $rankChart = [
                            'type' => 'line',
                            'data' => [
                                'labels' => $series['labels'],
                                'datasets' => collect($series['series'])->map(fn ($positions, $keyword) => [
                                    'label' => $keyword,
                                    'data' => $positions,
                                    'tension' => 0.3,
                                ])->values()->all(),
                            ],
                            'options' => ['scales' => ['y' => ['reverse' => true, 'min' => 1]]],
                        ];
                    @endphp
                    <canvas height="110" data-chart="{{ json_encode($rankChart) }}"></canvas>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-semibold">{{ __('Latest positions') }}</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="text-start px-4 py-2">{{ __('Keyword') }}</th>
                                <th class="text-start px-4 py-2">{{ __('Branch') }}</th>
                                <th class="text-start px-4 py-2">{{ __('Position') }}</th>
                                <th class="text-start px-4 py-2">{{ __('Radius') }}</th>
                                <th class="text-start px-4 py-2">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($latest as $snapshot)
                                <tr>
                                    <td class="px-4 py-2 font-medium">{{ $snapshot->keyword }}</td>
                                    <td class="px-4 py-2">{{ $snapshot->branch?->name }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-grid place-items-center size-7 rounded-full text-xs font-bold
                                            {{ ($snapshot->position ?? 99) <= 3 ? 'bg-emerald-100 text-emerald-700' : (($snapshot->position ?? 99) <= 10 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $snapshot->position ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-500">{{ $snapshot->radius_km }} km</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $snapshot->tracked_at->format('M d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No rank data yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if(auth()->user()->hasPermission('rank.manage'))
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h2 class="font-semibold mb-4">{{ __('Track a keyword') }}</h2>
                    <form method="POST" action="{{ route('rank.keywords.store') }}" class="space-y-3">
                        @csrf
                        <input name="keyword" required placeholder="{{ __('e.g. coffee shop riyadh') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <div class="flex items-center gap-2">
                            <input type="number" name="radius_km" value="5" step="0.5" min="0.5" max="100" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <span class="text-sm text-slate-500">km {{ __('radius') }}</span>
                        </div>
                        <button class="w-full rounded-lg bg-brand-600 text-white py-2 text-sm font-medium hover:bg-brand-700">{{ __('Add keyword') }}</button>
                    </form>
                    <form method="POST" action="{{ route('rank.refresh') }}" class="mt-3">
                        @csrf
                        <button class="w-full rounded-lg bg-white border border-slate-300 py-2 text-sm hover:bg-slate-50">🔄 {{ __('Refresh snapshots now') }}</button>
                    </form>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">{{ __('Tracked keywords') }}</h2>
                <div class="space-y-2">
                    @forelse($keywords as $keyword)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <span>{{ $keyword->keyword }} <span class="text-xs text-slate-400">({{ $keyword->radius_km }} km)</span></span>
                            @if(auth()->user()->hasPermission('rank.manage'))
                                <form method="POST" action="{{ route('rank.keywords.destroy', $keyword) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">✕</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No keywords tracked yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
