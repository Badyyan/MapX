@extends('layouts.app')

@section('title', __('Posts'))

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ __('Publish news, offers and events to all your listings from one place.') }}</p>
        @if(auth()->user()->hasPermission('posts.manage'))
            <a href="{{ route('posts.create') }}" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">+ {{ __('New post') }}</a>
        @endif
    </div>

    <div class="space-y-3">
        @forelse($posts as $post)
            <a href="{{ route('posts.show', $post) }}" class="block bg-white rounded-2xl border border-slate-200 p-5 hover:border-brand-400 transition">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold truncate">{{ $post->title ?: Str::limit($post->content, 60) }}</span>
                            @if($post->ai_generated)<span class="rounded-full bg-violet-100 text-violet-700 px-2 py-0.5 text-xs">✨ AI</span>@endif
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ implode(', ', array_map(fn ($p) => config("mapx.platforms.$p.name", $p), $post->platforms)) }}
                            · {{ count($post->branch_ids) }} {{ __('branches') }}
                            @if($post->scheduled_at) · 🕓 {{ $post->scheduled_at->format('M d, H:i') }} @endif
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium
                        {{ match($post->status) {
                            'published' => 'bg-emerald-100 text-emerald-700',
                            'scheduled' => 'bg-blue-100 text-blue-700',
                            'publishing' => 'bg-amber-100 text-amber-700',
                            'partial' => 'bg-orange-100 text-orange-700',
                            'failed' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-600',
                        } }}">
                        {{ __(ucfirst($post->status)) }}
                    </span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ __('No posts yet. Create your first post and publish it everywhere at once.') }}
            </div>
        @endforelse
    </div>

    {{ $posts->links() }}
@endsection
