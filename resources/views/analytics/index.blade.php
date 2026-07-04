@extends('layouts.app')

@section('title', __('Analytics'))

@section('content')
    <form method="GET" class="flex items-center gap-2">
        <select name="branch_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">{{ __('All branches') }}</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </form>

    {{-- KPI row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">{{ __('Presence health') }}</div>
            <div class="mt-1 text-3xl font-bold text-brand-600">{{ $healthScore }}%</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">{{ __('Avg rating') }}</div>
            <div class="mt-1 text-3xl font-bold text-amber-500">{{ $reviewKpis['average'] ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">{{ __('Reviews') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ number_format($reviewKpis['total']) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">📞 {{ __('Calls') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ number_format($actionTotals['calls'] ?? 0) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">🧭 {{ __('Routes') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ number_format($actionTotals['routes'] ?? 0) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase">🌐 {{ __('Clicks') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ number_format($actionTotals['website_clicks'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Actions chart --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="font-semibold mb-4">{{ __('Customer actions — last 30 days') }}</h2>
        @php
            $actionsChart = [
                'type' => 'bar',
                'data' => [
                    'labels' => array_map(fn ($d) => date('M d', strtotime($d)), $actions['labels']),
                    'datasets' => [
                        ['label' => __('Calls'), 'data' => $actions['series']['calls'] ?? [], 'backgroundColor' => '#3563fb'],
                        ['label' => __('Routes'), 'data' => $actions['series']['routes'] ?? [], 'backgroundColor' => '#10b981'],
                        ['label' => __('Website clicks'), 'data' => $actions['series']['website_clicks'] ?? [], 'backgroundColor' => '#f59e0b'],
                    ],
                ],
                'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]],
            ];
        @endphp
        <canvas height="90" data-chart="{{ json_encode($actionsChart) }}"></canvas>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Rating trend') }}</h2>
            @php
                $trendChart = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $ratingTrend['labels'],
                        'datasets' => [['label' => __('Average rating'), 'data' => $ratingTrend['values'], 'borderColor' => '#f59e0b', 'tension' => 0.35]],
                    ],
                    'options' => ['scales' => ['y' => ['min' => 1, 'max' => 5]]],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($trendChart) }}"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Rating distribution') }}</h2>
            @php
                $distributionChart = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => array_map(fn ($s) => $s.' ★', array_keys($ratingDistribution)),
                        'datasets' => [['label' => __('Reviews'), 'data' => array_values($ratingDistribution), 'backgroundColor' => '#3563fb']],
                    ],
                    'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($distributionChart) }}"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Sentiment') }}</h2>
            @php
                $sentimentChart = [
                    'type' => 'doughnut',
                    'data' => [
                        'labels' => [__('Positive'), __('Neutral'), __('Negative')],
                        'datasets' => [[
                            'data' => [$sentiment['positive'] ?? 0, $sentiment['neutral'] ?? 0, $sentiment['negative'] ?? 0],
                            'backgroundColor' => ['#10b981', '#94a3b8', '#ef4444'],
                        ]],
                    ],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($sentimentChart) }}"></canvas>
        </div>
    </div>

    {{-- Topics + QR --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('What customers talk about') }}</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($topics as $topic => $count)
                    <span class="rounded-full bg-brand-50 text-brand-700 px-3 py-1.5 text-sm">{{ $topic }} <span class="text-brand-400">×{{ $count }}</span></span>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Topics appear once reviews are analyzed.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('QR review funnel') }}</h2>
            @php
                $qrChart = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => [__('Scans'), __('To public review'), __('Captured internally')],
                        'datasets' => [['label' => 'QR', 'data' => [$qrFunnel['scans'], $qrFunnel['positive'], $qrFunnel['negative']], 'backgroundColor' => ['#3563fb', '#10b981', '#ef4444']]],
                    ],
                    'options' => ['plugins' => ['legend' => ['display' => false]]],
                ];
            @endphp
            <canvas height="120" data-chart="{{ json_encode($qrChart) }}"></canvas>
        </div>
    </div>
@endsection
