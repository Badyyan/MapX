@extends('layouts.app')

@section('title', __('Company settings'))

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Company profile') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Company name') }} *</label>
                    <input name="name" value="{{ old('name', $company->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Legal name') }}</label>
                    <input name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Industry') }}</label>
                    <input name="industry" value="{{ old('industry', $company->industry) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Website') }}</label>
                    <input name="website" value="{{ old('website', $company->website) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Phone') }}</label>
                    <input name="phone" value="{{ old('phone', $company->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Email') }}</label>
                    <input name="email" value="{{ old('email', $company->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Default language') }}</label>
                    <select name="default_locale" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="en" @selected($company->default_locale === 'en')>English</option>
                        <option value="ar" @selected($company->default_locale === 'ar')>العربية</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Logo') }}</label>
                    <input type="file" name="logo" accept="image/*" class="text-sm">
                </div>
            </div>
        </div>

        <button class="rounded-lg bg-brand-600 text-white px-6 py-2.5 font-medium hover:bg-brand-700">{{ __('Save settings') }}</button>
    </form>
@endsection
