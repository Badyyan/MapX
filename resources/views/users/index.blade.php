@extends('layouts.app')

@section('title', __('Team'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 table-wrap">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('Member') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Branch access') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="sr-only">{{ __('Action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 shrink-0 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 grid place-items-center text-sm font-semibold text-slate-500">
                                            {{ mb_substr($member->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $member->name }}</p>
                                            <p class="text-xs text-slate-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('users.update', $member) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 1 : 0 }}">
                                        <select name="role_id" onchange="this.form.submit()" class="input !w-auto !py-1.5 !px-2.5 !text-xs" @disabled($member->id === auth()->id()) aria-label="{{ __('Role') }}">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected($member->role_id === $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-xs text-slate-400">
                                    {{ empty($member->branch_ids) ? __('All branches') : count($member->branch_ids).' '.__('branches') }}
                                </td>
                                <td>
                                    <span class="{{ $member->is_active ? 'badge badge-success' : 'badge badge-neutral' }}">
                                        {{ $member->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($member->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm('{{ __('Remove this member?') }}')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm">{{ __('Remove') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6 h-fit">
            <h2 class="section-title mb-5 flex items-center gap-2"><x-icon name="user-plus" class="size-4 text-slate-400" /> {{ __('Invite a team member') }}</h2>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                @csrf
                <input name="name" required placeholder="{{ __('Full name') }}" class="input" aria-label="{{ __('Full name') }}">
                <input type="email" name="email" required placeholder="{{ __('Email') }}" class="input" aria-label="{{ __('Email') }}">
                <input type="password" name="password" required placeholder="{{ __('Temporary password') }}" class="input" aria-label="{{ __('Temporary password') }}">
                <select name="role_id" required class="input" aria-label="{{ __('Role') }}">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <div>
                    <p class="label !text-xs !text-slate-400">{{ __('Branch access (leave empty for all)') }}</p>
                    <div class="max-h-36 overflow-y-auto space-y-1.5">
                        @foreach($branches as $branch)
                            <label class="flex items-center gap-2.5 text-sm text-slate-700">
                                <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="checkbox">
                                {{ $branch->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-primary w-full"><x-icon name="user-plus" class="size-4" /> {{ __('Add member') }}</button>
            </form>
        </div>
    </div>
@endsection
