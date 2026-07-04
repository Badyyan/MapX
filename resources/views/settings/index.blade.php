@extends('layouts.app')

@section('title', __('Company settings'))

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="building" class="size-4 text-slate-400" /> {{ __('Company profile') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="label">{{ __('Company name') }} <span class="text-red-500">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $company->name) }}" required class="input">
                </div>
                <div>
                    <label for="legal_name" class="label">{{ __('Legal name') }}</label>
                    <input id="legal_name" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="input">
                </div>
                <div>
                    <label for="industry" class="label">{{ __('Industry') }}</label>
                    <input id="industry" name="industry" value="{{ old('industry', $company->industry) }}" class="input">
                </div>
                <div>
                    <label for="website" class="label">{{ __('Website') }}</label>
                    <input id="website" name="website" value="{{ old('website', $company->website) }}" class="input" dir="ltr">
                </div>
                <div>
                    <label for="phone" class="label">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone', $company->phone) }}" class="input" dir="ltr">
                </div>
                <div>
                    <label for="email" class="label">{{ __('Email') }}</label>
                    <input id="email" name="email" value="{{ old('email', $company->email) }}" class="input" dir="ltr">
                </div>
                <div>
                    <label for="default_locale" class="label">{{ __('Default language') }}</label>
                    <select id="default_locale" name="default_locale" class="input">
                        <option value="en" @selected($company->default_locale === 'en')>English</option>
                        <option value="ar" @selected($company->default_locale === 'ar')>العربية</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('Logo') }}</label>
                    <input type="file" name="logo" accept="image/*" class="text-sm text-slate-500">
                </div>
            </div>
        </div>

        <button class="btn btn-primary px-6"><x-icon name="check" class="size-4" /> {{ __('Save settings') }}</button>
    </form>
@endsection
