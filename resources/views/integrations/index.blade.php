@extends('layouts.app')

@section('title', __('Integrations'))

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500 max-w-2xl">{{ __('Connect your locations to maps, social networks and ride services. Connected platforms are kept in sync automatically.') }}</p>
        @if(auth()->user()->hasPermission('integrations.manage'))
            <form method="POST" action="{{ route('integrations.sync-all') }}">
                @csrf
                <button class="rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">🔄 {{ __('Sync all') }}</button>
            </form>
        @endif
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($platforms as $key => $platform)
            @php($platformConnections = $connections->get($key, collect()))
            @php($syncedCount = $platformConnections->where('sync_status', 'synced')->count())
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="size-10 rounded-xl grid place-items-center text-white font-bold" style="background: {{ $platform['color'] }}">
                            {{ strtoupper(substr($platform['name'], 0, 1)) }}
                        </span>
                        <div>
                            <div class="font-semibold">{{ $platform['name'] }}</div>
                            <div class="text-xs text-slate-500">{{ implode(' · ', array_map(fn ($c) => __(ucfirst($c)), $platform['capabilities'])) }}</div>
                        </div>
                    </div>
                    @if($platformConnections->isNotEmpty())
                        <span class="rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-0.5 text-xs font-medium">{{ __('Connected') }}</span>
                    @endif
                </div>

                <div class="mt-4 flex-1">
                    @if($platformConnections->isNotEmpty())
                        <div class="text-sm text-slate-600">{{ __(':synced of :total branches synchronized', ['synced' => $syncedCount, 'total' => $platformConnections->count()]) }}</div>
                        <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-emerald-500" style="width: {{ $platformConnections->count() ? $syncedCount / $platformConnections->count() * 100 : 0 }}%"></div>
                        </div>
                    @else
                        <p class="text-sm text-slate-400">{{ __('Not connected yet.') }}</p>
                    @endif
                </div>

                @if(auth()->user()->hasPermission('integrations.manage'))
                    <div class="mt-4 flex items-center gap-2">
                        @if($platformConnections->isEmpty())
                            <form method="POST" action="{{ route('integrations.connect') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="platform" value="{{ $key }}">
                                <button class="w-full rounded-lg bg-brand-600 text-white px-3 py-2 text-sm font-medium hover:bg-brand-700">{{ __('Connect all branches') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('integrations.connect') }}">
                                @csrf
                                <input type="hidden" name="platform" value="{{ $key }}">
                                <button class="rounded-lg bg-white border border-slate-300 px-3 py-2 text-xs hover:bg-slate-50">{{ __('Re-connect') }}</button>
                            </form>
                            <form method="POST" action="{{ route('integrations.disconnect', $platformConnections->first()) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-white border border-red-200 text-red-600 px-3 py-2 text-xs hover:bg-red-50">{{ __('Disconnect') }}</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Connection detail table --}}
    @if($connections->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="text-start px-4 py-3">{{ __('Platform') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Branch') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Sync status') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Completeness') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Last sync') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($connections->flatten() as $connection)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $connection->platformName() }}</td>
                                <td class="px-4 py-3">{{ $connection->branch?->name ?? __('All') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="size-2 rounded-full {{ $connection->sync_status === 'synced' ? 'bg-emerald-500' : ($connection->sync_status === 'error' ? 'bg-red-500' : 'bg-amber-400') }}"></span>
                                        {{ __(ucfirst(str_replace('_', ' ', $connection->sync_status))) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $connection->data_completeness }}%</td>
                                <td class="px-4 py-3 text-slate-500">{{ $connection->last_synced_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    @if(auth()->user()->hasPermission('integrations.manage'))
                                        <form method="POST" action="{{ route('integrations.sync', $connection) }}" class="inline">
                                            @csrf
                                            <button class="text-brand-600 hover:underline text-xs">{{ __('Sync') }}</button>
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
