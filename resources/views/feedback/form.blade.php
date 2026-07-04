@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <h1 class="text-lg font-semibold text-slate-900">{{ __('We’re sorry to hear that') }}</h1>
    <p class="mt-1.5 text-sm text-slate-500">{{ __('Tell us what went wrong — the manager will personally review your feedback.') }}</p>

    <form method="POST" action="{{ route('feedback.store', $campaign->slug) }}" class="mt-7 space-y-4">
        @csrf
        <input type="hidden" name="rating" value="{{ $rating }}">
        <div>
            <label for="comment" class="label">{{ __('What happened?') }}</label>
            <textarea id="comment" name="comment" rows="4" class="input resize-y" placeholder="{{ __('Your feedback…') }}"></textarea>
        </div>
        <div>
            <label for="customer_name" class="label">{{ __('Your name (optional)') }}</label>
            <input id="customer_name" name="customer_name" class="input" autocomplete="name">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="customer_phone" class="label">{{ __('Phone (optional)') }}</label>
                <input id="customer_phone" name="customer_phone" class="input" dir="ltr" autocomplete="tel">
            </div>
            <div>
                <label for="customer_email" class="label">{{ __('Email (optional)') }}</label>
                <input id="customer_email" type="email" name="customer_email" class="input" dir="ltr" autocomplete="email">
            </div>
        </div>
        <button class="btn btn-primary w-full !mt-6"><x-icon name="send" class="size-4" /> {{ __('Send feedback') }}</button>
    </form>
@endsection
