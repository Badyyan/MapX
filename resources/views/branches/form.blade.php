@extends('layouts.app')

@section('title', $branch->exists ? __('Edit branch') : __('Add branch'))

@section('content')
    <form method="POST" enctype="multipart/form-data"
          action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}"
          class="max-w-3xl space-y-6">
        @csrf
        @if($branch->exists) @method('PUT') @endif

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="store" class="size-4 text-slate-400" /> {{ __('Basic information') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="name" class="label">{{ __('Branch name') }} <span class="text-red-500">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $branch->name) }}" required class="input">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="label">{{ __('Description') }}</label>
                    <textarea id="description" name="description" rows="3" class="input">{{ old('description', $branch->description) }}</textarea>
                </div>
                <div>
                    <label for="categories" class="label">{{ __('Categories (comma separated)') }}</label>
                    <input id="categories" name="categories" value="{{ old('categories', implode(', ', $branch->categories ?? [])) }}" placeholder="Restaurant, Cafe" class="input">
                </div>
                <div>
                    <label for="status" class="label">{{ __('Status') }}</label>
                    <select id="status" name="status" class="input">
                        <option value="active" @selected(old('status', $branch->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="map-pin" class="size-4 text-slate-400" /> {{ __('Location') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="address" class="label">{{ __('Address') }}</label>
                    <input id="address" name="address" value="{{ old('address', $branch->address) }}" class="input">
                </div>
                <div>
                    <label for="city" class="label">{{ __('City') }}</label>
                    <input id="city" name="city" value="{{ old('city', $branch->city) }}" class="input">
                </div>
                <div>
                    <label for="region" class="label">{{ __('Region') }}</label>
                    <input id="region" name="region" value="{{ old('region', $branch->region) }}" class="input">
                </div>
                <div>
                    <label for="lat" class="label">{{ __('Latitude') }}</label>
                    <input id="lat" name="lat" value="{{ old('lat', $branch->lat) }}" placeholder="24.7136" class="input" dir="ltr">
                </div>
                <div>
                    <label for="lng" class="label">{{ __('Longitude') }}</label>
                    <input id="lng" name="lng" value="{{ old('lng', $branch->lng) }}" placeholder="46.6753" class="input" dir="ltr">
                </div>
                <div class="sm:col-span-2">
                    <label for="google_place_id" class="label">Google Place ID <span class="text-slate-400 font-normal">({{ __('used for the public review link') }})</span></label>
                    <input id="google_place_id" name="google_place_id" value="{{ old('google_place_id', $branch->google_place_id) }}" class="input" dir="ltr">
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="phone" class="size-4 text-slate-400" /> {{ __('Contact') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="phone" class="label">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="input" dir="ltr">
                </div>
                <div>
                    <label for="whatsapp_number" class="label">WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $branch->whatsapp_number) }}" class="input" dir="ltr">
                </div>
                <div>
                    <label for="website" class="label">{{ __('Website') }}</label>
                    <input id="website" name="website" value="{{ old('website', $branch->website) }}" placeholder="https://…" class="input" dir="ltr">
                </div>
                <div>
                    <label for="email" class="label">{{ __('Email') }}</label>
                    <input id="email" name="email" value="{{ old('email', $branch->email) }}" class="input" dir="ltr">
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="clock" class="size-4 text-slate-400" /> {{ __('Opening hours') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                    <div class="flex items-center gap-2.5">
                        <span class="w-24 shrink-0 text-sm text-slate-500">{{ __(ucfirst($day)) }}</span>
                        <input name="hours[{{ $day }}][open]" value="{{ old("hours.$day.open", $branch->hours[$day]['open'] ?? '') }}" placeholder="09:00" class="input !w-24 !px-2.5 !py-2 text-center tabular-nums" dir="ltr" aria-label="{{ __(ucfirst($day)) }} {{ __('open') }}">
                        <span class="text-slate-300">–</span>
                        <input name="hours[{{ $day }}][close]" value="{{ old("hours.$day.close", $branch->hours[$day]['close'] ?? '') }}" placeholder="22:00" class="input !w-24 !px-2.5 !py-2 text-center tabular-nums" dir="ltr" aria-label="{{ __(ucfirst($day)) }} {{ __('close') }}">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card p-6 space-y-4">
            <h2 class="section-title flex items-center gap-2"><x-icon name="image" class="size-4 text-slate-400" /> {{ __('Photos') }}</h2>
            <input type="file" name="images[]" multiple accept="image/*" class="text-sm text-slate-500 file:me-4 file:btn file:btn-secondary file:btn-sm">
            @if($branch->images)
                <div class="flex gap-2.5 flex-wrap">
                    @foreach($branch->images as $image)
                        <img src="{{ asset('storage/'.$image) }}" class="size-20 rounded-xl object-cover ring-1 ring-slate-200" alt="">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button class="btn btn-primary px-6">
                <x-icon name="check" class="size-4" />
                {{ $branch->exists ? __('Save changes') : __('Create branch') }}
            </button>
            <a href="{{ route('branches.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
