@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <div class="text-center py-6">
        <span class="mx-auto grid place-items-center size-16 rounded-full bg-emerald-50 text-emerald-500">
            <x-icon name="check-circle" class="size-8" />
        </span>
        <h1 class="mt-5 text-xl font-semibold text-slate-900">{{ __('Thank you!') }}</h1>
        <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ __('Your feedback has been passed to the :branch team. We appreciate you helping us improve.', ['branch' => $campaign->branch?->name]) }}</p>
    </div>
@endsection
