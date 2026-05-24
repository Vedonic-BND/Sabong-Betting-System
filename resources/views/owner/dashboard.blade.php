@extends('layouts.app')

@section('title', 'Owner Dashboard')

@section('content')

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">Owner Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Real-time business metrics & performance</p>
    </div>
    <div class="flex items-center gap-4">
        <a href="{{ route('owner.financial-overview') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" clip-rule="evenodd" />
            </svg>
            Financial Overview
        </a>
        <button id="eod-btn" class="text-green-600 dark:text-green-400 hover:underline text-sm font-medium flex items-center gap-2 px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors" onclick="generateEodReport()">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            EOD Report
        </button>
        <span id="ws-status" class="text-xs px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-medium flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-gray-400 rounded-full"></span>
            Connecting...
        </span>
    </div>
</div>

{{-- KPI CARDS - EXECUTIVE SUMMARY --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    {{-- Today's Revenue --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">Today's Revenue</p>
                <p id="today-revenue" class="text-4xl font-bold text-green-600 dark:text-green-400 mt-2">
                    ₱{{ number_format($stats['total_earnings'] ?? 0, 2) }}
                </p>
            </div>
            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">Commission from paid payouts</p>
    </div>

    {{-- Total Handle (Active Bets) --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">Total Handle</p>
                <p id="total-handle" class="text-4xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    ₱{{ number_format($stats['total_bets'] ?? 0, 2) }}
                </p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.16 2.75a.75.75 0 00-1.08.6v12.97a.75.75 0 001.08.6l8.899-6.488a.75.75 0 000-1.2L8.16 2.75z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">Sum of all bets</p>
    </div>

    {{-- Unclaimed Bets --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">Unclaimed Bets</p>
                <p id="unclaimed-bets" class="text-4xl font-bold text-amber-600 dark:text-amber-400 mt-2">
                    ₱{{ number_format($stats['unclaimed_bets'] ?? 0, 2) }}
                </p>
            </div>
            <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-lg">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.22 5a.75.75 0 0 0-1.06 1.06L9.44 9.5H3.75a.75.75 0 0 0 0 1.5h5.69l-2.28 2.28a.75.75 0 1 0 1.06 1.06l3.5-3.5a.75.75 0 0 0 0-1.06l-3.5-3.5ZM11.78 15a.75.75 0 0 0 1.06-1.06L10.56 10.5h5.69a.75.75 0 0 0 0-1.5h-5.69l2.28-2.28a.75.75 0 0 0-1.06-1.06l-3.5 3.5a.75.75 0 0 0 0 1.06l3.5 3.5Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">Bets awaiting payout claim</p>
    </div>

    {{-- Active Fights --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">Active Fights</p>
                <p id="active-fights" class="text-4xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                    {{ $stats['total_fights'] ?? 0 }}
                </p>
            </div>
            <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-lg">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.5 1.5H5.75A2.75 2.75 0 003 4.25v11.5A2.75 2.75 0 005.75 18.5h8.5A2.75 2.75 0 0017 15.75V8.5m-13-5h8m-8 3h8m-8 3h5" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">Open or closed fights</p>
    </div>

</div>

{{-- SECONDARY METRICS ROW --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

    {{-- USER MANAGEMENT --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            User Management
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-700 dark:text-gray-300">Total Admins</span>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_admins'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-700 dark:text-gray-300">Total Tellers</span>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_tellers'] ?? 0 }}</span>
            </div>
        </div>
        <a href="{{ route('owner.users.index') }}" class="mt-4 inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
            Manage Users →
        </a>
    </div>

    {{-- FIGHT STATISTICS --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            Fight Performance
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-700 dark:text-gray-300">Total Fights</span>
                <span id="fights-count" class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_fights'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-700 dark:text-gray-300">Fights Completed</span>
                <span id="fights-completed" class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['fights_completed'] ?? 0 }}</span>
            </div>
        </div>
        <a href="{{ route('owner.fights.index') }}" class="mt-4 inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
            View Fights →
        </a>
    </div>

</div>

{{-- LIVE ACTIVITY FEED --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 animate-pulse text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V5z" />
                </svg>
                Live Activity Feed
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Real-time events</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-xs font-medium text-green-600 dark:text-green-400">Live</span>
        </div>
    </div>
    <ul id="live-feed" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
        <li class="px-6 py-8 text-sm text-gray-400 dark:text-gray-500 text-center flex items-center justify-center h-32">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Waiting for activity...
            </div>
        </li>
    </ul>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Utility Functions ─────────────────────────────
    function formatCurrency(value) {
        return '₱' + parseFloat(value).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function animateNumber(elementId, oldValue, newValue, duration = 600) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const isText = element.textContent.includes('₱');
        oldValue = parseFloat(oldValue) || 0;
        newValue = parseFloat(newValue) || 0;

        if (Math.abs(newValue - oldValue) < 0.01) {
            // No significant change, just update
            element.textContent = isText ? formatCurrency(newValue) : newValue.toString();
            return;
        }

        const increment = (newValue - oldValue) / 30;
        let current = oldValue;
        const startTime = Date.now();

        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);

            current = oldValue + (increment * 30 * progress);

            if (isText) {
                element.textContent = formatCurrency(current);
            } else {
                element.textContent = Math.round(current).toString();
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.textContent = isText ? formatCurrency(newValue) : newValue.toString();
            }
        };

        animate();
    }

    function waitForEcho(callback) {
        if (typeof window.Echo !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForEcho(callback), 100);
        }
    }

    waitForEcho(function () {

        // ── WebSocket Status Indicator ────────────────
        const wsStatus = document.getElementById('ws-status');
        const updateStatus = (connected) => {
            if (connected) {
                wsStatus.innerHTML = '<span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Live';
                wsStatus.className = 'text-xs px-4 py-2 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-medium flex items-center gap-2';
            } else {
                wsStatus.innerHTML = '<span class="inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse"></span> Disconnected';
                wsStatus.className = 'text-xs px-4 py-2 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 font-medium flex items-center gap-2';
            }
        };

        window.Echo.connector.pusher.connection.bind('connected', () => updateStatus(true));
        window.Echo.connector.pusher.connection.bind('disconnected', () => updateStatus(false));

        // ── Live Event Listeners ──────────────────────
        window.Echo.channel('fights')
            .listen('.fight.updated', (data) => {
                addFeedItem(
                    `<span class="text-yellow-600 dark:text-yellow-400 font-semibold">Fight #${data.fight_number}</span> status changed to <span class="font-bold uppercase">${data.status}</span>`,
                    'text-yellow-600 dark:text-yellow-400'
                );
                refreshDashboardData();
            })
            .listen('.bet.placed', (data) => {
                const sideText = data.side.toUpperCase();
                addFeedItem(
                    `<span class="font-semibold">${sideText}</span> bet of <span class="font-bold text-blue-600 dark:text-blue-400">${formatCurrency(data.amount)}</span> by <span class="font-medium">${data.teller}</span>`,
                    'text-blue-600 dark:text-blue-400'
                );
                refreshDashboardData();
            })
            .listen('.bet.deleted', (data) => {
                addFeedItem(
                    `<span class="font-semibold">Bet deleted:</span> <span class="text-orange-600 dark:text-orange-400">${data.teller}</span> removed <span class="font-bold">${formatCurrency(data.amount)}</span>`,
                    'text-orange-600 dark:text-orange-400'
                );
                refreshDashboardData();
            })
            .listen('.winner.declared', (data) => {
                const winnerText = data.winner.toUpperCase();
                addFeedItem(
                    `🏆 <span class="font-bold text-green-600 dark:text-green-400">Fight #${data.fight_number}</span> winner: <span class="font-bold text-green-600 dark:text-green-400">${winnerText}</span>`,
                    'text-green-600 dark:text-green-400'
                );
                refreshDashboardData();
            });

        // ── Cash Transaction Listener ──────────────────
        window.Echo.channel('cash-status')
            .listen('teller.cash-updated', (data) => {
                if (data.type === 'cash_in') {
                    addFeedItem(
                        `💵 <span class="font-medium">${data.teller_name}</span> received <span class="font-bold text-green-600 dark:text-green-400">₱${formatCurrency(parseFloat(data.amount))}</span> from runner`,
                        'text-green-600 dark:text-green-400'
                    );
                } else if (data.type === 'cash_out') {
                    addFeedItem(
                        `💸 Runner collected <span class="font-bold text-orange-600 dark:text-orange-400">₱${formatCurrency(parseFloat(data.amount))}</span> from <span class="font-medium">${data.teller_name}</span>`,
                        'text-orange-600 dark:text-orange-400'
                    );
                }
                refreshDashboardData();
            });

        // ── Refresh Dashboard Data ────────────────────
        function refreshDashboardData() {
            fetch('/owner/stats')
                .then(r => {
                    if (!r.ok) throw new Error('Failed to fetch stats');
                    return r.json();
                })
                .then(stats => {
                    // Animate KPI updates
                    animateNumber('today-revenue',
                        parseFloat(document.getElementById('today-revenue').textContent.replace('₱', '').replace(/,/g, '')),
                        stats.total_earnings || 0
                    );
                    animateNumber('total-handle',
                        parseFloat(document.getElementById('total-handle').textContent.replace('₱', '').replace(/,/g, '')),
                        stats.total_bets || 0
                    );
                    animateNumber('active-fights',
                        parseInt(document.getElementById('active-fights').textContent),
                        stats.total_fights || 0
                    );
                    animateNumber('fights-count',
                        parseInt(document.getElementById('fights-count').textContent),
                        stats.total_fights || 0
                    );
                    animateNumber('fights-completed',
                        parseInt(document.getElementById('fights-completed').textContent),
                        stats.fights_completed || 0
                    );
                })
                .catch(err => console.error('Dashboard refresh error:', err));
        }

        // ── Add Feed Item with Animation ──────────────
        function addFeedItem(message, color = 'text-gray-700 dark:text-gray-300') {
            const feed = document.getElementById('live-feed');
            const placeholder = feed.querySelector('li');

            // Remove placeholder if this is the first item
            if (placeholder && placeholder.textContent.includes('Waiting')) {
                placeholder.remove();
            }

            const time = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit'
            });

            const li = document.createElement('li');
            li.className = `px-6 py-4 text-sm flex justify-between items-start gap-4 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors ${color}`;
            li.style.opacity = '0';
            li.style.transform = 'translateY(-10px)';

            li.innerHTML = `
                <span class="flex-1">${message}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono">${time}</span>
            `;

            feed.prepend(li);

            // Animate in
            setTimeout(() => {
                li.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                li.style.opacity = '1';
                li.style.transform = 'translateY(0)';
            }, 10);

            // Keep feed manageable (max 50 items)
            while (feed.children.length > 50) {
                feed.removeChild(feed.lastChild);
            }
        }

    }); // end waitForEcho
});

// EOD Report Generation and Download
async function generateEodReport() {
    const btn = document.getElementById('eod-btn');
    const originalText = btn.innerHTML;

    try {
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block animate-spin">⟳</span> Generating...';

        // Generate the report
        const response = await fetch('{{ route("owner.eod-report.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to generate EOD report');
        }

        // Get today's date
        const today = new Date().toISOString().split('T')[0];
        const files = [
            `audit-logs-eod-${today}.csv`,
            `fights-eod-${today}.csv`,
            `transactions-eod-${today}.csv`,
            `summary-eod-${today}.txt`
        ];

        btn.innerHTML = '📥 Downloading...';

        // Download each file
        for (const file of files) {
            const downloadUrl = '{{ route("owner.eod-report.download", ":filename") }}'.replace(':filename', file);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = file;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Small delay between downloads
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        btn.innerHTML = '✓ Complete';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 2000);

    } catch (error) {
        console.error('EOD Report Error:', error);
        btn.innerHTML = '✗ Error';
        btn.title = error.message;
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 2000);
    }
}
</script>
@endpush

@endsection
