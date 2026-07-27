@extends('layouts.app')

@section('title', __('Checkout'))

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/moyasar-payment-form@{{ config('services.moyasar.form_version') }}/dist/moyasar.css">
@endpush

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('billing.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
            <x-icon name="arrow-right" class="size-4 rotate-180 rtl:rotate-0" />
            {{ __('Back to billing') }}
        </a>

        {{-- Order summary --}}
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Order summary') }}</h2>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">{{ $plan === 'yearly' ? __('Yearly') : __('Monthly') }}
                    · {{ __(':price SAR × :count branches', ['price' => $pricePerBranch, 'count' => $branchCount]) }}</span>
                <span class="text-slate-500 tabular-nums">{{ number_format($amount, 2) }} {{ $currency }}</span>
            </div>
            @if($plan === 'yearly')
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="badge badge-brand">{{ __('Save 20%') }}</span>
                    <span class="text-slate-400">{{ __('2 months free vs monthly billing') }}</span>
                </div>
            @endif
            <div class="mt-4 pt-4 border-t border-slate-900/[0.06] flex items-center justify-between">
                <span class="font-semibold text-slate-900">{{ __('Total due today') }}</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    {{ number_format($amount, 2) }} <span class="text-base font-normal text-slate-400">{{ $currency }}</span>
                </span>
            </div>
        </div>

        {{-- Payment form (rendered by Moyasar; card data never touches MapX) --}}
        <div class="card p-6">
            <h2 class="section-title mb-5 flex items-center gap-2">
                <x-icon name="credit-card" class="size-4 text-slate-400" />
                {{ __('Payment details') }}
            </h2>

            <div class="mysr-form"></div>

            <p class="mt-5 flex items-start gap-2 text-xs text-slate-400">
                <x-icon name="lock" class="size-3.5 shrink-0 mt-px" />
                {{ __('Payments are processed by Moyasar. Card details are entered directly into Moyasar\'s secure form and never reach MapX servers.') }}
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Loaded from Moyasar's CDN on purpose: the card fields live inside
         their script, which keeps MapX's bundle out of PCI scope and is
         required for Apple Pay to work. --}}
    <script src="https://unpkg.com/moyasar-payment-form@{{ config('services.moyasar.form_version') }}/dist/moyasar.umd.js" crossorigin="anonymous"></script>
    <script>
        Moyasar.init({
            element: '.mysr-form',
            // Amount is computed server-side and re-verified on the callback;
            // this value is display/charge input only.
            amount: @json($amountMinor),
            currency: @json($currency),
            description: @json($description),
            publishable_api_key: @json($publishableKey),
            callback_url: @json($callbackUrl),
            methods: @json($methods),
            metadata: @json($metadata),
            language: @json(app()->getLocale()),
            // Save the card so renewals can be charged without the customer
            // re-entering it — Moyasar has no recurring subscriptions.
            credit_card: { save_card: true },
            apple_pay: {
                country: @json(config('services.moyasar.apple_pay_country')),
                label: @json(config('services.moyasar.apple_pay_label')),
                validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate',
            },
        });
    </script>
@endpush
