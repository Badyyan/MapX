@extends('layouts.app')

@section('title', __('Auto-reply rules'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 max-w-2xl">{{ __('Rules run on every new review, matched by rating, sentiment, keywords, platform and language. The highest-priority match wins.') }}</p>
        <a href="{{ route('auto-rules.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New rule') }}</a>
    </div>

    <div class="space-y-3 animate-stagger">
        @forelse($rules as $rule)
            <div class="card card-hover p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-start gap-3.5 min-w-0">
                    <span class="grid place-items-center size-10 shrink-0 rounded-xl {{ $rule->is_active ? 'bg-brand-50 text-brand-600' : 'bg-slate-100 text-slate-400' }}">
                        <x-icon name="bot" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ $rule->name }}</span>
                            <span class="{{ $rule->is_active ? 'badge badge-success' : 'badge badge-neutral' }}">{{ $rule->is_active ? __('Active') : __('Paused') }}</span>
                            @if($rule->require_approval)
                                <span class="badge badge-warning"><x-icon name="clock" class="size-3" /> {{ __('Needs approval') }}</span>
                            @endif
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                            <span class="inline-flex items-center gap-1"><x-icon name="star" class="size-3" /> {{ $rule->min_rating }}–{{ $rule->max_rating }}</span>
                            @if($rule->sentiment)<span>{{ __(ucfirst($rule->sentiment)) }}</span>@endif
                            @if($rule->platforms)<span>{{ implode(', ', $rule->platforms) }}</span>@endif
                            @if($rule->keywords)<span class="inline-flex items-center gap-1"><x-icon name="key" class="size-3" /> {{ implode(', ', $rule->keywords) }}</span>@endif
                            @if($rule->delay_minutes)<span class="inline-flex items-center gap-1"><x-icon name="timer" class="size-3" /> {{ $rule->delay_minutes }} {{ __('min delay') }}</span>@endif
                            <span>{{ __('Priority') }} {{ $rule->priority }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('auto-rules.toggle', $rule) }}">
                        @csrf
                        <button class="btn btn-secondary btn-sm">{{ $rule->is_active ? __('Pause') : __('Activate') }}</button>
                    </form>
                    <a href="{{ route('auto-rules.edit', $rule) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="size-3" /> {{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('auto-rules.destroy', $rule) }}" onsubmit="return confirm('{{ __('Delete this rule?') }}')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm"><x-icon name="trash" class="size-3" /> {{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                <x-icon name="bot" class="size-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ __('No auto-reply rules yet. Create one to answer reviews automatically.') }}</p>
            </div>
        @endforelse
    </div>
@endsection
