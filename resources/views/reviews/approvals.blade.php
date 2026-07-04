@extends('layouts.app')

@section('title', __('Pending approvals'))

@section('content')
    <p class="text-sm text-slate-500">{{ __('Auto-generated replies waiting for your approval before being sent to the platform.') }}</p>

    <div class="space-y-4">
        @forelse($pending as $reply)
            <article class="card p-5">
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2.5">
                    <span>{{ config("mapx.platforms.{$reply->review->platform}.name") }}</span>
                    <span>·</span>
                    <span>{{ $reply->review->branch?->name }}</span>
                    <span>·</span>
                    <span>{{ $reply->review->author_name }}</span>
                    <x-rating :value="$reply->review->rating" class="size-3" />
                </div>
                <p class="text-sm text-slate-700" dir="auto">{{ $reply->review->content }}</p>
                <div class="mt-3.5 rounded-xl bg-amber-50 ring-1 ring-inset ring-amber-600/15 p-4 text-sm">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700 mb-1.5">
                        <x-icon name="bot" class="size-3.5" /> {{ __('Proposed reply') }}
                    </p>
                    <p class="text-slate-700" dir="auto">{{ $reply->content }}</p>
                </div>
                <div class="mt-4 flex gap-2">
                    <form method="POST" action="{{ route('replies.approve', $reply) }}">
                        @csrf
                        <button class="btn btn-primary !bg-emerald-600 hover:!bg-emerald-500 !shadow-emerald-600/25">
                            <x-icon name="check" class="size-4" /> {{ __('Approve & send') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('replies.reject', $reply) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger"><x-icon name="x" class="size-4" /> {{ __('Discard') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                <x-icon name="check-circle" class="size-10 text-emerald-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ __('Nothing waiting for approval.') }}</p>
            </div>
        @endforelse
    </div>

    {{ $pending->links() }}
@endsection
