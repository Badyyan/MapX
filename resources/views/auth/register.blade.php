@extends('layouts.guest')

@section('title', __('Create account'))

@section('content')
    <h1 class="text-xl font-semibold text-slate-900">{{ __('Start your 7-day free trial') }}</h1>
    <p class="mt-1 text-sm text-slate-500 mb-7">{{ __('1 company, up to 3 branches. No credit card required.') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="company_name" class="label">{{ __('Company name') }}</label>
            <input id="company_name" name="company_name" value="{{ old('company_name') }}" required class="input">
            @error('company_name')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="industry" class="label">{{ __('Industry') }}</label>
            <select id="industry" name="industry" class="input">
                @foreach(['Restaurants & Cafes', 'Retail', 'Clinics & Hospitals', 'Gyms & Fitness', 'Automotive', 'Beauty salons', 'Pharmacies', 'Services', 'Other'] as $industry)
                    <option value="{{ $industry }}" @selected(old('industry') === $industry)>{{ __($industry) }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="name" class="label">{{ __('Your name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input">
                @error('name')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="label">{{ __('Phone') }}</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="+9665…" autocomplete="tel" class="input">
            </div>
        </div>
        <div>
            <label for="reg-email" class="label">{{ __('Email') }}</label>
            <input id="reg-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="input">
            @error('email')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="reg-password" class="label">{{ __('Password') }}</label>
                <input id="reg-password" type="password" name="password" required autocomplete="new-password" class="input">
                @error('password')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input">
            </div>
        </div>
        <div>
            <label for="locale" class="label">{{ __('Preferred language') }}</label>
            <select id="locale" name="locale" class="input">
                <option value="en" @selected(old('locale', app()->getLocale()) === 'en')>English</option>
                <option value="ar" @selected(old('locale', app()->getLocale()) === 'ar')>العربية</option>
            </select>
        </div>
        <button class="btn btn-primary w-full !mt-6">{{ __('Create account') }}</button>
    </form>

    <p class="mt-7 text-sm text-center text-slate-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:text-brand-700 hover:underline">{{ __('Log in') }}</a>
    </p>
@endsection
