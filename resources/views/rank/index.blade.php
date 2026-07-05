@extends('layouts.app')

@section('title', __('Local rank tracker'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <h2 class="section-title">{{ __('Position history (lower is better)') }}</h2>
                    <form method="GET">
                        <select name="branch_id" onchange="this.form.submit()" class="input !w-auto !py-2" aria-label="{{ __('Branch') }}">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @if(empty($series['labels']))
                    <p class="text-sm text-slate-400">{{ __('No snapshots yet. Add keywords and run a refresh.') }}</p>
                @else
                    @php
                        $palette = ['#0071e3', '#34c759', '#ff9500', '#ff3b30', '#30b0c7'];
                        $rankChart = [
                            'type' => 'line',
                            'data' => [
                                'labels' => $series['labels'],
                                'datasets' => collect($series['series'])->values()->map(fn ($positions, $i) => [
                                    'label' => array_keys($series['series'])[$i],
                                    'data' => $positions,
                                    'tension' => 0.3,
                                    'borderColor' => $palette[$i % count($palette)],
                                    'pointBackgroundColor' => $palette[$i % count($palette)],
                                ])->all(),
                            ],
                            'options' => ['scales' => ['y' => ['reverse' => true, 'min' => 1]]],
                        ];
                    @endphp
                    <canvas height="110" data-chart="{{ json_encode($rankChart) }}"></canvas>
                @endif
            </div>

            <div class="table-wrap">
                <div class="px-6 py-4 border-b border-slate-100 section-title">{{ __('Latest positions') }}</div>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>{{ __('Keyword') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Position') }}</th>
                                <th>{{ __('Radius') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latest as $snapshot)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $snapshot->keyword }}</td>
                                    <td>{{ $snapshot->branch?->name }}</td>
                                    <td>
                                        <span class="inline-grid place-items-center size-7 rounded-full text-xs font-bold tabular-nums
                                            {{ ($snapshot->position ?? 99) <= 3 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : (($snapshot->position ?? 99) <= 10 ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20') }}">
                                            {{ $snapshot->position ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-slate-400 tabular-nums">{{ $snapshot->radius_km }} km</td>
                                    <td class="text-slate-400">{{ $snapshot->tracked_at->format('M d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="!py-8 text-center text-slate-400">{{ __('No rank data yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if(auth()->user()->hasPermission('rank.manage'))
                <div class="card p-6">
                    <h2 class="section-title mb-5">{{ __('Track a keyword') }}</h2>
                    <form method="POST" action="{{ route('rank.keywords.store') }}" class="space-y-3">
                        @csrf
                        <input name="keyword" required placeholder="{{ __('e.g. coffee shop riyadh') }}" class="input" aria-label="{{ __('Keyword') }}">
                        <div class="flex items-center gap-2.5">
                            <input type="number" name="radius_km" value="5" step="0.5" min="0.5" max="100" class="input !w-24" aria-label="{{ __('Radius') }}">
                            <span class="text-sm text-slate-500">km {{ __('radius') }}</span>
                        </div>
                        <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> {{ __('Add keyword') }}</button>
                    </form>
                    <form method="POST" action="{{ route('rank.refresh') }}" class="mt-3">
                        @csrf
                        <button class="btn btn-secondary w-full"><x-icon name="refresh-cw" class="size-4" /> {{ __('Refresh snapshots now') }}</button>
                    </form>
                </div>
            @endif

            <div class="card p-6">
                <h2 class="section-title mb-4">{{ __('Tracked keywords') }}</h2>
                <div class="space-y-2">
                    @forelse($keywords as $keyword)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3.5 py-2.5 text-sm">
                            <span class="text-slate-700">{{ $keyword->keyword }} <span class="text-xs text-slate-400">({{ $keyword->radius_km }} km)</span></span>
                            @if(auth()->user()->hasPermission('rank.manage'))
                                <form method="POST" action="{{ route('rank.keywords.destroy', $keyword) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-slate-300 hover:text-red-500 transition-colors" aria-label="{{ __('Keyword removed.') }}"><x-icon name="x" class="size-4" /></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No keywords tracked yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
