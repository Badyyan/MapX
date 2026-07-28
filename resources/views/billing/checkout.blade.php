@extends('layouts.app')

@section('title', __('Checkout'))

@push('head')
    {{-- Third-party CSS on the critical path: preconnect so DNS + TLS to the
         CDN don't serialise ahead of the stylesheet fetch. --}}
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/moyasar-payment-form@{{ config('services.moyasar.form_version') }}/dist/moyasar.css">
@endpush

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('billing.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-right" class="size-4 rotate-180 rtl:rotate-0" />
            {{ __('Back to billing') }}
        </a>

        {{-- Order summary --}}
        <div class="card p-6">
            <h2 class="section-title mb-5">{{ __('Order summary') }}</h2>
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="text-slate-600">{{ $plan === 'yearly' ? __('Yearly') : __('Monthly') }}
                    · {{ __(':price SAR × :count branches', ['price' => $pricePerBranch, 'count' => $branchCount]) }}</span>
                <span class="text-slate-600 tabular-nums shrink-0">{{ number_format($amount, 2) }} {{ $currency }}</span>
            </div>
            @if($plan === 'yearly')
                <div class="mt-2 flex items-center justify-between gap-4 text-sm">
                    <span class="badge badge-brand">{{ __('Save 20%') }}</span>
                    <span class="text-slate-600">{{ __('2 months free vs monthly billing') }}</span>
                </div>
            @endif
            <div class="mt-4 pt-4 border-t border-slate-900/[0.06] flex items-center justify-between gap-4">
                <span class="font-semibold text-slate-900">{{ __('Total due today') }}</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 tabular-nums shrink-0">
                    {{ number_format($amount, 2) }} <span class="text-base font-normal text-slate-600">{{ $currency }}</span>
                </span>
            </div>
        </div>

        {{-- Payment form (rendered by Moyasar; card data never touches MapX) --}}
        <div class="card p-6">
            <h2 class="section-title mb-5 flex items-center gap-2">
                <x-icon name="credit-card" class="size-4 text-slate-600" />
                {{ __('Payment details') }}
            </h2>

            {{-- The form is injected by a third-party script. Everything below
                 exists because that script can fail — ad blockers, corporate
                 proxies, a CDN outage, a bad MOYASAR_FORM_VERSION. Without a
                 fallback the customer sees an empty box on a payment page and
                 has no idea whether the fault is theirs. --}}
            <div id="mysr-loading" class="flex items-center gap-2.5 text-sm text-slate-600 py-6">
                <span class="size-4 rounded-full border-2 border-slate-300 border-t-brand-600 animate-spin" aria-hidden="true"></span>
                {{ __('Loading secure payment form…') }}
            </div>

            <div class="mysr-form" role="group" aria-label="{{ __('Payment details') }}" aria-busy="true"></div>

            <div id="mysr-fallback" hidden role="alert"
                 class="flex items-start gap-3 rounded-xl bg-red-50 ring-1 ring-inset ring-red-600/15 text-red-700 px-4 py-3 text-sm">
                <x-icon name="alert-triangle" class="size-4.5 shrink-0 mt-0.5" />
                <span>
                    {{ __('The secure payment form could not be loaded. This is usually an ad blocker or a network restriction.') }}
                    <a href="{{ route('billing.checkout', ['plan' => $plan]) }}" class="underline font-medium">{{ __('Try again') }}</a>
                    {{ __('or contact support — you have not been charged.') }}
                </span>
            </div>

            <noscript>
                <div class="flex items-start gap-3 rounded-xl bg-amber-50 ring-1 ring-inset ring-amber-600/20 text-amber-800 px-4 py-3 text-sm">
                    <x-icon name="alert-triangle" class="size-4.5 shrink-0 mt-0.5" />
                    {{ __('JavaScript is required to enter card details securely. Please enable it and reload this page.') }}
                </div>
            </noscript>

            <p class="mt-5 flex items-start gap-2 text-xs text-slate-600">
                <x-icon name="lock" class="size-3.5 shrink-0 mt-px" />
                {{ __('Payments are processed by Moyasar. Card details are entered directly into Moyasar\'s secure form and never reach MapX servers.') }}
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Defined before the CDN script so its onerror handler can reach it.
        function mapxPaymentFormFailed() {
            document.getElementById('mysr-loading')?.setAttribute('hidden', '');
            const fallback = document.getElementById('mysr-fallback');
            if (fallback) fallback.hidden = false;
            document.querySelector('.mysr-form')?.setAttribute('aria-busy', 'false');
        }
    </script>

    {{-- Loaded from Moyasar's CDN on purpose: the card fields live inside
         their script, which keeps MapX's bundle out of PCI scope and is
         required for Apple Pay to work.
         TODO: add an `integrity` SRI hash. It must be computed against the
         real file — this build environment cannot reach unpkg, and a wrong
         hash would make the browser refuse the script permanently. --}}
    <script src="https://unpkg.com/moyasar-payment-form@{{ config('services.moyasar.form_version') }}/dist/moyasar.umd.js"
            crossorigin="anonymous" onerror="mapxPaymentFormFailed()"></script>

    <script>
        // Guard rather than assume: a 404 on the CDN would otherwise throw an
        // uncaught ReferenceError and leave the page silently broken.
        if (typeof Moyasar === 'undefined') {
            mapxPaymentFormFailed();
        } else {
            try {
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

                document.getElementById('mysr-loading')?.setAttribute('hidden', '');
                document.querySelector('.mysr-form')?.setAttribute('aria-busy', 'false');
            } catch (e) {
                mapxPaymentFormFailed();
            }
        }
    </script>
@endpush
