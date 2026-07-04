@extends('layouts.app')

@section('title', __('Auto-reply rules'))

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500 max-w-2xl">{{ __('Rules run on every new review, matched by rating, sentiment, keywords, platform and language. The highest-priority match wins.') }}</p>
        <a href="{{ route('auto-rules.create') }}" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">+ {{ __('New rule') }}</a>
    </div>

    <div class="space-y-3">
        @forelse($rules as $rule)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $rule->name }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $rule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $rule->is_active ? __('Active') : __('Paused') }}
                        </span>
                        @if($rule->require_approval)
                            <span class="rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs">{{ __('Needs approval') }}</span>
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-slate-500 space-x-2">
                        <span>★ {{ $rule->min_rating }}–{{ $rule->max_rating }}</span>
                        @if($rule->sentiment)<span>· {{ __(ucfirst($rule->sentiment)) }}</span>@endif
                        @if($rule->platforms)<span>· {{ implode(', ', $rule->platforms) }}</span>@endif
                        @if($rule->keywords)<span>· 🔑 {{ implode(', ', $rule->keywords) }}</span>@endif
                        @if($rule->delay_minutes)<span>· ⏱ {{ $rule->delay_minutes }} {{ __('min delay') }}</span>@endif
                        <span>· {{ __('Priority') }} {{ $rule->priority }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('auto-rules.toggle', $rule) }}">
                        @csrf
                        <button class="rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-xs hover:bg-slate-50">{{ $rule->is_active ? __('Pause') : __('Activate') }}</button>
                    </form>
                    <a href="{{ route('auto-rules.edit', $rule) }}" class="rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-xs hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('auto-rules.destroy', $rule) }}" onsubmit="return confirm('{{ __('Delete this rule?') }}')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-white border border-red-200 text-red-600 px-3 py-1.5 text-xs hover:bg-red-50">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ __('No auto-reply rules yet. Create one to answer reviews automatically.') }}
            </div>
        @endforelse
    </div>
@endsection
