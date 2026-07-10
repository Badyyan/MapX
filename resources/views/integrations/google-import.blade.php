@extends('layouts.app')

@section('title', __('Import from Google'))

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <span class="grid place-items-center size-12 shrink-0 rounded-2xl text-white font-bold text-xl shadow-sm" style="background: #4285F4">G</span>
                <div>
                    <h2 class="font-semibold text-slate-900">{{ __('Your Google Business locations') }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ $googleEmail }} · {{ __('Select the locations to bring into MapX. Name, address, hours, phone and categories import automatically.') }}
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('integrations.google.import.store') }}">
            @csrf
            <div class="card overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @forelse($locations as $location)
                        @php($alreadyImported = in_array($location['gbp_name'], $importedIds, true))
                        <label class="flex items-start gap-4 px-6 py-4 transition-colors {{ $alreadyImported ? 'opacity-50' : 'hover:bg-slate-50/60 cursor-pointer' }}">
                            <input type="checkbox" name="locations[]" value="{{ $location['gbp_name'] }}"
                                   class="checkbox mt-1" @disabled($alreadyImported) @checked(! $alreadyImported)>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-slate-900">{{ $location['title'] }}</span>
                                    @if($alreadyImported)
                                        <span class="badge badge-success"><x-icon name="check" class="size-3" /> {{ __('Imported') }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    {{ $location['address'] }}{{ $location['city'] ? ', '.$location['city'] : '' }}
                                </p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                    @if($location['phone'])<span class="inline-flex items-center gap-1"><x-icon name="phone" class="size-3" /> <span dir="ltr">{{ $location['phone'] }}</span></span>@endif
                                    @if($location['categories'])<span>{{ implode(' · ', array_slice($location['categories'], 0, 3)) }}</span>@endif
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="px-6 py-12 text-center text-slate-400">
                            <x-icon name="map-pin" class="size-10 text-slate-300 mx-auto mb-3" />
                            {{ __('No locations found on this Google account. Make sure you connected the account that manages your Business Profiles.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            @if(count($locations))
                <div class="mt-6 flex items-center gap-3">
                    <button class="btn btn-primary px-6"><x-icon name="download" class="size-4" /> {{ __('Import selected locations') }}</button>
                    <a href="{{ route('integrations.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            @endif
        </form>
    </div>
@endsection
