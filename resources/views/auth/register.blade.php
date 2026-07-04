@extends('layouts.guest')

@section('title', __('Create account'))

@section('content')
    <h1 class="text-xl font-semibold mb-1">{{ __('Start your 7-day free trial') }}</h1>
    <p class="text-sm text-slate-500 mb-6">{{ __('1 company, up to 3 branches. No credit card required.') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Company name') }}</label>
            <input name="company_name" value="{{ old('company_name') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('company_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Industry') }}</label>
            <select name="industry" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @foreach(['Restaurants & Cafes', 'Retail', 'Clinics & Hospitals', 'Gyms & Fitness', 'Automotive', 'Beauty salons', 'Pharmacies', 'Services', 'Other'] as $industry)
                    <option value="{{ $industry }}" @selected(old('industry') === $industry)>{{ __($industry) }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Your name') }}</label>
                <input name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Phone') }}</label>
                <input name="phone" value="{{ old('phone') }}" placeholder="+9665…"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Password') }}</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Preferred language') }}</label>
            <select name="locale" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="en" @selected(old('locale', app()->getLocale()) === 'en')>English</option>
                <option value="ar" @selected(old('locale', app()->getLocale()) === 'ar')>العربية</option>
            </select>
        </div>
        <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Create account') }}</button>
    </form>

    <p class="mt-6 text-sm text-center text-slate-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:underline">{{ __('Log in') }}</a>
    </p>
@endsection
