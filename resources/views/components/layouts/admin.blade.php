<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - Obii KriationZ Accounts</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200" x-transition:leave="transition-opacity ease-linear duration-200" x-cloak class="fixed inset-0 bg-black/40 z-40 lg:hidden" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-primary-800 transition-transform duration-200 ease-in-out lg:translate-x-0 lg:z-30 flex flex-col"
    >
        {{-- Brand --}}
        <div class="flex items-center gap-3 h-16 px-5 border-b border-primary-700/50">
            <img src="{{ asset('images/logo-obii-kriationz.png') }}" alt="Obii KriationZ" class="h-8" />
        </div>
        <div class="px-5 py-2">
            <span class="text-xs font-medium text-primary-300 uppercase tracking-wider">Accounts</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                Dashboard
            </a>

            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-primary-400 uppercase tracking-wider">Manage</span>
            </div>

            <a href="{{ route('admin.customers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.customers.*') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Customers
            </a>

            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-primary-400 uppercase tracking-wider">Sales</span>
            </div>

            <a href="{{ route('admin.sales.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.sales.*') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Invoices
            </a>

            <a href="{{ route('admin.import') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.import*') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>

            @if(auth()->user()->hasPermission('users', 'read') || auth()->user()->hasPermission('roles', 'read'))
                <div class="pt-4 pb-1 px-3">
                    <span class="text-xs font-semibold text-primary-400 uppercase tracking-wider">Settings</span>
                </div>

                @if(auth()->user()->hasPermission('users', 'read'))
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                @endif

                @if(auth()->user()->hasPermission('roles', 'read'))
                    <a href="{{ route('admin.roles.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.roles.*') ? 'bg-white/10 text-white' : 'text-primary-200 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Roles
                    </a>
                @endif
            @endif
        </nav>

        {{-- User section --}}
        <div class="border-t border-primary-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-sm font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-primary-300 truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="p-1.5 text-primary-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content area --}}
    <div class="lg:pl-64 min-h-screen flex flex-col">
        {{-- Top bar (mobile) --}}
        <header class="sticky top-0 z-20 bg-white border-b border-gray-200 shadow-sm lg:hidden">
            <div class="flex items-center justify-between h-14 px-4">
                <button @click="sidebarOpen = true" class="p-2 text-gray-600 hover:text-primary-800 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <img src="{{ asset('images/logo-obii-kriationz.png') }}" alt="Obii KriationZ" class="h-6" />
                <div class="w-9"></div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
