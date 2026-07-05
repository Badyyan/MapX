@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    {{-- KPI row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-4 animate-stagger">
        <div class="card p-5">
            <p class="stat-label">{{ __('Presence health') }}</p>
            <p class="stat-value {{ $healthScore >= 70 ? '!text-emerald-600' : ($healthScore >= 40 ? '!text-amber-500' : '!text-red-500') }}">{{ $healthScore }}%</p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Branches') }}</p>
            <p class="stat-value">{{ $branchCount }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Synchronized') }}</p>
            <p class="stat-value">{{ $syncedConnections }}<span class="text-lg font-medium text-slate-400">/{{ $totalConnections }}</span></p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Total reviews') }}</p>
            <p class="stat-value">{{ number_format($reviewKpis['total']) }}</p>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Average rating') }}</p>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 min-w-0">
                <span class="text-3xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $reviewKpis['average'] ?? '—' }}</span>
                <x-rating :value="$reviewKpis['average'] ?? 0" class="size-3.5" />
            </div>
        </div>
        <div class="card p-5">
            <p class="stat-label">{{ __('Unanswered') }}</p>
            <p class="stat-value {{ $reviewKpis['unanswered'] > 0 ? '!text-red-500' : '' }}">{{ $reviewKpis['unanswered'] }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Rating trend --}}
        <div class="lg:col-span-2 card p-6">
            <h2 class="section-title mb-5">{{ __('Rating trend') }}</h2>
            @php
                $ratingTrendChart = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $ratingTrend['labels'],
                        'datasets' => [[
                            'label' => __('Average rating'),
                            'data' => $ratingTrend['values'],
                            'borderColor' => '#0071e3',
                            'backgroundColor' => 'rgba(0,113,227,.06)',
                            'fill' => true,
                            'tension' => 0.35,
                            'pointRadius' => 3,
                            'pointBackgroundColor' => '#0071e3',
                        ]],
                    ],
                    'options' => ['scales' => ['y' => ['min' => 1, 'max' => 5]]],
                ];
            @endphp
            <canvas height="110" data-chart="{{ json_encode($ratingTrendChart) }}"></canvas>
        </div>

        {{-- Sentiment donut --}}
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

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Customer actions --}}
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Customer actions (30 days)') }}</h2>
            <dl class="space-y-2.5">
                @foreach(['calls' => ['phone', __('Calls')], 'routes' => ['navigation', __('Routes built')], 'website_clicks' => ['globe', __('Website clicks')]] as $key => [$icon, $label])
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="flex items-center gap-2.5 text-sm text-slate-600">
                            <span class="grid place-items-center size-7 rounded-lg bg-white shadow-xs text-brand-600"><x-icon :name="$icon" class="size-3.5" /></span>
                            {{ $label }}
                        </dt>
                        <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($actionTotals[$key] ?? 0) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- QR funnel --}}
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('QR review funnel') }}</h2>
            <dl class="space-y-2.5">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="flex items-center gap-2.5 text-sm text-slate-600"><x-icon name="qr-code" class="size-4 text-slate-400" /> {{ __('Scans') }}</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($qrFunnel['scans']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3">
                    <dt class="flex items-center gap-2.5 text-sm text-emerald-700"><x-icon name="thumbs-up" class="size-4" /> {{ __('Sent to public review') }}</dt>
                    <dd class="font-semibold text-emerald-700 tabular-nums">{{ number_format($qrFunnel['positive']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-rose-50 px-4 py-3">
                    <dt class="flex items-center gap-2.5 text-sm text-rose-700"><x-icon name="thumbs-down" class="size-4" /> {{ __('Captured internally') }}</dt>
                    <dd class="font-semibold text-rose-700 tabular-nums">{{ number_format($qrFunnel['negative']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="flex items-center gap-2.5 text-sm text-slate-600"><x-icon name="trending-up" class="size-4 text-slate-400" /> {{ __('Conversion') }}</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ $qrFunnel['conversion'] }}%</dd>
                </div>
            </dl>
        </div>

        {{-- Scheduled posts --}}
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Upcoming posts') }}</h2>
            <div class="space-y-1">
                @forelse($scheduledPosts as $post)
                    <div class="flex items-start gap-3 rounded-xl px-3 py-2.5 -mx-3 hover:bg-slate-50 transition-colors">
                        <span class="mt-0.5 grid place-items-center size-7 rounded-lg bg-brand-50 text-brand-600"><x-icon name="calendar" class="size-3.5" /></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $post->title ?: Str::limit($post->content, 40) }}</p>
                            <p class="text-xs text-slate-500">{{ $post->scheduled_at?->format('M d, H:i') }} · {{ implode(', ', $post->platforms) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No scheduled posts.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent reviews --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="section-title">{{ __('Latest reviews') }}</h2>
            <a href="{{ route('reviews.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                {{ __('Open inbox') }} <x-icon name="arrow-right" class="size-3.5 rtl:rotate-180" />
            </a>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($recentReviews as $review)
                <div class="rounded-xl ring-1 ring-slate-900/[0.06] p-4 card-hover">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-slate-900 truncate">{{ $review->author_name }}</span>
                        <x-rating :value="$review->rating" class="size-3.5" />
                    </div>
                    <p class="mt-2 text-sm text-slate-600 line-clamp-2" dir="auto">{{ $review->content }}</p>
                    <p class="mt-2.5 text-xs text-slate-400">{{ config("mapx.platforms.{$review->platform}.name") }} · {{ $review->branch?->name }} · {{ $review->review_date?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-400">{{ __('No reviews yet. Connect your platforms to start collecting reviews.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
