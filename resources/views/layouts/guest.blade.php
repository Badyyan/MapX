<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'MapX'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-brand-950 via-brand-800 to-brand-600 min-h-screen text-slate-800 antialiased">
<div class="min-h-screen flex flex-col items-center justify-center p-4">
    <a href="/" class="mb-6 text-3xl font-bold text-white tracking-tight">Map<span class="text-brand-300">X</span></a>
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        @yield('content')
    </div>
    <p class="mt-6 text-xs text-white/60">{{ __('Online presence & reputation management for your business') }}</p>
</div>
</body>
</html>
