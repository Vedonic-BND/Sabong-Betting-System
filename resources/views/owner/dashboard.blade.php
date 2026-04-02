@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <span id="ws-status"
        class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-500">
        Connecting...
    </span>
</div>

{{-- STATS GRID --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <div class="bg-white rounded-xl shadow p-6">
        <p class="text-sm text-gray-500">Total Admins</p>
        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['total_admins'] }}</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <p class="text-sm text-gray-500">Total Tellers</p>
        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['total_tellers'] }}</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <p class="text-sm text-gray-500">Total Fights</p>
        <p id="total-fights" class="text-3xl font-bold text-gray-800 mt-1">
            {{ $stats['total_fights'] }}
        </p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <p class="text-sm text-gray-500">Total Bets</p>
        <p id="total-bets" class="text-3xl font-bold text-gray-800 mt-1">
            ₱{{ number_format($stats['total_bets'], 2) }}
        </p>
    </div>

</div>

{{-- LIVE FEED --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-700">Live Feed</h3>
        <span class="text-xs text-gray-400">Updates in real-time</span>
    </div>
    <ul id="live-feed" class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
        <li class="px-6 py-4 text-sm text-gray-400 text-center">
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
