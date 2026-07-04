@extends('layouts.app')

@section('title', __('Audit log'))

@section('content')
    <form method="GET" class="flex items-center gap-2">
        <input name="action" value="{{ request('action') }}" placeholder="{{ __('Filter by action…') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm w-64">
        <button class="rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm">{{ __('Filter') }}</button>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-start px-4 py-3">{{ __('When') }}</th>
                        <th class="text-start px-4 py-3">{{ __('User') }}</th>
                        <th class="text-start px-4 py-3">{{ __('Action') }}</th>
                        <th class="text-start px-4 py-3">{{ __('Subject') }}</th>
                        <th class="text-start px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-2.5 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('M d, H:i:s') }}</td>
                            <td class="px-4 py-2.5">{{ $log->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $log->action }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $log->subject_type ? class_basename($log->subject_type).'#'.$log->subject_id : '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $log->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No audit entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $logs->links() }}
@endsection
