@extends('layouts.app')

@section('title', __('Integrations'))

@section('content')
    @if($mockMode)
        <div class="card flex items-center gap-3 px-4 py-3 !ring-amber-600/20 bg-amber-50/60 text-sm text-amber-800" role="status">
            <x-icon name="info" class="size-4.5 shrink-0 text-amber-600" />
            {{ __('Demo mode is on (MAPX_INTEGRATIONS_MOCK=true): connections are simulated so you can explore the product. Set it to false and configure API credentials to connect real accounts.') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 max-w-2xl">{{ __('Connect your locations to maps, social networks and ride services. Connected platforms are kept in sync automatically.') }}</p>
        @if(auth()->user()->hasPermission('integrations.manage'))
            <form method="POST" action="{{ route('integrations.sync-all') }}">
                @csrf
                <button class="btn btn-secondary"><x-icon name="refresh-cw" class="size-4" /> {{ __('Sync all') }}</button>
            </form>
        @endif
    </div>

    {{-- Google Business Profile — the primary integration --}}
    <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <span class="grid place-items-center size-12 shrink-0 rounded-2xl text-white font-bold text-xl shadow-sm" style="background: #4285F4">G</span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-slate-900">Google Business Profile</p>
                        @if($googleConnection)
                            <span class="badge badge-success"><x-icon name="check" class="size-3" /> {{ __('Connected') }}</span>
                        @elseif(! $googleConfigured && ! $mockMode)
                            <span class="badge badge-warning">{{ __('Setup required') }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">
                        @if($googleConnection)
                            {{ $googleConnection->meta['google_email'] ?? '' }}
                            · {{ __('Import your existing locations, sync data, reply to reviews and publish posts.') }}
                        @else
                            {{ __('Sign in with Google to pull in every location you already manage — no manual entry.') }}
                        @endif
                    </p>
                </div>
            </div>

            @if(auth()->user()->hasPermission('integrations.manage'))
                <div class="flex flex-wrap items-center gap-2">
                    @if($googleConnection)
                        <a href="{{ route('integrations.google.import') }}" class="btn btn-primary">
                            <x-icon name="download" class="size-4" /> {{ __('Import locations') }}
                        </a>
                        <form method="POST" action="{{ route('integrations.google.disconnect') }}" onsubmit="return confirm('{{ __('Disconnect Google? Imported branches stay, but syncing stops.') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger">{{ __('Disconnect') }}</button>
                        </form>
                    @elseif($googleConfigured)
                        <a href="{{ route('integrations.google.redirect') }}" class="btn btn-primary">
                            <x-icon name="plug" class="size-4" /> {{ __('Connect with Google') }}
                        </a>
                    @elseif($mockMode)
                        <form method="POST" action="{{ route('integrations.connect') }}">
                            @csrf
                            <input type="hidden" name="platform" value="google">
                            <button class="btn btn-secondary"><x-icon name="plug" class="size-4" /> {{ __('Connect (demo)') }}</button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400 max-w-52">{{ __('Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET — see INTEGRATIONS.md') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Meta: Facebook Pages + Instagram --}}
    <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <span class="grid place-items-center size-12 shrink-0 rounded-2xl text-white font-bold text-xl shadow-sm" style="background: #1877F2">f</span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-slate-900">Facebook Pages & Instagram</p>
                        @if($metaConnection)
                            <span class="badge badge-success"><x-icon name="check" class="size-3" /> {{ __('Connected') }}</span>
                        @elseif(! $metaConfigured && ! $mockMode)
                            <span class="badge badge-warning">{{ __('Setup required') }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Publish posts to your pages and Instagram business accounts.') }}</p>
                </div>
            </div>

            @if(auth()->user()->hasPermission('integrations.manage'))
                <div class="flex flex-wrap items-center gap-2">
                    @if($metaConnection)
                        <a href="{{ route('integrations.meta.pages') }}" class="btn btn-primary">
                            <x-icon name="link" class="size-4" /> {{ __('Map pages to branches') }}
                        </a>
                    @elseif($metaConfigured)
                        <a href="{{ route('integrations.meta.redirect') }}" class="btn btn-primary">
                            <x-icon name="plug" class="size-4" /> {{ __('Connect with Facebook') }}
                        </a>
                    @elseif($mockMode)
                        <form method="POST" action="{{ route('integrations.connect') }}">
                            @csrf
                            <input type="hidden" name="platform" value="facebook">
                            <button class="btn btn-secondary"><x-icon name="plug" class="size-4" /> {{ __('Connect (demo)') }}</button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400 max-w-52">{{ __('Add META_APP_ID and META_APP_SECRET — see INTEGRATIONS.md') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Automatic deep-link platforms --}}
    <div class="card p-6">
        <div class="flex items-center gap-2 mb-1.5">
            <h2 class="section-title">{{ __('Automatic platforms') }}</h2>
            <span class="badge badge-info">{{ __('No connection needed') }}</span>
        </div>
        <p class="text-sm text-slate-500 mb-5">{{ __('These services have no listing API — MapX generates ready-to-use deep links for every branch from its coordinates, automatically.') }}</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach(['waze' => 'Waze', 'snap' => 'Snap Map', 'uber' => 'Uber', 'careem' => 'Careem', 'bolt' => 'Bolt'] as $key => $name)
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <span class="grid place-items-center size-9 shrink-0 rounded-lg text-white font-bold shadow-xs" style="background: {{ config("mapx.platforms.$key.color") }}">{{ strtoupper(substr($name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $name }}</p>
                        <p class="text-xs text-emerald-600 flex items-center gap-1"><x-icon name="check" class="size-3" /> {{ __('Active') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Per-branch connection table --}}
    @if($connections->isNotEmpty())
        <div class="table-wrap">
            <div class="px-6 py-4 border-b border-slate-100 section-title">{{ __('Connected branches') }}</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('Platform') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Sync status') }}</th>
                            <th>{{ __('Completeness') }}</th>
                            <th>{{ __('Last sync') }}</th>
                            <th><span class="sr-only">{{ __('Action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connections->flatten() as $connection)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $connection->platformName() }}</td>
                                <td>{{ $connection->branch?->name ?? __('All') }}</td>
                                <td>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="size-2 rounded-full {{ $connection->sync_status === 'synced' ? 'bg-emerald-500' : ($connection->sync_status === 'error' ? 'bg-red-500' : 'bg-amber-400') }}"></span>
                                        {{ __(ucfirst(str_replace('_', ' ', $connection->sync_status))) }}
                                    </span>
                                </td>
                                <td class="tabular-nums">{{ $connection->data_completeness }}%</td>
                                <td class="text-slate-400">{{ $connection->last_synced_at?->diffForHumans() ?? '—' }}</td>
                                <td class="text-end">
                                    @if(auth()->user()->hasPermission('integrations.manage'))
                                        <form method="POST" action="{{ route('integrations.sync', $connection) }}" class="inline">
                                            @csrf
                                            <button class="btn btn-ghost btn-sm"><x-icon name="refresh-cw" class="size-3" /> {{ __('Sync') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
