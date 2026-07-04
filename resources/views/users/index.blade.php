@extends('layouts.app')

@section('title', __('Team'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="text-start px-4 py-3">{{ __('Member') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Role') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Branch access') }}</th>
                            <th class="text-start px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $member)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $member->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $member->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('users.update', $member) }}" class="flex items-center gap-2">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 1 : 0 }}">
                                        <select name="role_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-2 py-1 text-xs" @disabled($member->id === auth()->id())>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected($member->role_id === $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ empty($member->branch_ids) ? __('All branches') : count($member->branch_ids).' '.__('branches') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $member->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $member->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @if($member->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm('{{ __('Remove this member?') }}')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700 text-xs">{{ __('Remove') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 h-fit">
            <h2 class="font-semibold mb-4">{{ __('Invite a team member') }}</h2>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                @csrf
                <input name="name" required placeholder="{{ __('Full name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="email" name="email" required placeholder="{{ __('Email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="password" name="password" required placeholder="{{ __('Temporary password') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <select name="role_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">{{ __('Branch access (leave empty for all)') }}</label>
                    <div class="max-h-32 overflow-y-auto space-y-1">
                        @foreach($branches as $branch)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="rounded border-slate-300">
                                {{ $branch->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="w-full rounded-lg bg-brand-600 text-white py-2 text-sm font-medium hover:bg-brand-700">{{ __('Add member') }}</button>
            </form>
        </div>
    </div>
@endsection
