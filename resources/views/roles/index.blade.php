@extends('layouts.app')

@section('title', __('Roles & permissions'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4 animate-stagger">
            @foreach($roles as $role)
                <div class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid place-items-center size-9 rounded-xl {{ $role->is_system ? 'bg-slate-100 text-slate-500' : 'bg-brand-50 text-brand-600' }}">
                                <x-icon name="shield" class="size-4.5" />
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ $role->name }}</span>
                                @if($role->is_system)<span class="badge badge-neutral">{{ __('System') }}</span>@endif
                                <span class="text-xs text-slate-400">{{ $role->users_count }} {{ __('members') }}</span>
                            </div>
                        </div>
                        @unless($role->is_system)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><x-icon name="trash" class="size-3" /> {{ __('Delete') }}</button>
                            </form>
                        @endunless
                    </div>
                    <div class="mt-3.5 flex flex-wrap gap-1.5">
                        @if(($role->permissions ?? []) === ['*'])
                            <span class="badge badge-brand"><x-icon name="key" class="size-3" /> {{ __('All permissions') }}</span>
                        @else
                            @foreach($role->permissions ?? [] as $permission)
                                <span class="badge badge-neutral font-mono !font-normal">{{ $permission }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-6 h-fit">
            <h2 class="section-title mb-5 flex items-center gap-2"><x-icon name="shield" class="size-4 text-slate-400" /> {{ __('Create custom role') }}</h2>
            <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
                @csrf
                <input name="name" required placeholder="{{ __('Role name, e.g. Marketing') }}" class="input" aria-label="{{ __('Rule name') }}">
                <div class="space-y-2 max-h-80 overflow-y-auto pe-1">
                    @foreach($permissionCatalog as $permission)
                        <label class="flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm hover:bg-slate-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="checkbox">
                            <span class="font-mono text-xs text-slate-600">{{ $permission }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> {{ __('Create role') }}</button>
            </form>
        </div>
    </div>
@endsection
