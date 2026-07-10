@extends('layouts.app')

@section('title', __('Map Facebook pages'))

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <span class="grid place-items-center size-12 shrink-0 rounded-2xl text-white font-bold text-xl shadow-sm" style="background: #1877F2">f</span>
                <div>
                    <h2 class="font-semibold text-slate-900">{{ __('Your Facebook pages') }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Link each page to the branch it represents. Linked Instagram business accounts connect automatically.') }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($pages as $page)
                <div class="card p-5 flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $page['name'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ __('Page ID') }}: <span class="tabular-nums" dir="ltr">{{ $page['id'] }}</span>
                            @if(! empty($page['instagram_business_account']['username']))
                                · Instagram: {{ '@'.$page['instagram_business_account']['username'] }}
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.meta.connect-page') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                        <select name="branch_id" required class="input !w-auto !py-2" aria-label="{{ __('Branch') }}">
                            <option value="" disabled selected>{{ __('Choose branch…') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-sm !py-2"><x-icon name="link" class="size-3.5" /> {{ __('Link') }}</button>
                    </form>
                </div>
            @empty
                <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center text-slate-400">
                    {{ __('No pages found on this Facebook account.') }}
                </div>
            @endforelse
        </div>

        <a href="{{ route('integrations.index') }}" class="btn btn-ghost">{{ __('Back to integrations') }}</a>
    </div>
@endsection
