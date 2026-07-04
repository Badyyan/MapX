@extends('layouts.guest')

@section('title', __('Log in'))

@section('content')
    <h1 class="text-xl font-semibold mb-6">{{ __('Welcome back') }}</h1>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Password') }}</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> {{ __('Remember me') }}
        </label>
        <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Log in') }}</button>
    </form>

    <p class="mt-6 text-sm text-center text-slate-500">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:underline">{{ __('Start your free trial') }}</a>
    </p>
@endsection
