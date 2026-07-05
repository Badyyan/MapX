<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'MapX'))</title>
    <script>
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<div class="relative min-h-screen bg-[#0a0a0c] flex flex-col items-center justify-center overflow-hidden p-4 py-10">
    {{-- Ambient background --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-40 start-1/2 -translate-x-1/2 size-[42rem] rounded-full bg-brand-600/25 blur-3xl"></div>
        <div class="absolute -bottom-52 -start-40 size-[36rem] rounded-full bg-violet-600/15 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(148_163_184/0.08)_1px,transparent_0)] bg-[size:28px_28px]"></div>
    </div>

    <a href="/" class="relative mb-8 flex items-center gap-2.5 animate-(--animate-fade-up)">
        <span class="grid place-items-center size-10 rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/40">
            <x-icon name="map-pin" class="size-5" />
        </span>
        <span class="text-2xl font-bold tracking-tight text-white">Map<span class="text-brand-400">X</span></span>
    </a>

    <div class="relative w-full max-w-md bg-white dark:bg-[#1f1f22] rounded-2xl shadow-(--shadow-pop) p-8 animate-(--animate-fade-up)" style="animation-delay: 60ms">
        @yield('content')
    </div>

    <p class="relative mt-8 text-xs text-slate-400 animate-(--animate-fade-up)" style="animation-delay: 120ms">
        {{ __('Online presence & reputation management for your business') }}
    </p>
</div>
</body>
</html>
