@extends('layouts.app')

@section('title', __('Analytics'))

@section('content')
    <form method="GET">
        <select name="branch_id" onchange="this.form.submit()" class="input !w-auto" aria-label="{{ __('Branch') }}">
            <option value="">{{ __('All branches') }}</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </form>

    {{-- KPI row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-4 animate-stagger">
        <div class="card p-5">
            <p class="stat-label">{{ __('Presence health') }}</p>
            <p class="stat-value !text-brand-600">{{ $healthScore }}%</p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Avg rating') }}</p>
            <p class="stat-value !text-amber-500">{{ $reviewKpis['average'] ?? '—' }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Reviews') }}</p>
            <p class="stat-value">{{ number_format($reviewKpis['total']) }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label flex items-center gap-1.5"><x-icon name="phone" class="size-3" /> {{ __('Calls') }}</p>
            <p class="stat-value">{{ number_format($actionTotals['calls'] ?? 0) }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label flex items-center gap-1.5"><x-icon name="navigation" class="size-3" /> {{ __('Routes') }}</p>
            <p class="stat-value">{{ number_format($actionTotals['routes'] ?? 0) }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label flex items-center gap-1.5"><x-icon name="globe" class="size-3" /> {{ __('Clicks') }}</p>
            <p class="stat-value">{{ number_format($actionTotals['website_clicks'] ?? 0) }}</p>
        </div>
    </div>

    {{-- Actions chart --}}
    <div class="card p-6">
        <h2 class="section-title mb-5">{{ __('Customer actions — last 30 days') }}</h2>
        @php
            $actionsChart = [
                'type' => 'bar',
                'data' => [
                    'labels' => array_map(fn ($d) => date('M d', strtotime($d)), $actions['labels']),
                    'datasets' => [
                        ['label' => __('Calls'), 'data' => $actions['series']['calls'] ?? [], 'backgroundColor' => '#0071e3', 'borderRadius' => 3],
                        ['label' => __('Routes'), 'data' => $actions['series']['routes'] ?? [], 'backgroundColor' => '#34c759', 'borderRadius' => 3],
                        ['label' => __('Website clicks'), 'data' => $actions['series']['website_clicks'] ?? [], 'backgroundColor' => '#ff9500', 'borderRadius' => 3],
                    ],
                ],
                'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]],
            ];
        @endphp
        <canvas height="90" data-chart="{{ json_encode($actionsChart) }}"></canvas>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Rating trend') }}</h2>
            @php
                $trendChart = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $ratingTrend['labels'],
                        'datasets' => [['label' => __('Average rating'), 'data' => $ratingTrend['values'], 'borderColor' => '#ff9500', 'tension' => 0.35, 'pointBackgroundColor' => '#ff9500']],
                    ],
                    'options' => ['scales' => ['y' => ['min' => 1, 'max' => 5]]],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($trendChart) }}"></canvas>
        </div>
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Rating distribution') }}</h2>
            @php
                $distributionChart = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => array_map(fn ($s) => $s.' ★', array_keys($ratingDistribution)),
                        'datasets' => [['label' => __('Reviews'), 'data' => array_values($ratingDistribution), 'backgroundColor' => '#0071e3', 'borderRadius' => 3]],
                    ],
                    'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($distributionChart) }}"></canvas>
        </div>
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Sentiment') }}</h2>
            @php
                $sentimentChart = [
                    'type' => 'doughnut',
                    'data' => [
                        'labels' => [__('Positive'), __('Neutral'), __('Negative')],
                        'datasets' => [[
                            'data' => [$sentiment['positive'] ?? 0, $sentiment['neutral'] ?? 0, $sentiment['negative'] ?? 0],
                            'backgroundColor' => ['#34c759', '#d2d2d7', '#ff3b30'],
                            'borderWidth' => 0,
                            'spacing' => 2,
                        ]],
                    ],
                    'options' => ['cutout' => '68%'],
                ];
            @endphp
            <canvas data-chart="{{ json_encode($sentimentChart) }}"></canvas>
        </div>
    </div>

    {{-- Topics + QR --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('What customers talk about') }}</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($topics as $topic => $count)
                    <span class="badge badge-brand !py-1.5 !px-3 !text-sm !font-normal">{{ $topic }} <span class="text-brand-400 font-medium">×{{ $count }}</span></span>
                @empty
                    <p class="text-sm text-slate-400">{{ __('Topics appear once reviews are analyzed.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('QR review funnel') }}</h2>
            @php
                $qrChart = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => [__('Scans'), __('To public review'), __('Captured internally')],
                        'datasets' => [['label' => 'QR', 'data' => [$qrFunnel['scans'], $qrFunnel['positive'], $qrFunnel['negative']], 'backgroundColor' => ['#0071e3', '#34c759', '#ff3b30'], 'borderRadius' => 3]],
                    ],
                    'options' => ['plugins' => ['legend' => ['display' => false]]],
                ];
            @endphp
            <canvas height="120" data-chart="{{ json_encode($qrChart) }}"></canvas>
        </div>
    </div>
@endsection
