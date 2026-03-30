<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sabong Betting System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- NAVBAR --}}
    <nav class="bg-gray-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
            <span class="text-lg font-semibold tracking-wide">
                🐓 Sabong Betting System
            </span>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400">
                    {{ Auth::user()->name }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    {{-- SIDEBAR + CONTENT --}}
    <div class="flex min-h-screen">

        {{-- SIDEBAR --}}
        <aside class="w-56 bg-gray-800 text-white flex flex-col py-6 px-4 gap-1 min-h-screen">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-3">Menu</p>

            <a href="{{ route('owner.dashboard') }}"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm
                {{ request()->routeIs('owner.dashboard') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📊 Dashboard
            </a>

            <a href="{{ route('owner.users.index') }}"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm
                {{ request()->routeIs('owner.users.*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                👥 Users
            </a>

            <a href="{{ route('owner.fights.index') }}"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm
                {{ request()->routeIs('owner.fights.*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                🐓 Fights
            </a>

            <a href="{{ route('owner.audit-logs.index') }}"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm
                {{ request()->routeIs('owner.audit-logs.*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📋 Audit Logs
            </a>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 p-8">
            @yield('content')
        </main>

    </div>

@stack('scripts')
</body>
</html>
