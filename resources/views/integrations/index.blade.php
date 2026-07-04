@extends('layouts.app')

@section('title', __('Integrations'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 max-w-2xl">{{ __('Connect your locations to maps, social networks and ride services. Connected platforms are kept in sync automatically.') }}</p>
        @if(auth()->user()->hasPermission('integrations.manage'))
            <form method="POST" action="{{ route('integrations.sync-all') }}">
                @csrf
                <button class="btn btn-secondary"><x-icon name="refresh-cw" class="size-4" /> {{ __('Sync all') }}</button>
            </form>
        @endif
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 animate-stagger">
        @foreach($platforms as $key => $platform)
            @php($platformConnections = $connections->get($key, collect()))
            @php($syncedCount = $platformConnections->where('sync_status', 'synced')->count())
            <div class="card card-hover p-5 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <span class="grid place-items-center size-11 shrink-0 rounded-xl text-white font-bold text-lg shadow-sm" style="background: {{ $platform['color'] }}">
                            {{ strtoupper(substr($platform['name'], 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate">{{ $platform['name'] }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ implode(' · ', array_map(fn ($c) => __(ucfirst($c)), $platform['capabilities'])) }}</p>
                        </div>
                    </div>
                    @if($platformConnections->isNotEmpty())
                        <span class="badge badge-success shrink-0"><x-icon name="check" class="size-3" /> {{ __('Connected') }}</span>
                    @endif
                </div>

                <div class="mt-5 flex-1">
                    @if($platformConnections->isNotEmpty())
                        <p class="text-sm text-slate-600">{{ __(':synced of :total branches synchronized', ['synced' => $syncedCount, 'total' => $platformConnections->count()]) }}</p>
                        <div class="mt-2.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $platformConnections->count() ? $syncedCount / $platformConnections->count() * 100 : 0 }}%"></div>
                        </div>
                    @else
                        <p class="text-sm text-slate-400">{{ __('Not connected yet.') }}</p>
                    @endif
                </div>

                @if(auth()->user()->hasPermission('integrations.manage'))
                    <div class="mt-5 flex items-center gap-2">
                        @if($platformConnections->isEmpty())
                            <form method="POST" action="{{ route('integrations.connect') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="platform" value="{{ $key }}">
                                <button class="btn btn-primary btn-sm w-full !py-2"><x-icon name="plug" class="size-3.5" /> {{ __('Connect all branches') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('integrations.connect') }}">
                                @csrf
                                <input type="hidden" name="platform" value="{{ $key }}">
                                <button class="btn btn-secondary btn-sm"><x-icon name="refresh-cw" class="size-3" /> {{ __('Re-connect') }}</button>
                            </form>
                            <form method="POST" action="{{ route('integrations.disconnect', $platformConnections->first()) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">{{ __('Disconnect') }}</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Connection detail table --}}
    @if($connections->isNotEmpty())
        <div class="table-wrap">
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
