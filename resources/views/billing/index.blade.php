@extends('layouts.app')

@section('title', __('Billing'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Current plan --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-4">{{ __('Current plan') }}</h2>
                @if($subscription)
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="text-2xl font-bold capitalize">{{ __(ucfirst($subscription->plan)) }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                @if($subscription->onTrial())
                                    {{ __('Trial ends :date', ['date' => $subscription->trial_ends_at->format('M d, Y')]) }}
                                    · {{ __('up to :limit branches', ['limit' => $subscription->branch_limit]) }}
                                @elseif($subscription->current_period_end)
                                    {{ __('Renews :date', ['date' => $subscription->current_period_end->format('M d, Y')]) }}
                                @endif
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-medium
                            {{ $subscription->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ __(ucfirst($subscription->status)) }}
                        </span>
                    </div>
                    @if(! $subscription->isActive())
                        <div class="mt-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                            {{ __('Your subscription is inactive and platform features are locked. Choose a plan below to unlock.') }}
                        </div>
                    @endif
                @else
                    <p class="text-sm text-slate-500">{{ __('No subscription found.') }}</p>
                @endif
            </div>

            {{-- Plans --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border-2 border-slate-200 p-6">
                    <div class="font-semibold">{{ __('Monthly') }}</div>
                    <div class="mt-2 text-3xl font-bold">{{ number_format($monthlyPrice, 2) }} <span class="text-base font-normal text-slate-500">{{ $currency }}/{{ __('mo') }}</span></div>
                    <p class="mt-1 text-sm text-slate-500">{{ __(':price SAR × :count branches', ['price' => $pricePerBranch, 'count' => $branchCount]) }}</p>
                    <form method="POST" action="{{ route('billing.subscribe') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="plan" value="monthly">
                        <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Choose monthly') }}</button>
                    </form>
                </div>
                <div class="bg-white rounded-2xl border-2 border-brand-500 p-6 relative">
                    <span class="absolute -top-3 start-4 rounded-full bg-brand-600 text-white text-xs px-3 py-1">{{ __('Save 20%') }}</span>
                    <div class="font-semibold">{{ __('Yearly') }}</div>
                    <div class="mt-2 text-3xl font-bold">{{ number_format($yearlyPrice, 2) }} <span class="text-base font-normal text-slate-500">{{ $currency }}/{{ __('yr') }}</span></div>
                    <p class="mt-1 text-sm text-slate-500">{{ __('2 months free vs monthly billing') }}</p>
                    <form method="POST" action="{{ route('billing.subscribe') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="plan" value="yearly">
                        <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Choose yearly') }}</button>
                    </form>
                </div>
            </div>

            @if($subscription?->status === 'active')
                <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('{{ __('Cancel your subscription?') }}')">
                    @csrf
                    <button class="text-sm text-red-500 hover:underline">{{ __('Cancel subscription') }}</button>
                </form>
            @endif
        </div>

        {{-- Invoices --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 h-fit">
            <h2 class="font-semibold mb-4">{{ __('Invoices') }}</h2>
            <div class="space-y-3">
                @forelse($invoices as $invoice)
                    <div class="rounded-xl border border-slate-200 p-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $invoice->number }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($invoice->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ __(ucfirst($invoice->status)) }}
                            </span>
                        </div>
                        <div class="mt-1 text-slate-500 text-xs">
                            {{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}
                            · {{ $invoice->branch_count }} {{ __('branches') }}
                            · {{ $invoice->created_at->format('M d, Y') }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No invoices yet.') }}</p>
                @endforelse
            </div>
            <div class="mt-3">{{ $invoices->links() }}</div>
        </div>
    </div>
@endsection
