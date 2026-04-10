@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard</h2>
    <span id="ws-status"
        class="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
        Connecting...
    </span>
</div>

{{-- USERS & FIGHTS GRID --}}
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Users & Fights</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Admins</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['total_admins'] }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Tellers</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['total_tellers'] }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Fights</p>
            <p id="total-fights" class="text-3xl font-bold text-gray-800 dark:text-white mt-1">
                {{ $stats['total_fights'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">Fights Completed</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">0</p>
        </div>

    </div>
</div>

{{-- MONEY STATS GRID --}}
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Financial Overview</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Bets</p>
            <p id="total-bets" class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                ₱{{ number_format($stats['total_bets'], 2) }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Amount wagered</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Earnings</p>
            <p id="total-earnings" class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                ₱{{ number_format($stats['total_earnings'], 2) }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Commission earned</p>
        </div>

    </div>
</div>

{{-- LIVE FEED --}}
<hr class="mb-6 border-gray-300 dark:border-gray-700">
<div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-700 dark:text-white">Live Feed</h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Updates in real-time</span>
    </div>
    <ul id="live-feed" class="divide-y divide-gray-100 dark:divide-gray-700 max-h-80 overflow-y-auto">
        <li class="px-6 py-4 text-sm text-gray-400 dark:text-gray-500 text-center">
            Waiting for activity...
        </li>
    </ul>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    function waitForEcho(callback) {
        if (typeof window.Echo !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForEcho(callback), 100);
        }
    }

    waitForEcho(function () {

        // ── WebSocket status ──────────────────────────
        window.Echo.connector.pusher.connection.bind('connected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Live';
            s.className   = 'text-xs px-3 py-1 rounded-full bg-green-100 text-green-700';
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Disconnected';
            s.className   = 'text-xs px-3 py-1 rounded-full bg-red-100 text-red-700';
        });

        // ── Listen for events ─────────────────────────
        window.Echo.channel('fights')
            .listen('.fight.updated', (data) => {
                addFeedItem(
                    `Fight #${data.fight_number} status changed to <strong>${data.status}</strong>`,
                    'text-yellow-700'
                );
                // refresh total fights count
                fetch('/owner/stats')
                    .then(r => r.json())
                    .then(stats => {
                        document.getElementById('total-fights').textContent =
                            stats.total_fights;
                        document.getElementById('total-bets').textContent =
                            '₱' + parseFloat(stats.total_bets).toLocaleString('en-PH', {
                                minimumFractionDigits: 2
                            });
                    });
            })
            .listen('.bet.placed', (data) => {
                addFeedItem(
                    `New bet — <strong>${data.side.toUpperCase()}</strong>
                     ₱${parseFloat(data.amount).toLocaleString()}
                     by ${data.teller}`,
                    'text-blue-700'
                );
                // update total bets
                fetch('/owner/stats')
                    .then(r => r.json())
                    .then(stats => {
                        document.getElementById('total-bets').textContent =
                            '₱' + parseFloat(stats.total_bets).toLocaleString('en-PH', {
                                minimumFractionDigits: 2
                            });
                    });
            })
            .listen('.winner.declared', (data) => {
                addFeedItem(
                    `🏆 Fight #${data.fight_number} — Winner: <strong>${data.winner.toUpperCase()}</strong>`,
                    'text-green-700'
                );
                // refresh earnings when payouts are finalized
                fetch('/owner/stats')
                    .then(r => r.json())
                    .then(stats => {
                        document.getElementById('total-earnings').textContent =
                            '₱' + parseFloat(stats.total_earnings).toLocaleString('en-PH', {
                                minimumFractionDigits: 2
                            });
                    });
            });

        function addFeedItem(message, color = 'text-gray-700') {
            const feed = document.getElementById('live-feed');
            const placeholder = feed.querySelector('.text-center');
            if (placeholder) placeholder.remove();

            const time = new Date().toLocaleTimeString();
            const li   = document.createElement('li');
            li.className = 'px-6 py-3 text-sm flex justify-between items-center';
            li.innerHTML = `
                <span class="${color}">${message}</span>
                <span class="text-xs text-gray-400">${time}</span>
            `;
            feed.prepend(li);
        }

    }); // end waitForEcho
});
</script>
@endpush

@endsection
