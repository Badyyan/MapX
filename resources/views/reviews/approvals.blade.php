@extends('layouts.app')

@section('title', __('Pending approvals'))

@section('content')
    <p class="text-sm text-slate-500">{{ __('Auto-generated replies waiting for your approval before being sent to the platform.') }}</p>

    <div class="space-y-4">
        @forelse($pending as $reply)
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="text-xs text-slate-500 mb-2">
                    {{ config("mapx.platforms.{$reply->review->platform}.name") }} · {{ $reply->review->branch?->name }}
                    · {{ $reply->review->author_name }} · <span class="text-amber-500">{{ str_repeat('★', (int) $reply->review->rating) }}</span>
                </div>
                <p class="text-sm text-slate-700" dir="auto">{{ $reply->review->content }}</p>
                <div class="mt-3 rounded-xl bg-amber-50 border border-amber-200 p-3 text-sm" dir="auto">
                    <div class="text-xs text-amber-700 mb-1">{{ __('Proposed reply') }}</div>
                    {{ $reply->content }}
                </div>
                <div class="mt-3 flex gap-2">
                    <form method="POST" action="{{ route('replies.approve', $reply) }}">
                        @csrf
                        <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">✓ {{ __('Approve & send') }}</button>
                    </form>
                    <form method="POST" action="{{ route('replies.reject', $reply) }}">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-white border border-red-300 text-red-600 px-4 py-2 text-sm hover:bg-red-50">✕ {{ __('Discard') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ __('Nothing waiting for approval. 🎉') }}
            </div>
        @endforelse
    </div>

    {{ $pending->links() }}
@endsection
