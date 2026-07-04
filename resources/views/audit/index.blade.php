@extends('layouts.app')

@section('title', __('Audit log'))

@section('content')
    <form method="GET" class="flex items-center gap-2" role="search">
        <div class="relative">
            <x-icon name="search" class="size-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input name="action" value="{{ request('action') }}" placeholder="{{ __('Filter by action…') }}" class="input !ps-9 w-64" aria-label="{{ __('Filter by action…') }}">
        </div>
        <button class="btn btn-secondary">{{ __('Filter') }}</button>
    </form>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-slate-400 whitespace-nowrap tabular-nums">{{ $log->created_at->format('M d, H:i:s') }}</td>
                            <td class="text-slate-700">{{ $log->user?->name ?? '—' }}</td>
                            <td><span class="kbd">{{ $log->action }}</span></td>
                            <td class="text-slate-400 text-xs">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                            <td class="text-slate-300 text-xs tabular-nums" dir="ltr">{{ $log->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="!py-10 text-center text-slate-400">{{ __('No audit entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $logs->links() }}
@endsection
