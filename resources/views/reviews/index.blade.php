@extends('layouts.app')

@section('title', __('Reviews inbox'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2 text-sm" role="search">
            <div class="relative">
                <x-icon name="search" class="size-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}" class="input !ps-9 w-44" aria-label="{{ __('Search…') }}">
            </div>
            <select name="platform" class="input !w-auto" aria-label="{{ __('Platform') }}">
                <option value="">{{ __('All platforms') }}</option>
                @foreach($platforms as $key => $platform)
                    <option value="{{ $key }}" @selected(request('platform') === $key)>{{ $platform['name'] }}</option>
                @endforeach
            </select>
            <select name="branch_id" class="input !w-auto" aria-label="{{ __('Branch') }}">
                <option value="">{{ __('All branches') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="rating" class="input !w-auto" aria-label="{{ __('Any rating') }}">
                <option value="">{{ __('Any rating') }}</option>
                @foreach([5, 4, 3, 2, 1] as $stars)
                    <option value="{{ $stars }}" @selected(request('rating') == $stars)>{{ $stars }} ★</option>
                @endforeach
            </select>
            <select name="sentiment" class="input !w-auto" aria-label="{{ __('Any sentiment') }}">
                <option value="">{{ __('Any sentiment') }}</option>
                <option value="positive" @selected(request('sentiment') === 'positive')>{{ __('Positive') }}</option>
                <option value="neutral" @selected(request('sentiment') === 'neutral')>{{ __('Neutral') }}</option>
                <option value="negative" @selected(request('sentiment') === 'negative')>{{ __('Negative') }}</option>
            </select>
            <select name="replied" class="input !w-auto" aria-label="{{ __('Replied') }}">
                <option value="">{{ __('All') }}</option>
                <option value="no" @selected(request('replied') === 'no')>{{ __('Unanswered') }}</option>
                <option value="yes" @selected(request('replied') === 'yes')>{{ __('Replied') }}</option>
            </select>
            <button class="btn btn-primary"><x-icon name="search" class="size-4" /> {{ __('Filter') }}</button>
        </form>

        @if($pendingApprovals > 0)
            <a href="{{ route('reviews.approvals') }}" class="btn btn-secondary !text-amber-700 !ring-amber-300 hover:!bg-amber-50">
                <x-icon name="clock" class="size-4" />
                {{ __(':count replies awaiting approval', ['count' => $pendingApprovals]) }}
            </a>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($reviews as $review)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div class="size-10 shrink-0 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 grid place-items-center font-semibold text-slate-500">
                            {{ mb_substr($review->author_name ?? '؟', 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-slate-900">{{ $review->author_name ?? __('Anonymous') }}</p>
                            <p class="text-xs text-slate-400">
                                {{ config("mapx.platforms.{$review->platform}.name", $review->platform) }}
                                · {{ $review->branch?->name }}
                                · {{ $review->review_date?->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <x-rating :value="$review->rating" class="size-4" />
                        <span class="{{ $review->sentiment === 'positive' ? 'badge badge-success' : ($review->sentiment === 'negative' ? 'badge badge-danger' : 'badge badge-neutral') }}">
                            {{ __(ucfirst($review->sentiment)) }}
                        </span>
                    </div>
                </div>

                <p class="mt-3.5 text-sm text-slate-700 leading-relaxed" dir="auto">{{ $review->content }}</p>

                @foreach($review->replies as $reply)
                    <div class="mt-3.5 ms-6 rounded-xl bg-slate-50 ring-1 ring-slate-900/[0.04] p-3.5 text-sm">
                        <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5">
                            <span class="inline-flex items-center gap-1.5">
                                @if($reply->source === 'auto_rule')
                                    <x-icon name="bot" class="size-3.5" /> {{ __('Auto-reply') }}
                                @elseif($reply->source === 'ai')
                                    <x-icon name="sparkles" class="size-3.5" /> {{ __('AI-assisted reply') }}
                                @else
                                    <x-icon name="send" class="size-3.5" /> {{ __('Reply') }}
                                @endif
                                @if($reply->user) · {{ $reply->user->name }} @endif
                            </span>
                            <span class="{{ $reply->status === 'sent' ? 'badge badge-success' : ($reply->status === 'pending_approval' ? 'badge badge-warning' : 'badge badge-neutral') }}">
                                {{ __(ucfirst(str_replace('_', ' ', $reply->status))) }}
                            </span>
                        </div>
                        <p class="text-slate-600" dir="auto">{{ $reply->content }}</p>
                    </div>
                @endforeach

                @if(auth()->user()->hasPermission('reviews.reply') && ! $review->is_replied)
                    <form method="POST" action="{{ route('reviews.reply', $review) }}" class="mt-4 flex items-start gap-2.5" data-review="{{ $review->id }}">
                        @csrf
                        <textarea name="content" rows="2" required placeholder="{{ __('Write a reply…') }}" class="input flex-1 resize-y" aria-label="{{ __('Write a reply…') }}"></textarea>
                        <input type="hidden" name="ai_assisted" value="0">
                        <div class="flex flex-col gap-2">
                            <button class="btn btn-primary btn-sm !py-2"><x-icon name="send" class="size-3.5" /> {{ __('Send') }}</button>
                            <button type="button" onclick="suggestReply(this, {{ $review->id }})" class="btn btn-ai btn-sm !py-2">
                                <x-icon name="sparkles" class="size-3.5" /> {{ __('AI suggest') }}
                            </button>
                        </div>
                    </form>
                @endif
            </article>
        @empty
            <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                <x-icon name="inbox" class="size-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ __('No reviews match your filters.') }}</p>
            </div>
        @endforelse
    </div>

    {{ $reviews->links() }}

    @push('scripts')
    <script>
        async function suggestReply(button, reviewId) {
            const original = button.innerHTML;
            button.innerHTML = '…';
            button.disabled = true;
            try {
                const response = await fetch(`{{ url('/reviews') }}/${reviewId}/suggest`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                const form = button.closest('form');
                form.querySelector('textarea').value = data.suggestion;
                form.querySelector('input[name=ai_assisted]').value = '1';
            } catch (e) {
                alert('{{ __('Could not generate a suggestion.') }}');
            } finally {
                button.innerHTML = original;
                button.disabled = false;
            }
        }
    </script>
    @endpush
@endsection
