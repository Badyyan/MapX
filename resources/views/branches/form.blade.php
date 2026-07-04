@extends('layouts.app')

@section('title', $branch->exists ? __('Edit branch') : __('Add branch'))

@section('content')
    <form method="POST" enctype="multipart/form-data"
          action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}"
          class="max-w-3xl space-y-6">
        @csrf
        @if($branch->exists) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Basic information') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('Branch name') }} *</label>
                    <input name="name" value="{{ old('name', $branch->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('Description') }}</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $branch->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Categories (comma separated)') }}</label>
                    <input name="categories" value="{{ old('categories', implode(', ', $branch->categories ?? [])) }}" placeholder="Restaurant, Cafe" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Status') }}</label>
                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="active" @selected(old('status', $branch->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Location') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('Address') }}</label>
                    <input name="address" value="{{ old('address', $branch->address) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('City') }}</label>
                    <input name="city" value="{{ old('city', $branch->city) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Region') }}</label>
                    <input name="region" value="{{ old('region', $branch->region) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Latitude') }}</label>
                    <input name="lat" value="{{ old('lat', $branch->lat) }}" placeholder="24.7136" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Longitude') }}</label>
                    <input name="lng" value="{{ old('lng', $branch->lng) }}" placeholder="46.6753" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Google Place ID <span class="text-slate-400 text-xs">({{ __('used for the public review link') }})</span></label>
                    <input name="google_place_id" value="{{ old('google_place_id', $branch->google_place_id) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Contact') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Phone') }}</label>
                    <input name="phone" value="{{ old('phone', $branch->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">WhatsApp</label>
                    <input name="whatsapp_number" value="{{ old('whatsapp_number', $branch->whatsapp_number) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Website') }}</label>
                    <input name="website" value="{{ old('website', $branch->website) }}" placeholder="https://…" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Email') }}</label>
                    <input name="email" value="{{ old('email', $branch->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Opening hours') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                    <div class="flex items-center gap-2">
                        <span class="w-24 text-sm text-slate-600">{{ __(ucfirst($day)) }}</span>
                        <input name="hours[{{ $day }}][open]" value="{{ old("hours.$day.open", $branch->hours[$day]['open'] ?? '') }}" placeholder="09:00" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                        <span class="text-slate-400">—</span>
                        <input name="hours[{{ $day }}][close]" value="{{ old("hours.$day.close", $branch->hours[$day]['close'] ?? '') }}" placeholder="22:00" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Photos') }}</h2>
            <input type="file" name="images[]" multiple accept="image/*" class="text-sm">
            @if($branch->images)
                <div class="flex gap-2 flex-wrap">
                    @foreach($branch->images as $image)
                        <img src="{{ asset('storage/'.$image) }}" class="size-20 rounded-lg object-cover border border-slate-200" alt="">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-brand-600 text-white px-6 py-2.5 font-medium hover:bg-brand-700">
                {{ $branch->exists ? __('Save changes') : __('Create branch') }}
            </button>
            <a href="{{ route('branches.index') }}" class="text-sm text-slate-500 hover:underline">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
