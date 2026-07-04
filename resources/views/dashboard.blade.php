@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    {{-- KPI row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Presence health') }}</div>
            <div class="mt-1 text-3xl font-bold {{ $healthScore >= 70 ? 'text-emerald-600' : ($healthScore >= 40 ? 'text-amber-500' : 'text-red-500') }}">{{ $healthScore }}%</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Branches') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ $branchCount }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Synchronized') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ $syncedConnections }}<span class="text-base text-slate-400">/{{ $totalConnections }}</span></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Total reviews') }}</div>
            <div class="mt-1 text-3xl font-bold">{{ number_format($reviewKpis['total']) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Average rating') }}</div>
            <div class="mt-1 text-3xl font-bold text-amber-500">{{ $reviewKpis['average'] ?? '—' }} ★</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">{{ __('Unanswered') }}</div>
            <div class="mt-1 text-3xl font-bold {{ $reviewKpis['unanswered'] > 0 ? 'text-red-500' : '' }}">{{ $reviewKpis['unanswered'] }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Rating trend --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Rating trend') }}</h2>
            @php
                $ratingTrendChart = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $ratingTrend['labels'],
                        'datasets' => [[
                            'label' => __('Average rating'),
                            'data' => $ratingTrend['values'],
                            'borderColor' => '#3563fb',
                            'backgroundColor' => 'rgba(53,99,251,.08)',
                            'fill' => true,
                            'tension' => 0.35,
                        ]],
                    ],
                    'options' => ['scales' => ['y' => ['min' => 1, 'max' => 5]]],
                ];
            @endphp
            <canvas height="110" data-chart="{{ json_encode($ratingTrendChart) }}"></canvas>
        </div>

        {{-- Sentiment donut --}}
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

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- GBP actions --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Customer actions (30 days)') }}</h2>
            <dl class="space-y-3">
                @foreach(['calls' => ['📞', __('Calls')], 'routes' => ['🧭', __('Routes built')], 'website_clicks' => ['🌐', __('Website clicks')]] as $key => [$icon, $label])
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-sm text-slate-600">{{ $icon }} {{ $label }}</dt>
                        <dd class="font-bold">{{ number_format($actionTotals[$key] ?? 0) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- QR funnel --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('QR review funnel') }}</h2>
            <dl class="space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-sm text-slate-600">{{ __('Scans') }}</dt><dd class="font-bold">{{ number_format($qrFunnel['scans']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3">
                    <dt class="text-sm text-emerald-700">{{ __('Sent to public review') }}</dt><dd class="font-bold text-emerald-700">{{ number_format($qrFunnel['positive']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-red-50 px-4 py-3">
                    <dt class="text-sm text-red-700">{{ __('Captured internally') }}</dt><dd class="font-bold text-red-700">{{ number_format($qrFunnel['negative']) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-sm text-slate-600">{{ __('Conversion') }}</dt><dd class="font-bold">{{ $qrFunnel['conversion'] }}%</dd>
                </div>
            </dl>
        </div>

        {{-- Scheduled posts --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">{{ __('Upcoming posts') }}</h2>
            @forelse($scheduledPosts as $post)
                <div class="py-2 border-b border-slate-100 last:border-0">
                    <div class="text-sm font-medium truncate">{{ $post->title ?: Str::limit($post->content, 40) }}</div>
                    <div class="text-xs text-slate-500">{{ $post->scheduled_at?->format('M d, H:i') }} · {{ implode(', ', $post->platforms) }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No scheduled posts.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Recent reviews --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">{{ __('Latest reviews') }}</h2>
            <a href="{{ route('reviews.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('Open inbox') }} →</a>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($recentReviews as $review)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ $review->author_name }}</span>
                        <span class="text-amber-500 text-sm">{{ str_repeat('★', (int) $review->rating) }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $review->content }}</p>
                    <div class="mt-2 text-xs text-slate-400">{{ config("mapx.platforms.{$review->platform}.name") }} · {{ $review->branch?->name }} · {{ $review->review_date?->diffForHumans() }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No reviews yet. Connect your platforms to start collecting reviews.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
