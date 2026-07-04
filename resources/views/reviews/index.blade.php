@extends('layouts.app')

@section('title', __('Reviews inbox'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2 text-sm">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 w-44">
            <select name="platform" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">{{ __('All platforms') }}</option>
                @foreach($platforms as $key => $platform)
                    <option value="{{ $key }}" @selected(request('platform') === $key)>{{ $platform['name'] }}</option>
                @endforeach
            </select>
            <select name="branch_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">{{ __('All branches') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="rating" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">{{ __('Any rating') }}</option>
                @foreach([5, 4, 3, 2, 1] as $stars)
                    <option value="{{ $stars }}" @selected(request('rating') == $stars)>{{ $stars }} ★</option>
                @endforeach
            </select>
            <select name="sentiment" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">{{ __('Any sentiment') }}</option>
                <option value="positive" @selected(request('sentiment') === 'positive')>{{ __('Positive') }}</option>
                <option value="neutral" @selected(request('sentiment') === 'neutral')>{{ __('Neutral') }}</option>
                <option value="negative" @selected(request('sentiment') === 'negative')>{{ __('Negative') }}</option>
            </select>
            <select name="replied" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">{{ __('All') }}</option>
                <option value="no" @selected(request('replied') === 'no')>{{ __('Unanswered') }}</option>
                <option value="yes" @selected(request('replied') === 'yes')>{{ __('Replied') }}</option>
            </select>
            <button class="rounded-lg bg-brand-600 text-white px-4 py-2 font-medium">{{ __('Filter') }}</button>
        </form>

        @if($pendingApprovals > 0)
            <a href="{{ route('reviews.approvals') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-100 text-amber-800 px-4 py-2 text-sm font-medium hover:bg-amber-200">
                ⏳ {{ __(':count replies awaiting approval', ['count' => $pendingApprovals]) }}
            </a>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($reviews as $review)
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-slate-200 grid place-items-center font-semibold text-slate-600">
                            {{ mb_substr($review->author_name ?? '؟', 0, 1) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ $review->author_name ?? __('Anonymous') }}</div>
                            <div class="text-xs text-slate-500">
                                {{ config("mapx.platforms.{$review->platform}.name", $review->platform) }}
                                · {{ $review->branch?->name }}
                                · {{ $review->review_date?->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-amber-500">{{ str_repeat('★', (int) $review->rating) }}<span class="text-slate-200">{{ str_repeat('★', max(0, 5 - (int) $review->rating)) }}</span></span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $review->sentiment === 'positive' ? 'bg-emerald-100 text-emerald-700' : ($review->sentiment === 'negative' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                            {{ __(ucfirst($review->sentiment)) }}
                        </span>
                    </div>
                </div>

                <p class="mt-3 text-sm text-slate-700" dir="auto">{{ $review->content }}</p>

                @foreach($review->replies as $reply)
                    <div class="mt-3 ms-6 rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>
                                ↳ {{ $reply->source === 'auto_rule' ? __('Auto-reply') : ($reply->source === 'ai' ? __('AI-assisted reply') : __('Reply')) }}
                                @if($reply->user) · {{ $reply->user->name }} @endif
                            </span>
                            <span class="rounded-full px-2 py-0.5 {{ $reply->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($reply->status === 'pending_approval' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600') }}">
                                {{ __(ucfirst(str_replace('_', ' ', $reply->status))) }}
                            </span>
                        </div>
                        <p dir="auto">{{ $reply->content }}</p>
                    </div>
                @endforeach

                @if(auth()->user()->hasPermission('reviews.reply') && ! $review->is_replied)
                    <form method="POST" action="{{ route('reviews.reply', $review) }}" class="mt-4 flex items-start gap-2" data-review="{{ $review->id }}">
                        @csrf
                        <textarea name="content" rows="2" required placeholder="{{ __('Write a reply…') }}"
                                  class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <input type="hidden" name="ai_assisted" value="0">
                        <div class="flex flex-col gap-2">
                            <button class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">{{ __('Send') }}</button>
                            <button type="button" onclick="suggestReply(this, {{ $review->id }})"
                                    class="rounded-lg bg-violet-50 text-violet-700 border border-violet-200 px-4 py-2 text-sm hover:bg-violet-100">
                                ✨ {{ __('AI suggest') }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ __('No reviews match your filters.') }}
            </div>
        @endforelse
    </div>

    {{ $reviews->links() }}

    @push('scripts')
    <script>
        async function suggestReply(button, reviewId) {
            const original = button.textContent;
            button.textContent = '…';
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
                button.textContent = original;
                button.disabled = false;
            }
        }
    </script>
    @endpush
@endsection
