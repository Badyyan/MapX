@extends('layouts.app')

@section('title', $branch->name)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            <span class="grid place-items-center size-12 rounded-2xl bg-brand-50 text-brand-600">
                <x-icon name="store" class="size-6" />
            </span>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $branch->name }}</h2>
                <p class="text-slate-500">{{ $branch->address }}{{ $branch->city ? ', '.$branch->city : '' }}</p>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    @foreach($branch->categories ?? [] as $category)
                        <span class="badge badge-brand">{{ $category }}</span>
                    @endforeach
                    <span class="{{ $branch->verification_status === 'verified' ? 'badge badge-success' : 'badge badge-warning' }}">
                        <x-icon name="badge-check" class="size-3" />
                        {{ __(ucfirst($branch->verification_status)) }}
                    </span>
                </div>
            </div>
        </div>
        @if(auth()->user()->hasPermission('branches.manage'))
            <div class="flex gap-2">
                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-secondary">
                    <x-icon name="pencil" class="size-4" /> {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('{{ __('Delete this branch?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger"><x-icon name="trash" class="size-4" /> {{ __('Delete') }}</button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Data completeness --}}
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="section-title">{{ __('Data completeness') }}</h3>
                    <span class="font-bold text-brand-600 tabular-nums">{{ $branch->completenessScore() }}%</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400 transition-all duration-500" style="width: {{ $branch->completenessScore() }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-400">{{ __('Complete data improves your visibility in local search results.') }}</p>
            </div>

            {{-- Platform sync status --}}
            <div class="card p-6">
                <h3 class="section-title mb-4">{{ __('Platform status') }}</h3>
                <div class="divide-y divide-slate-100">
                    @forelse($branch->connections as $connection)
                        <div class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="relative flex size-2.5">
                                    <span class="absolute inline-flex size-full rounded-full {{ $connection->sync_status === 'synced' ? 'bg-emerald-400 animate-ping opacity-30' : '' }}"></span>
                                    <span class="relative inline-flex size-2.5 rounded-full {{ $connection->sync_status === 'synced' ? 'bg-emerald-500' : ($connection->sync_status === 'error' ? 'bg-red-500' : 'bg-amber-400') }}"></span>
                                </span>
                                <span class="text-sm font-medium text-slate-800">{{ $connection->platformName() }}</span>
                            </div>
                            <span class="text-xs text-slate-400">
                                {{ __(ucfirst(str_replace('_', ' ', $connection->sync_status))) }}
                                @if($connection->last_synced_at) · {{ $connection->last_synced_at->diffForHumans() }} @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-2">
                            {{ __('No platforms connected yet.') }}
                            <a class="text-brand-600 font-medium hover:underline" href="{{ route('integrations.index') }}">{{ __('Connect now') }}</a>
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Recent reviews --}}
            <div class="card p-6">
                <h3 class="section-title mb-4">{{ __('Recent reviews') }}</h3>
                @forelse($recentReviews as $review)
                    <div class="py-3.5 border-b border-slate-100 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900">{{ $review->author_name }}</span>
                            <x-rating :value="$review->rating" class="size-3.5" />
                        </div>
                        <p class="text-sm text-slate-600 mt-1.5" dir="auto">{{ $review->content }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No reviews for this branch yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            {{-- Contact card --}}
            <div class="card p-6 text-sm space-y-3">
                <h3 class="section-title mb-1">{{ __('Contact') }}</h3>
                @if($branch->phone)
                    <div class="flex items-center gap-2.5 text-slate-600"><x-icon name="phone" class="size-4 text-slate-400" /> <span dir="ltr">{{ $branch->phone }}</span></div>
                @endif
                @if($branch->whatsapp_number)
                    <div class="flex items-center gap-2.5"><x-icon name="message-circle" class="size-4 text-slate-400" /> <a class="text-brand-600 hover:underline" href="https://wa.me/{{ preg_replace('/\D/', '', $branch->whatsapp_number) }}">WhatsApp</a></div>
                @endif
                @if($branch->website)
                    <div class="flex items-center gap-2.5"><x-icon name="globe" class="size-4 text-slate-400" /> <a class="text-brand-600 hover:underline break-all" href="{{ $branch->website }}" target="_blank" rel="noopener">{{ $branch->website }}</a></div>
                @endif
                @if($branch->email)
                    <div class="flex items-center gap-2.5 text-slate-600"><x-icon name="mail" class="size-4 text-slate-400" /> {{ $branch->email }}</div>
                @endif
                @if($branch->lat)
                    <div class="flex items-center gap-2.5 text-slate-500"><x-icon name="map-pin" class="size-4 text-slate-400" /> <span dir="ltr">{{ $branch->lat }}, {{ $branch->lng }}</span></div>
                @endif
            </div>

            {{-- Deep links --}}
            <div class="card p-6 text-sm">
                <h3 class="section-title mb-4">{{ __('Ride & map deep links') }}</h3>
                @if($branch->lat)
                    <div class="space-y-1.5">
                        @foreach([
                            ['map', 'Google Maps', $branch->googleMapsUrl()],
                            ['navigation', 'Waze', $branch->wazeDeepLink()],
                            ['car', 'Uber', $branch->uberDeepLink()],
                            ['car', 'Careem', $branch->careemDeepLink()],
                            ['zap', 'Bolt', $branch->boltDeepLink()],
                        ] as [$icon, $label, $url])
                            <a class="flex items-center justify-between rounded-lg px-3 py-2.5 bg-slate-50 hover:bg-slate-100 transition-colors group" target="_blank" rel="noopener" href="{{ $url }}">
                                <span class="flex items-center gap-2.5 text-slate-700"><x-icon :name="$icon" class="size-4 text-slate-400" /> {{ $label }}</span>
                                <x-icon name="external-link" class="size-3.5 text-slate-300 group-hover:text-slate-500 transition-colors" />
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-400">{{ __('Add coordinates to generate deep links.') }}</p>
                @endif
            </div>

            {{-- Hours --}}
            @if($branch->hours)
                <div class="card p-6 text-sm">
                    <h3 class="section-title mb-4">{{ __('Opening hours') }}</h3>
                    @foreach($branch->hours as $day => $hours)
                        <div class="flex justify-between py-1.5 border-b border-slate-50 last:border-0">
                            <span class="text-slate-500">{{ __(ucfirst($day)) }}</span>
                            <span class="text-slate-800 tabular-nums" dir="ltr">{{ $hours['open'] ?? '' }} – {{ $hours['close'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
