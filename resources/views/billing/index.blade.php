@extends('layouts.app')

@section('title', __('Billing'))

@section('content')
    @unless($gatewayConfigured)
        <div class="card flex items-center gap-3 px-4 py-3 !ring-amber-600/20 bg-amber-50/60 text-sm text-amber-800" role="status">
            <x-icon name="alert-triangle" class="size-4.5 shrink-0 text-amber-600" />
            {{ __('Sandbox billing: no payment gateway is configured, so subscriptions activate without charging. Add Stripe keys (see INTEGRATIONS.md) to take real payments.') }}
        </div>
    @endunless

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Current plan --}}
            <div class="card p-6">
                <h2 class="section-title mb-5">{{ __('Current plan') }}</h2>
                @if($subscription)
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="grid place-items-center size-12 rounded-2xl bg-brand-50 text-brand-600">
                                <x-icon name="credit-card" class="size-6" />
                            </span>
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-slate-900 capitalize">{{ __(ucfirst($subscription->plan)) }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    @if($subscription->onTrial())
                                        {{ __('Trial ends :date', ['date' => $subscription->trial_ends_at->format('M d, Y')]) }}
                                        · {{ __('up to :limit branches', ['limit' => $subscription->branch_limit]) }}
                                    @elseif($subscription->current_period_end)
                                        {{ __('Renews :date', ['date' => $subscription->current_period_end->format('M d, Y')]) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="{{ $subscription->isActive() ? 'badge badge-success !text-sm !px-3 !py-1' : 'badge badge-danger !text-sm !px-3 !py-1' }}">
                            {{ __(ucfirst($subscription->status)) }}
                        </span>
                    </div>
                    @if(! $subscription->isActive())
                        <div class="mt-5 flex items-center gap-3 rounded-xl bg-red-50 ring-1 ring-inset ring-red-600/15 text-red-700 px-4 py-3 text-sm" role="alert">
                            <x-icon name="lock" class="size-4.5 shrink-0" />
                            {{ __('Your subscription is inactive and platform features are locked. Choose a plan below to unlock.') }}
                        </div>
                    @endif
                @else
                    <p class="text-sm text-slate-400">{{ __('No subscription found.') }}</p>
                @endif
            </div>

            {{-- Plans --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="card p-6">
                    <p class="font-semibold text-slate-900">{{ __('Monthly') }}</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 tabular-nums">{{ number_format($monthlyPrice, 2) }} <span class="text-base font-normal text-slate-400">{{ $currency }}/{{ __('mo') }}</span></p>
                    <p class="mt-1.5 text-sm text-slate-500">{{ __(':price SAR × :count branches', ['price' => $pricePerBranch, 'count' => $branchCount]) }}</p>
                    <form method="POST" action="{{ route('billing.subscribe') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan" value="monthly">
                        <button class="btn btn-secondary w-full">{{ $gatewayConfigured ? __('Pay monthly — secure checkout') : __('Choose monthly') }}</button>
                    </form>
                </div>
                <div class="card p-6 relative !ring-2 !ring-brand-500">
                    <span class="absolute -top-3 start-5 badge badge-brand !bg-brand-600 !text-white !ring-0 shadow-sm">{{ __('Save 20%') }}</span>
                    <p class="font-semibold text-slate-900">{{ __('Yearly') }}</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 tabular-nums">{{ number_format($yearlyPrice, 2) }} <span class="text-base font-normal text-slate-400">{{ $currency }}/{{ __('yr') }}</span></p>
                    <p class="mt-1.5 text-sm text-slate-500">{{ __('2 months free vs monthly billing') }}</p>
                    <form method="POST" action="{{ route('billing.subscribe') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan" value="yearly">
                        <button class="btn btn-primary w-full">{{ $gatewayConfigured ? __('Pay yearly — secure checkout') : __('Choose yearly') }}</button>
                    </form>
                </div>
            </div>

            @if($subscription?->status === 'active')
                <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('{{ __('Cancel your subscription?') }}')">
                    @csrf
                    <button class="text-sm text-red-500 hover:text-red-600 hover:underline">{{ __('Cancel subscription') }}</button>
                </form>
            @endif
        </div>

        {{-- Invoices --}}
        <div class="card p-6 h-fit">
            <h2 class="section-title mb-5 flex items-center gap-2"><x-icon name="receipt" class="size-4 text-slate-400" /> {{ __('Invoices') }}</h2>
            <div class="space-y-3">
                @forelse($invoices as $invoice)
                    <div class="rounded-xl ring-1 ring-slate-900/[0.06] p-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-900">{{ $invoice->number }}</span>
                            <span class="{{ $invoice->status === 'paid' ? 'badge badge-success' : ($invoice->status === 'failed' ? 'badge badge-danger' : 'badge badge-warning') }}">
                                {{ __(ucfirst($invoice->status)) }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400 tabular-nums">
                            {{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}
                            · {{ $invoice->branch_count }} {{ __('branches') }}
                            · {{ $invoice->created_at->format('M d, Y') }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No invoices yet.') }}</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $invoices->links() }}</div>
        </div>
    </div>
@endsection
