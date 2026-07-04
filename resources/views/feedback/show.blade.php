@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <div class="text-center">
        <span class="mx-auto mb-4 grid place-items-center size-14 rounded-2xl bg-brand-50 text-brand-600">
            <x-icon name="heart-handshake" class="size-7" />
        </span>
        <h1 class="text-xl font-semibold text-slate-900">{{ $campaign->branch?->name }}</h1>
        <p class="mt-2 text-slate-500 text-sm">{{ __('How was your experience with us today?') }}</p>

        <form method="POST" action="{{ route('feedback.rate', $campaign->slug) }}" class="mt-7">
            @csrf
            <div class="flex flex-wrap justify-center gap-2" dir="ltr" role="group" aria-label="{{ __('How was your experience with us today?') }}">
                @foreach(range(1, $campaign->rating_scale) as $value)
                    <button name="rating" value="{{ $value }}"
                            class="size-12 rounded-xl ring-1 ring-inset text-lg font-bold transition-all duration-150
                                   hover:scale-110 hover:shadow-md active:scale-95
                                   {{ $value >= $campaign->threshold
                                       ? 'ring-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:ring-emerald-400'
                                       : 'ring-slate-200 text-rose-500 hover:bg-rose-50 hover:ring-rose-300' }}">
                        {{ $value }}
                    </button>
                @endforeach
            </div>
            <p class="mt-5 text-xs text-slate-400">1 = {{ __('Poor') }} · {{ $campaign->rating_scale }} = {{ __('Excellent') }}</p>
        </form>
    </div>
@endsection
