@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <div class="text-center">
        <h1 class="text-xl font-semibold">{{ $campaign->branch?->name }}</h1>
        <p class="mt-2 text-slate-500 text-sm">{{ __('How was your experience with us today?') }}</p>

        <form method="POST" action="{{ route('feedback.rate', $campaign->slug) }}" class="mt-6">
            @csrf
            <div class="flex flex-wrap justify-center gap-2" dir="ltr">
                @foreach(range(1, $campaign->rating_scale) as $value)
                    <button name="rating" value="{{ $value }}"
                            class="size-12 rounded-xl border-2 border-slate-200 text-lg font-bold hover:border-amber-400 hover:bg-amber-50 transition
                                   {{ $value >= $campaign->threshold ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $value }}
                    </button>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-slate-400">1 = {{ __('Poor') }} · {{ $campaign->rating_scale }} = {{ __('Excellent') }}</p>
        </form>
    </div>
@endsection
