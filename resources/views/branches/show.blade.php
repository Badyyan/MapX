@extends('layouts.app')

@section('title', $branch->name)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $branch->name }}</h2>
            <p class="text-slate-500">{{ $branch->address }}{{ $branch->city ? ', '.$branch->city : '' }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($branch->categories ?? [] as $category)
                    <span class="rounded-full bg-brand-50 text-brand-700 px-2.5 py-0.5 text-xs">{{ $category }}</span>
                @endforeach
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                    {{ $branch->verification_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ __(ucfirst($branch->verification_status)) }}
                </span>
            </div>
        </div>
        @if(auth()->user()->hasPermission('branches.manage'))
            <div class="flex gap-2">
                <a href="{{ route('branches.edit', $branch) }}" class="rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('{{ __('Delete this branch?') }}')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg bg-white border border-red-300 text-red-600 px-4 py-2 text-sm hover:bg-red-50">{{ __('Delete') }}</button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Data completeness --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">{{ __('Data completeness') }}</h3>
                    <span class="font-bold text-brand-600">{{ $branch->completenessScore() }}%</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-brand-500" style="width: {{ $branch->completenessScore() }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ __('Complete data improves your visibility in local search results.') }}</p>
            </div>

            {{-- Platform sync status --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="font-semibold mb-4">{{ __('Platform status') }}</h3>
                <div class="divide-y divide-slate-100">
                    @forelse($branch->connections as $connection)
                        <div class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="size-2.5 rounded-full {{ $connection->sync_status === 'synced' ? 'bg-emerald-500' : ($connection->sync_status === 'error' ? 'bg-red-500' : 'bg-amber-400') }}"></span>
                                <span class="text-sm font-medium">{{ $connection->platformName() }}</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ __(ucfirst(str_replace('_', ' ', $connection->sync_status))) }}
                                @if($connection->last_synced_at) · {{ $connection->last_synced_at->diffForHumans() }} @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-2">{{ __('No platforms connected yet.') }} <a class="text-brand-600 hover:underline" href="{{ route('integrations.index') }}">{{ __('Connect now') }}</a></p>
                    @endforelse
                </div>
            </div>

            {{-- Recent reviews --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="font-semibold mb-4">{{ __('Recent reviews') }}</h3>
                @forelse($recentReviews as $review)
                    <div class="py-3 border-b border-slate-100 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ $review->author_name }}</span>
                            <span class="text-amber-500 text-sm">{{ str_repeat('★', (int) $review->rating) }}</span>
                        </div>
                        <p class="text-sm text-slate-600 mt-1">{{ $review->content }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No reviews for this branch yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            {{-- Contact card --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm space-y-2">
                <h3 class="font-semibold mb-2">{{ __('Contact') }}</h3>
                @if($branch->phone)<div>📞 {{ $branch->phone }}</div>@endif
                @if($branch->whatsapp_number)<div>💬 <a class="text-brand-600" href="https://wa.me/{{ preg_replace('/\D/', '', $branch->whatsapp_number) }}">WhatsApp</a></div>@endif
                @if($branch->website)<div>🌐 <a class="text-brand-600 break-all" href="{{ $branch->website }}" target="_blank" rel="noopener">{{ $branch->website }}</a></div>@endif
                @if($branch->email)<div>✉️ {{ $branch->email }}</div>@endif
                @if($branch->lat)<div class="text-slate-500">📍 {{ $branch->lat }}, {{ $branch->lng }}</div>@endif
            </div>

            {{-- Deep links --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm">
                <h3 class="font-semibold mb-3">{{ __('Ride & map deep links') }}</h3>
                @if($branch->lat)
                    <div class="space-y-2">
                        <a class="block rounded-lg bg-slate-50 px-3 py-2 hover:bg-slate-100" target="_blank" rel="noopener" href="{{ $branch->googleMapsUrl() }}">🗺️ Google Maps</a>
                        <a class="block rounded-lg bg-slate-50 px-3 py-2 hover:bg-slate-100" target="_blank" rel="noopener" href="{{ $branch->wazeDeepLink() }}">🚗 Waze</a>
                        <a class="block rounded-lg bg-slate-50 px-3 py-2 hover:bg-slate-100" target="_blank" rel="noopener" href="{{ $branch->uberDeepLink() }}">🚕 Uber</a>
                        <a class="block rounded-lg bg-slate-50 px-3 py-2 hover:bg-slate-100" href="{{ $branch->careemDeepLink() }}">🟢 Careem</a>
                        <a class="block rounded-lg bg-slate-50 px-3 py-2 hover:bg-slate-100" target="_blank" rel="noopener" href="{{ $branch->boltDeepLink() }}">⚡ Bolt</a>
                    </div>
                @else
                    <p class="text-slate-500">{{ __('Add coordinates to generate deep links.') }}</p>
                @endif
            </div>

            {{-- Hours --}}
            @if($branch->hours)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm">
                    <h3 class="font-semibold mb-3">{{ __('Opening hours') }}</h3>
                    @foreach($branch->hours as $day => $hours)
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">{{ __(ucfirst($day)) }}</span>
                            <span>{{ $hours['open'] ?? '' }} – {{ $hours['close'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
