@extends('layouts.app')

@section('title', __('Roles & permissions'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @foreach($roles as $role)
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ $role->name }}</span>
                            @if($role->is_system)<span class="rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-xs">{{ __('System') }}</span>@endif
                            <span class="text-xs text-slate-400">{{ $role->users_count }} {{ __('members') }}</span>
                        </div>
                        @unless($role->is_system)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs">{{ __('Delete') }}</button>
                            </form>
                        @endunless
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @if(($role->permissions ?? []) === ['*'])
                            <span class="rounded-full bg-brand-100 text-brand-700 px-2.5 py-0.5 text-xs font-medium">{{ __('All permissions') }}</span>
                        @else
                            @foreach($role->permissions ?? [] as $permission)
                                <span class="rounded-full bg-slate-100 text-slate-600 px-2.5 py-0.5 text-xs">{{ $permission }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 h-fit">
            <h2 class="font-semibold mb-4">{{ __('Create custom role') }}</h2>
            <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
                @csrf
                <input name="name" required placeholder="{{ __('Role name, e.g. Marketing') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <div class="space-y-1.5 max-h-72 overflow-y-auto">
                    @foreach($permissionCatalog as $permission)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-slate-300">
                            <span class="font-mono text-xs">{{ $permission }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="w-full rounded-lg bg-brand-600 text-white py-2 text-sm font-medium hover:bg-brand-700">{{ __('Create role') }}</button>
            </form>
        </div>
    </div>
@endsection
