@extends('layouts.guest')

@section('title', __('Log in'))

@section('content')
    <h1 class="text-xl font-semibold text-slate-900">{{ __('Welcome back') }}</h1>
    <p class="mt-1 text-sm text-slate-500 mb-7">{{ __('Sign in to manage your online presence.') }}</p>

    @if($errors->any())
        <div class="mb-5 flex items-center gap-2.5 rounded-xl bg-red-50 ring-1 ring-inset ring-red-600/15 text-red-700 px-4 py-3 text-sm" role="alert">
            <x-icon name="alert-triangle" class="size-4 shrink-0" />
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
        </div>
        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input">
        </div>
        <label class="flex items-center gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="checkbox"> {{ __('Remember me') }}
        </label>
        <button class="btn btn-primary w-full">
            {{ __('Log in') }}
            <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
        </button>
    </form>

    <p class="mt-7 text-sm text-center text-slate-500">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:text-brand-700 hover:underline">{{ __('Start your free trial') }}</a>
    </p>
@endsection
