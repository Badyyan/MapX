@extends('layouts.guest')

@section('title', $campaign->branch?->name)

@section('content')
    <div class="text-center py-6">
        <div class="text-5xl">🙏</div>
        <h1 class="mt-4 text-xl font-semibold">{{ __('Thank you!') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('Your feedback has been passed to the :branch team. We appreciate you helping us improve.', ['branch' => $campaign->branch?->name]) }}</p>
    </div>
@endsection
