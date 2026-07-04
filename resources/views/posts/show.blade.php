@extends('layouts.app')

@section('title', $post->title ?: __('Post'))

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="{{ $post->status === 'published' ? 'badge badge-success' : ($post->status === 'failed' ? 'badge badge-danger' : 'badge badge-neutral') }}">
                    {{ __(ucfirst($post->status)) }}
                </span>
                <div class="flex gap-2">
                    @if(auth()->user()->hasPermission('posts.manage'))
                        @if(in_array($post->status, ['draft', 'failed', 'partial'], true))
                            <form method="POST" action="{{ route('posts.publish', $post) }}">
                                @csrf
                                <button class="btn btn-primary"><x-icon name="send" class="size-4" /> {{ __('Publish now') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('{{ __('Delete this post?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger"><x-icon name="trash" class="size-4" /> {{ __('Delete') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($post->title)<h2 class="mt-5 text-xl font-bold tracking-tight text-slate-900">{{ $post->title }}</h2>@endif
            <p class="mt-3 whitespace-pre-line text-slate-700 leading-relaxed" dir="auto">{{ $post->content }}</p>
            @if($post->image_path)
                <img src="{{ asset('storage/'.$post->image_path) }}" class="mt-5 rounded-xl max-h-80 object-cover ring-1 ring-slate-200" alt="">
            @endif
            <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-3.5" /> {{ __('Created') }} {{ $post->created_at->format('M d, Y H:i') }}</span>
                @if($post->scheduled_at)<span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-3.5" /> {{ __('Scheduled for') }} {{ $post->scheduled_at->format('M d, Y H:i') }}</span>@endif
                @if($post->cta_url)<span class="inline-flex items-center gap-1.5"><x-icon name="link" class="size-3.5" /> {{ $post->cta_url }}</span>@endif
            </div>
        </div>

        <div class="table-wrap">
            <div class="px-6 py-4 border-b border-slate-100 section-title">{{ __('Delivery status per platform') }}</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Platform') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Published') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($post->statuses as $status)
                            <tr>
                                <td>{{ $status->branch?->name }}</td>
                                <td>{{ config("mapx.platforms.{$status->platform}.name", $status->platform) }}</td>
                                <td>
                                    <span class="{{ $status->status === 'published' ? 'badge badge-success' : ($status->status === 'failed' ? 'badge badge-danger' : 'badge badge-neutral') }}">
                                        {{ __(ucfirst($status->status)) }}
                                    </span>
                                    @if($status->error)<p class="text-xs text-red-500 mt-1">{{ Str::limit($status->error, 80) }}</p>@endif
                                </td>
                                <td class="text-slate-400">{{ $status->published_at?->format('M d, H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="!py-8 text-center text-slate-400">{{ __('Not delivered yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
