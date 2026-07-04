<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) — {{ config('app.name', 'MapX') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen lg:flex">

    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-brand-950 text-white">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-6 h-16 border-b border-white/10">
            <span class="text-xl font-bold tracking-tight">Map<span class="text-brand-400">X</span></span>
            <span class="text-[10px] uppercase tracking-widest text-white/50 mt-1">{{ __('Presence 360') }}</span>
        </a>
        <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-3 text-sm">
            @php($user = auth()->user())
            @php($nav = fn ($route) => request()->routeIs($route) ? 'bg-brand-600 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white')

            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('dashboard') }}">📊 {{ __('Dashboard') }}</a>

            @if($user->hasPermission('branches.view'))
                <a href="{{ route('branches.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('branches.*') }}">📍 {{ __('Branches') }}</a>
            @endif
            @if($user->hasPermission('integrations.view'))
                <a href="{{ route('integrations.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('integrations.*') }}">🔌 {{ __('Integrations') }}</a>
            @endif
            @if($user->hasPermission('reviews.view'))
                <a href="{{ route('reviews.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('reviews.*') }}">⭐ {{ __('Reviews') }}</a>
            @endif
            @if($user->hasPermission('auto_rules.manage'))
                <a href="{{ route('auto-rules.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('auto-rules.*') }}">🤖 {{ __('Auto-replies') }}</a>
            @endif
            @if($user->hasPermission('qr.view'))
                <a href="{{ route('qr.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('qr.*') }}">🔗 {{ __('QR Reviews') }}</a>
            @endif
            @if($user->hasPermission('posts.view'))
                <a href="{{ route('posts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('posts.*') }}">📝 {{ __('Posts') }}</a>
            @endif
            @if($user->hasPermission('analytics.view'))
                <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('analytics.*') }}">📈 {{ __('Analytics') }}</a>
            @endif
            @if($user->hasPermission('rank.view'))
                <a href="{{ route('rank.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('rank.*') }}">🎯 {{ __('Local Rank') }}</a>
            @endif
            @if($user->hasPermission('verification.manage'))
                <a href="{{ route('verification.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('verification.*') }}">✅ {{ __('Verification') }}</a>
            @endif

            <div class="pt-3 mt-3 border-t border-white/10"></div>

            @if($user->hasPermission('users.manage'))
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('users.*') }}">👥 {{ __('Team') }}</a>
            @endif
            @if($user->hasPermission('roles.manage'))
                <a href="{{ route('roles.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('roles.*') }}">🛡️ {{ __('Roles') }}</a>
            @endif
            @if($user->hasPermission('billing.manage'))
                <a href="{{ route('billing.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('billing.*') }}">💳 {{ __('Billing') }}</a>
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('settings.*') }}">⚙️ {{ __('Settings') }}</a>
            @endif
            @if($user->hasPermission('audit.view'))
                <a href="{{ route('audit.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 {{ $nav('audit.*') }}">🧾 {{ __('Audit log') }}</a>
            @endif
        </nav>
        <div class="p-4 border-t border-white/10 text-xs text-white/60">
            {{ $user->company?->name }}
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="lg:hidden font-bold text-brand-900">Map<span class="text-brand-500">X</span></a>
                <h1 class="hidden lg:block text-lg font-semibold">@yield('title', __('Dashboard'))</h1>
            </div>
            <div class="flex items-center gap-4">
                @php($sub = auth()->user()->company?->subscription)
                @if($sub?->onTrial())
                    <a href="{{ route('billing.index') }}" class="hidden sm:inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-3 py-1 text-xs font-medium">
                        ⏳ {{ __('Trial ends :date', ['date' => $sub->trial_ends_at->diffForHumans()]) }}
                    </a>
                @endif
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                   class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">
                    {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                </a>
                <div class="flex items-center gap-2">
                    <div class="size-8 rounded-full bg-brand-600 text-white grid place-items-center text-sm font-semibold">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:block text-sm leading-tight">
                        <div class="font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-slate-500 text-xs">{{ auth()->user()->role?->name }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-slate-500 hover:text-red-600">{{ __('Log out') }}</button>
                </form>
            </div>
        </header>

        {{-- Mobile nav --}}
        <nav class="lg:hidden bg-brand-950 text-white/80 text-xs flex overflow-x-auto gap-1 px-2 py-2">
            <a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('dashboard') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            @if(auth()->user()->hasPermission('branches.view'))<a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('branches.*') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('branches.index') }}">{{ __('Branches') }}</a>@endif
            @if(auth()->user()->hasPermission('reviews.view'))<a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('reviews.*') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('reviews.index') }}">{{ __('Reviews') }}</a>@endif
            @if(auth()->user()->hasPermission('qr.view'))<a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('qr.*') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('qr.index') }}">QR</a>@endif
            @if(auth()->user()->hasPermission('posts.view'))<a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('posts.*') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('posts.index') }}">{{ __('Posts') }}</a>@endif
            @if(auth()->user()->hasPermission('analytics.view'))<a class="px-3 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('analytics.*') ? 'bg-brand-600 text-white' : '' }}" href="{{ route('analytics.index') }}">{{ __('Analytics') }}</a>@endif
        </nav>

        <main class="flex-1 p-4 lg:p-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc ms-4">
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
