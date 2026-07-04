@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <h1 class="text-lg font-semibold">{{ __('We’re sorry to hear that 😔') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ __('Tell us what went wrong — the manager will personally review your feedback.') }}</p>

    <form method="POST" action="{{ route('feedback.store', $campaign->slug) }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="rating" value="{{ $rating }}">
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('What happened?') }}</label>
            <textarea name="comment" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="{{ __('Your feedback…') }}"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Your name (optional)') }}</label>
            <input name="customer_name" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Phone (optional)') }}</label>
                <input name="customer_phone" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Email (optional)') }}</label>
                <input type="email" name="customer_email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Send feedback') }}</button>
    </form>
@endsection
