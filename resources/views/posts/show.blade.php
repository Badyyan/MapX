@extends('layouts.app')

@section('title', $post->title ?: __('Post'))

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <span class="rounded-full px-3 py-1 text-xs font-medium
                    {{ $post->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ __(ucfirst($post->status)) }}
                </span>
                <div class="flex gap-2">
                    @if(auth()->user()->hasPermission('posts.manage'))
                        @if(in_array($post->status, ['draft', 'failed', 'partial'], true))
                            <form method="POST" action="{{ route('posts.publish', $post) }}">
                                @csrf
                                <button class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">{{ __('Publish now') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('{{ __('Delete this post?') }}')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg bg-white border border-red-300 text-red-600 px-4 py-2 text-sm hover:bg-red-50">{{ __('Delete') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($post->title)<h2 class="mt-4 text-xl font-bold">{{ $post->title }}</h2>@endif
            <p class="mt-3 whitespace-pre-line text-slate-700" dir="auto">{{ $post->content }}</p>
            @if($post->image_path)
                <img src="{{ asset('storage/'.$post->image_path) }}" class="mt-4 rounded-xl max-h-80 object-cover" alt="">
            @endif
            <div class="mt-4 text-xs text-slate-500">
                {{ __('Created') }} {{ $post->created_at->format('M d, Y H:i') }}
                @if($post->scheduled_at) · {{ __('Scheduled for') }} {{ $post->scheduled_at->format('M d, Y H:i') }} @endif
                @if($post->cta_url) · CTA: {{ $post->cta_url }} @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-4">{{ __('Delivery status per platform') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="text-start px-4 py-2">{{ __('Branch') }}</th>
                            <th class="text-start px-4 py-2">{{ __('Platform') }}</th>
                            <th class="text-start px-4 py-2">{{ __('Status') }}</th>
                            <th class="text-start px-4 py-2">{{ __('Published') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($post->statuses as $status)
                            <tr>
                                <td class="px-4 py-2">{{ $status->branch?->name }}</td>
                                <td class="px-4 py-2">{{ config("mapx.platforms.{$status->platform}.name", $status->platform) }}</td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $status->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($status->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ __(ucfirst($status->status)) }}
                                    </span>
                                    @if($status->error)<div class="text-xs text-red-500 mt-1">{{ Str::limit($status->error, 80) }}</div>@endif
                                </td>
                                <td class="px-4 py-2 text-slate-500">{{ $status->published_at?->format('M d, H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">{{ __('Not delivered yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
