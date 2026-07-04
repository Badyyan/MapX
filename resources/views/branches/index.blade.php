@extends('layouts.app')

@section('title', __('Branches'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search branches…') }}"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-64 bg-white">
            <button class="rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm">{{ __('Search') }}</button>
        </form>
        @if(auth()->user()->hasPermission('branches.manage'))
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('branches.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <label class="rounded-lg bg-white border border-slate-300 px-3 py-2 text-sm cursor-pointer hover:bg-slate-50">
                        {{ __('Import CSV') }}
                        <input type="file" name="file" accept=".csv,.txt" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
                <a href="{{ route('branches.create') }}" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">+ {{ __('Add branch') }}</a>
            </div>
        @endif
    </div>

    <p class="text-xs text-slate-500">{{ __('CSV columns: name, description, address, city, region, country, lat, lng, phone, whatsapp, website, email, categories (separated by ;)') }}</p>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($branches as $branch)
            <a href="{{ route('branches.show', $branch) }}" class="bg-white rounded-2xl border border-slate-200 p-5 hover:border-brand-400 hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold">{{ $branch->name }}</div>
                        <div class="text-sm text-slate-500 mt-0.5">{{ $branch->address }}{{ $branch->city ? ', '.$branch->city : '' }}</div>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $branch->verification_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($branch->verification_status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                        {{ __(ucfirst($branch->verification_status)) }}
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-slate-500">⭐ {{ $branch->reviews_count }} {{ __('reviews') }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-20 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-brand-500" style="width: {{ $branch->completenessScore() }}%"></div>
                        </div>
                        <span class="text-xs text-slate-500">{{ $branch->completenessScore() }}%</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                {{ __('No branches yet. Add your first branch to start managing your online presence.') }}
            </div>
        @endforelse
    </div>

    {{ $branches->links() }}
@endsection
