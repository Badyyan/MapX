<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) — {{ config('app.name', 'MapX') }}</title>
    <script>
        // Apply the stored (or system) theme before first paint to avoid flashing.
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<div class="min-h-screen lg:flex">

    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-white/85 dark:bg-[#1d1d1f]/85 backdrop-blur-xl border-e border-slate-200/80 sticky top-0 h-screen">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-5 h-16 border-b border-slate-100">
            <span class="grid place-items-center size-8 rounded-lg bg-brand-600 text-white shadow-sm shadow-brand-600/30">
                <x-icon name="map-pin" class="size-4.5" />
            </span>
            <span class="text-lg font-bold tracking-tight text-slate-900">Map<span class="text-brand-600">X</span></span>
        </a>

        @php($user = auth()->user())
        @php($active = fn ($route) => request()->routeIs($route) ? 'nav-item nav-item-active' : 'nav-item')

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
            <div class="space-y-0.5">
                <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400">{{ __('Workspace') }}</p>
                <a href="{{ route('dashboard') }}" class="{{ $active('dashboard') }}"><x-icon name="layout-dashboard" class="size-4.5" /> {{ __('Dashboard') }}</a>
                @if($user->hasPermission('branches.view'))
                    <a href="{{ route('branches.index') }}" class="{{ $active('branches.*') }}"><x-icon name="map-pin" class="size-4.5" /> {{ __('Branches') }}</a>
                @endif
                @if($user->hasPermission('integrations.view'))
                    <a href="{{ route('integrations.index') }}" class="{{ $active('integrations.*') }}"><x-icon name="plug" class="size-4.5" /> {{ __('Integrations') }}</a>
                @endif
                @if($user->hasPermission('verification.manage'))
                    <a href="{{ route('verification.index') }}" class="{{ $active('verification.*') }}"><x-icon name="badge-check" class="size-4.5" /> {{ __('Verification') }}</a>
                @endif
            </div>

            <div class="space-y-0.5">
                <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400">{{ __('Reputation') }}</p>
                @if($user->hasPermission('reviews.view'))
                    <a href="{{ route('reviews.index') }}" class="{{ $active('reviews.*') }}"><x-icon name="star" class="size-4.5" /> {{ __('Reviews') }}</a>
                @endif
                @if($user->hasPermission('auto_rules.manage'))
                    <a href="{{ route('auto-rules.index') }}" class="{{ $active('auto-rules.*') }}"><x-icon name="bot" class="size-4.5" /> {{ __('Auto-replies') }}</a>
                @endif
                @if($user->hasPermission('qr.view'))
                    <a href="{{ route('qr.index') }}" class="{{ $active('qr.*') }}"><x-icon name="qr-code" class="size-4.5" /> {{ __('QR Reviews') }}</a>
                @endif
                @if($user->hasPermission('posts.view'))
                    <a href="{{ route('posts.index') }}" class="{{ $active('posts.*') }}"><x-icon name="pen-square" class="size-4.5" /> {{ __('Posts') }}</a>
                @endif
            </div>

            <div class="space-y-0.5">
                <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400">{{ __('Insights') }}</p>
                @if($user->hasPermission('analytics.view'))
                    <a href="{{ route('analytics.index') }}" class="{{ $active('analytics.*') }}"><x-icon name="chart-column" class="size-4.5" /> {{ __('Analytics') }}</a>
                @endif
                @if($user->hasPermission('rank.view'))
                    <a href="{{ route('rank.index') }}" class="{{ $active('rank.*') }}"><x-icon name="target" class="size-4.5" /> {{ __('Local Rank') }}</a>
                @endif
            </div>

            @if($user->hasPermission('users.manage') || $user->hasPermission('roles.manage') || $user->hasPermission('billing.manage') || $user->hasPermission('audit.view'))
                <div class="space-y-0.5">
                    <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400">{{ __('Administration') }}</p>
                    @if($user->hasPermission('users.manage'))
                        <a href="{{ route('users.index') }}" class="{{ $active('users.*') }}"><x-icon name="users" class="size-4.5" /> {{ __('Team') }}</a>
                    @endif
                    @if($user->hasPermission('roles.manage'))
                        <a href="{{ route('roles.index') }}" class="{{ $active('roles.*') }}"><x-icon name="shield" class="size-4.5" /> {{ __('Roles') }}</a>
                    @endif
                    @if($user->hasPermission('billing.manage'))
                        <a href="{{ route('billing.index') }}" class="{{ $active('billing.*') }}"><x-icon name="credit-card" class="size-4.5" /> {{ __('Billing') }}</a>
                        <a href="{{ route('settings.index') }}" class="{{ $active('settings.*') }}"><x-icon name="settings" class="size-4.5" /> {{ __('Settings') }}</a>
                    @endif
                    @if($user->hasPermission('audit.view'))
                        <a href="{{ route('audit.index') }}" class="{{ $active('audit.*') }}"><x-icon name="scroll-text" class="size-4.5" /> {{ __('Audit log') }}</a>
                    @endif
                </div>
            @endif
        </nav>

        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 bg-slate-50">
                <div class="size-9 shrink-0 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white grid place-items-center text-sm font-semibold shadow-sm">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $user->role?->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-sm !p-1.5" title="{{ __('Log out') }}" aria-label="{{ __('Log out') }}">
                        <x-icon name="log-out" class="size-4" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/70 dark:bg-[#1d1d1f]/70 backdrop-blur-xl border-b border-slate-200/80 flex items-center justify-between gap-3 px-4 lg:px-8 sticky top-0 z-20">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center gap-2" aria-label="MapX">
                    <span class="grid place-items-center size-7 rounded-lg bg-brand-600 text-white"><x-icon name="map-pin" class="size-4" /></span>
                    <span class="font-bold text-slate-900">Map<span class="text-brand-600">X</span></span>
                </a>
                <h1 class="hidden lg:block page-title truncate">@yield('title', __('Dashboard'))</h1>
            </div>
            <div class="flex items-center gap-2.5">
                @php($sub = auth()->user()->company?->subscription)
                @if($sub?->onTrial())
                    <a href="{{ route('billing.index') }}" class="hidden sm:inline-flex badge badge-warning !py-1.5 !px-3 hover:bg-amber-100 transition-colors">
                        <x-icon name="clock" class="size-3.5" />
                        {{ __('Trial ends :date', ['date' => $sub->trial_ends_at->diffForHumans()]) }}
                    </a>
                @endif
                <button type="button" onclick="toggleTheme()" class="btn btn-secondary btn-sm !p-2" aria-label="{{ __('Toggle theme') }}">
                    <span data-theme-icon="light"><x-icon name="moon" class="size-3.5" /></span>
                    <span data-theme-icon="dark" class="hidden"><x-icon name="sun" class="size-3.5" /></span>
                </button>
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                   class="btn btn-secondary btn-sm" aria-label="{{ __('Switch language') }}">
                    <x-icon name="languages" class="size-3.5" />
                    {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
                </a>
                <div class="lg:hidden">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost btn-sm !p-2" aria-label="{{ __('Log out') }}"><x-icon name="log-out" class="size-4" /></button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Mobile nav --}}
        <nav class="lg:hidden bg-white dark:bg-[#1d1d1f] border-b border-slate-200/80 flex overflow-x-auto gap-1 px-3 py-2 text-sm" aria-label="{{ __('Main navigation') }}">
            @php($mActive = fn ($route) => request()->routeIs($route) ? 'bg-brand-50 text-brand-700' : 'text-slate-500')
            <a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('dashboard') }}" href="{{ route('dashboard') }}"><x-icon name="layout-dashboard" class="size-4" /> {{ __('Dashboard') }}</a>
            @if($user->hasPermission('branches.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('branches.*') }}" href="{{ route('branches.index') }}"><x-icon name="map-pin" class="size-4" /> {{ __('Branches') }}</a>@endif
            @if($user->hasPermission('reviews.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('reviews.*') }}" href="{{ route('reviews.index') }}"><x-icon name="star" class="size-4" /> {{ __('Reviews') }}</a>@endif
            @if($user->hasPermission('qr.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('qr.*') }}" href="{{ route('qr.index') }}"><x-icon name="qr-code" class="size-4" /> QR</a>@endif
            @if($user->hasPermission('posts.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('posts.*') }}" href="{{ route('posts.index') }}"><x-icon name="pen-square" class="size-4" /> {{ __('Posts') }}</a>@endif
            @if($user->hasPermission('analytics.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('analytics.*') }}" href="{{ route('analytics.index') }}"><x-icon name="chart-column" class="size-4" /> {{ __('Analytics') }}</a>@endif
            @if($user->hasPermission('rank.view'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('rank.*') }}" href="{{ route('rank.index') }}"><x-icon name="target" class="size-4" /> {{ __('Local Rank') }}</a>@endif
            @if($user->hasPermission('billing.manage'))<a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg whitespace-nowrap font-medium {{ $mActive('billing.*') }}" href="{{ route('billing.index') }}"><x-icon name="credit-card" class="size-4" /> {{ __('Billing') }}</a>@endif
        </nav>

        <main class="flex-1 p-4 lg:p-8 space-y-6 animate-(--animate-fade-up)">
            @if(session('success'))
                <div class="card flex items-center gap-3 px-4 py-3 !ring-emerald-600/20 bg-emerald-50/60 text-sm text-emerald-800" role="status">
                    <x-icon name="check-circle" class="size-4.5 shrink-0 text-emerald-600" />
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="card flex items-center gap-3 px-4 py-3 !ring-red-600/20 bg-red-50/60 text-sm text-red-800" role="alert">
                    <x-icon name="alert-triangle" class="size-4.5 shrink-0 text-red-600" />
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="card px-4 py-3 !ring-red-600/20 bg-red-50/60 text-sm text-red-800" role="alert">
                    <div class="flex items-center gap-3 font-medium mb-1">
                        <x-icon name="alert-triangle" class="size-4.5 shrink-0 text-red-600" />
                        {{ __('Please fix the following:') }}
                    </div>
                    <ul class="list-disc ms-11 space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
