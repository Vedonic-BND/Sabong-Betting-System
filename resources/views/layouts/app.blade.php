<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sabong Betting System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Initialize dark mode on page load
        function initializeDarkMode() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Run immediately and on DOM ready
        initializeDarkMode();
        document.addEventListener('DOMContentLoaded', initializeDarkMode);

        // Global function to toggle dark mode
        window.toggleDarkMode = function() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('class', document.documentElement.className.replace('dark', '').trim());
                localStorage.setItem('darkMode', 'false');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            }
            // Force reflow to trigger CSS re-evaluation
            void document.documentElement.offsetHeight;
            // Update the button icon visibility
            window.updateDarkModeIcon();
        }

        window.updateDarkModeIcon = function() {
            const isDark = document.documentElement.classList.contains('dark');
            const moonIcon = document.querySelector('[data-moon-icon]');
            const sunIcon = document.querySelector('[data-sun-icon]');
            if (moonIcon && sunIcon) {
                moonIcon.style.display = isDark ? 'none' : 'inline';
                sunIcon.style.display = isDark ? 'inline' : 'none';
            }
        }

        // Initialize icons on load
        document.addEventListener('DOMContentLoaded', function() {
            window.updateDarkModeIcon();
            // Check again after a brief delay to ensure DOM is ready
            setTimeout(window.updateDarkModeIcon, 100);
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen transition-colors" x-data="{ sidebarOpen: true }">

    {{-- NAVBAR --}}
    <nav class="bg-gray-900 dark:bg-gray-950 text-white shadow-md">
        <div class="px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-white hover:bg-gray-700 dark:hover:bg-gray-800 p-2 rounded transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="text-lg font-semibold tracking-wide">
                    🐓 Sabong Betting System
                </span>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="window.toggleDarkMode()"
                    class="p-2 rounded-lg bg-gray-700 dark:bg-gray-800 hover:bg-gray-600 dark:hover:bg-gray-700 transition"
                    title="Toggle dark mode">
                    <span data-moon-icon class="text-lg">🌙</span>
                    <span data-sun-icon class="text-lg" style="display: none;">☀️</span>
                </button>
                <div class="relative group">
                    <button class="text-sm text-gray-400 hover:text-white flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-700 dark:hover:bg-gray-800 transition">
                        {{ Auth::user()->name }}
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="absolute right-0 mt-0 w-48 bg-gray-700 dark:bg-gray-800 text-white rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="{{ route('owner.profile.show') }}"
                            class="block px-4 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 first:rounded-t">
                            ⚙️ Profile Settings
                        </a>
                        <a href="{{ route('owner.settings.show') }}"
                            class="block px-4 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700">
                            🔧 Display Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-600 dark:border-gray-700">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 last:rounded-b transition">
                                🚪 Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- SIDEBAR + CONTENT --}}
    <div class="flex min-h-screen">

        {{-- SIDEBAR --}}
        <aside :class="sidebarOpen ? 'w-56' : 'w-20'" class="bg-gray-800 dark:bg-gray-950 text-white flex flex-col py-6 px-4 gap-1 min-h-screen transition-all duration-300 ease-in-out overflow-hidden">
            <p :class="sidebarOpen ? 'opacity-100' : 'opacity-50'" class="text-xs text-gray-500 dark:text-gray-600 uppercase tracking-widest mb-3 transition-opacity duration-300">Menu</p>

            <a href="{{ route('owner.dashboard') }}"
                :title="!sidebarOpen ? 'Dashboard' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.dashboard') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">📊</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Dashboard</span>
            </a>

            <a href="{{ route('owner.users.index') }}"
                :title="!sidebarOpen ? 'Users' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.users.*') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">👥</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Users</span>
            </a>

            <a href="{{ route('owner.notifications.index') }}"
                :title="!sidebarOpen ? 'Requests' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.notifications.*') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">🔔</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Requests</span>
            </a>

            <a href="{{ route('owner.fights.index') }}"
                :title="!sidebarOpen ? 'Fights' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.fights.*') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">🐓</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Fights</span>
            </a>

            <a href="{{ route('owner.audit-logs.index') }}"
                :title="!sidebarOpen ? 'Audit Logs' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.audit-logs.*') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">📋</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Audit Logs</span>
            </a>

            <a href="{{ route('owner.transactions.index') }}"
                :title="!sidebarOpen ? 'Transactions' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.transactions.*') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">💱</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Transactions</span>
            </a>

            <hr class="my-2 border-gray-700 dark:border-gray-800">

            <a href="{{ route('owner.profile.show') }}"
                :title="!sidebarOpen ? 'Profile' : ''"
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-800 text-sm whitespace-nowrap transition
                {{ request()->routeIs('owner.profile.show') ? 'bg-gray-700 dark:bg-gray-800 text-white' : 'text-gray-300 dark:text-gray-400' }}">
                <span class="flex-shrink-0">⚙️</span>
                <span :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'" class="transition-opacity duration-300">Profile</span>
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
