@extends('layouts.app')

@section('title', __('Posts'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('Publish news, offers and events to all your listings from one place.') }}</p>
        @if(auth()->user()->hasPermission('posts.manage'))
            <a href="{{ route('posts.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New post') }}</a>
        @endif
    </div>

    <div class="space-y-3 animate-stagger">
        @forelse($posts as $post)
            <a href="{{ route('posts.show', $post) }}" class="card card-hover p-5 block">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5 min-w-0">
                        <span class="grid place-items-center size-10 shrink-0 rounded-xl bg-brand-50 text-brand-600">
                            <x-icon name="pen-square" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-semibold text-slate-900 truncate">{{ $post->title ?: Str::limit($post->content, 60) }}</span>
                                @if($post->ai_generated)
                                    <span class="badge badge-brand shrink-0 !bg-violet-50 !text-violet-700 !ring-violet-600/15"><x-icon name="sparkles" class="size-3" /> AI</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ implode(', ', array_map(fn ($p) => config("mapx.platforms.$p.name", $p), $post->platforms)) }}
                                · {{ count($post->branch_ids) }} {{ __('branches') }}
                                @if($post->scheduled_at) · {{ $post->scheduled_at->format('M d, H:i') }} @endif
                            </p>
                        </div>
                    </div>
                    <span class="{{ match($post->status) {
                        'published' => 'badge badge-success',
                        'scheduled' => 'badge badge-info',
                        'publishing' => 'badge badge-warning',
                        'partial' => 'badge badge-warning',
                        'failed' => 'badge badge-danger',
                        default => 'badge badge-neutral',
                    } }}">
                        {{ __(ucfirst($post->status)) }}
                    </span>
                </div>
            </a>
        @empty
            <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                <x-icon name="pen-square" class="size-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ __('No posts yet. Create your first post and publish it everywhere at once.') }}</p>
            </div>
        @endforelse
    </div>

    {{ $posts->links() }}
@endsection
