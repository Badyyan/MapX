@extends('layouts.app')

@section('title', __('Branches'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2" role="search">
            <div class="relative">
                <x-icon name="search" class="size-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search branches…') }}" class="input !ps-9 w-64" aria-label="{{ __('Search branches…') }}">
            </div>
            <button class="btn btn-secondary">{{ __('Search') }}</button>
        </form>
        @if(auth()->user()->hasPermission('branches.manage'))
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('branches.import') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="btn btn-secondary cursor-pointer">
                        <x-icon name="upload" class="size-4" />
                        {{ __('Import CSV') }}
                        <input type="file" name="file" accept=".csv,.txt" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
                <a href="{{ route('branches.create') }}" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" />
                    {{ __('Add branch') }}
                </a>
            </div>
        @endif
    </div>

    <p class="text-xs text-slate-400">{{ __('CSV columns: name, description, address, city, region, country, lat, lng, phone, whatsapp, website, email, categories (separated by ;)') }}</p>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 animate-stagger">
        @forelse($branches as $branch)
            <a href="{{ route('branches.show', $branch) }}" class="card card-hover p-5 block">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="mt-0.5 grid place-items-center size-9 shrink-0 rounded-xl bg-brand-50 text-brand-600">
                            <x-icon name="store" class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate">{{ $branch->name }}</p>
                            <p class="text-sm text-slate-500 mt-0.5 truncate">{{ $branch->address }}{{ $branch->city ? ', '.$branch->city : '' }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 {{ $branch->verification_status === 'verified' ? 'badge badge-success' : ($branch->verification_status === 'pending' ? 'badge badge-warning' : 'badge badge-neutral') }}">
                        {{ __(ucfirst($branch->verification_status)) }}
                    </span>
                </div>
                <div class="mt-5 flex items-center justify-between text-sm">
                    <span class="inline-flex items-center gap-1.5 text-slate-500">
                        <x-icon name="star" class="size-3.5 text-amber-400" />
                        {{ $branch->reviews_count }} {{ __('reviews') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <div class="w-20 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $branch->completenessScore() }}%"></div>
                        </div>
                        <span class="text-xs text-slate-400 tabular-nums">{{ $branch->completenessScore() }}%</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="md:col-span-2 xl:col-span-3 card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                <x-icon name="map-pin" class="size-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">{{ __('No branches yet. Add your first branch to start managing your online presence.') }}</p>
            </div>
        @endforelse
    </div>

    {{ $branches->links() }}
@endsection
