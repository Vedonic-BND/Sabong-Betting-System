@extends('layouts.app')

@section('title', 'Financial Overview')

@section('content')

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">Financial Overview</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Comprehensive financial analytics & reporting</p>
    </div>
    <a href="{{ route('owner.dashboard') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium flex items-center gap-2">
        ← Back to Dashboard
    </a>
</div>

{{-- REVENUE SECTION --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
        </svg>
        Revenue Overview
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Paid Earnings --}}
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium uppercase tracking-wide mb-2">Paid Earnings</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($stats['total_earnings'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">Commission from completed payouts</p>
        </div>

        {{-- Pending Earnings --}}
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium uppercase tracking-wide mb-2">Pending Earnings</p>
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">₱{{ number_format($stats['pending_earnings'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">Commission awaiting payout</p>
        </div>

        {{-- Total Earnings --}}
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium uppercase tracking-wide mb-2">Total Earnings</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">₱{{ number_format(($stats['total_earnings'] ?? 0) + ($stats['pending_earnings'] ?? 0), 2) }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">Paid + Pending commissions</p>
        </div>
    </div>
</div>

{{-- BETS & PAYOUTS SECTION --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    {{-- Bets Overview --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.16 2.75a.75.75 0 00-1.08.6v12.97a.75.75 0 001.08.6l8.899-6.488a.75.75 0 000-1.2L8.16 2.75z" />
            </svg>
            Bets Overview
        </h2>

        <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Bets Amount</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Sum of all placed bets</p>
                </div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">₱{{ number_format($stats['total_bets'] ?? 0, 2) }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Bet Count</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Number of individual bets</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_bet_count'] ?? 0 }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Unclaimed Bets</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Bets awaiting payout</p>
                </div>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format($stats['unclaimed_bets'] ?? 0, 2) }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Unclaimed Bet Count</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Number of unclaimed bets</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['unclaimed_bet_count'] ?? 0 }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Average Bet Size</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Average amount per bet</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($summary['average_bet'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Payouts Overview --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a1 1 0 001 1h12a1 1 0 001-1V6a2 2 0 00-2-2H4zm0 6a1 1 0 001 1h6a1 1 0 001-1v-1H4v1z" clip-rule="evenodd" />
            </svg>
            Payouts Overview
        </h2>

        <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Payouts</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Gross payout amount</p>
                </div>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($stats['total_payouts'] ?? 0, 2) }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Paid Payouts</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Already paid out</p>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($stats['paid_payouts'] ?? 0, 2) }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Pending Payouts</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Awaiting payout</p>
                </div>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">₱{{ number_format($stats['pending_payouts'] ?? 0, 2) }}</p>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Average Payout per Fight</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Average by number of fights</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($summary['average_payout'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- FIGHTS SECTION --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.5 1.5H5.75A2.75 2.75 0 003 4.25v11.5A2.75 2.75 0 005.75 18.5h8.5A2.75 2.75 0 0017 15.75V8.5m-13-5h8m-8 3h8m-8 3h5" />
        </svg>
        Fights Summary
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Fights</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">All fights in system</p>
            </div>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['total_fights'] ?? 0 }}</p>
        </div>

        <div class="flex justify-between items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Completed Fights</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Fights with status done</p>
            </div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['fights_completed'] ?? 0 }}</p>
        </div>
    </div>
</div>

{{-- TELLER CASH STATUS SECTION --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mt-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.5 1.5H5.75A2.75 2.75 0 003 4.25v11.5A2.75 2.75 0 005.75 18.5h8.5A2.75 2.75 0 0017 15.75V8.5m-13-5h8m-8 3h8m-8 3h5" />
        </svg>
        Teller Cash Status
    </h2>

    @if($tellerCashStatus && count($tellerCashStatus) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($tellerCashStatus as $teller)
                <div id="teller-card-{{ $teller['teller_id'] }}" class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900/30 dark:to-slate-800/30 rounded-xl p-6 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $teller['teller_name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-timestamp>
                                Last Updated: {{ $teller['last_updated'] ? $teller['last_updated']->format('M d, Y H:i') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        {{-- Bet In --}}
                        <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Bet In</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Bets received</p>
                            </div>
                            <p id="bet-in-{{ $teller['teller_id'] }}" class="text-lg font-bold text-blue-600 dark:text-blue-400" data-bet-in>₱{{ number_format($teller['total_cash_in'] ?? 0, 2) }}</p>
                        </div>

                        {{-- Payout --}}
                        <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Payout</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Amount paid out</p>
                            </div>
                            <p id="payout-{{ $teller['teller_id'] }}" class="text-lg font-bold text-purple-600 dark:text-purple-400" data-payout>₱{{ number_format($teller['total_paid_out'] ?? 0, 2) }}</p>
                        </div>

                        {{-- Provided --}}
                        <div class="flex justify-between items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Provided</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Runner cash in</p>
                            </div>
                            <p id="provided-{{ $teller['teller_id'] }}" class="text-lg font-bold text-emerald-600 dark:text-emerald-400" data-provided>₱{{ number_format($teller['cash_provided'] ?? 0, 2) }}</p>
                        </div>

                        {{-- Collected --}}
                        <div class="flex justify-between items-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Collected</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Runner cash out</p>
                            </div>
                            <p id="collected-{{ $teller['teller_id'] }}" class="text-lg font-bold text-orange-600 dark:text-orange-400" data-collected>₱{{ number_format($teller['cash_collected'] ?? 0, 2) }}</p>
                        </div>

                        {{-- On Hand Cash --}}
                        <div class="flex justify-between items-center p-3 bg-gray-100 dark:bg-gray-700/50 rounded-lg border-2 border-gray-300 dark:border-gray-600">
                            <div>
                                <p class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">On Hand</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Current balance</p>
                            </div>
                            <p id="on-hand-{{ $teller['teller_id'] }}" class="text-lg font-bold {{ ($teller['on_hand_cash'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" data-on-hand>₱{{ number_format($teller['on_hand_cash'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium">No tellers with cash on hand</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">All teller cash balances are zero</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
// ── Real-Time Updates via WebSocket ────────────────────────
// Preferred: WebSocket provides immediate updates on data changes
// Fallback: 30-second auto-reload if WebSocket unavailable

console.log('📍 [Financial Overview] Script starting...');
console.log('📍 [Financial Overview] window.Echo defined:', typeof window.Echo !== 'undefined');

// Small delay to ensure Echo is fully initialized
setTimeout(() => {
    console.log('📍 [Financial Overview] After delay - window.Echo defined:', typeof window.Echo !== 'undefined');
    
    let webSocketConnected = false;

    if (typeof window.Echo !== 'undefined') {
        try {
            console.log('✅ [Financial Overview] Echo available, attempting WebSocket connection...');
            
            // Set up the listener
            const listener = window.Echo.channel('cash-status')
                .listen('teller.cash-updated', (event) => {
                    console.log('🔔 [Financial Overview] RECEIVED EVENT from WebSocket:', event);
                    console.log('🔔 [Financial Overview] Event timestamp:', new Date().toLocaleTimeString());
                    location.reload();
                });
            
            console.log('📍 [Financial Overview] Listener setup object:', listener);
            webSocketConnected = true;
            console.log('✅ [Financial Overview] WebSocket listener active and ready');
            console.log('🎯 [Financial Overview] Waiting for events on channel: cash-status');
            console.log('🎯 [Financial Overview] Listening for event name: teller.cash-updated');
        } catch (error) {
            console.error('❌ [Financial Overview] Error setting up WebSocket listener:', error);
            console.error('Error details:', error.message);
        }
    } else {
        console.warn('⚠️ [Financial Overview] Echo not available - window.Echo is undefined');
        console.warn('This might happen if:');
        console.warn('  1. Reverb server is not running');
        console.warn('  2. BROADCAST_CONNECTION is set to "null" in .env');
        console.warn('  3. echo.js failed to load');
    }

    // Fallback: Auto-reload every 30 seconds if WebSocket not working
    // This ensures page updates even if Reverb server is down
    if (!webSocketConnected) {
        console.log('⏱️ [Financial Overview] WebSocket unavailable - enabling 30-second auto-reload fallback');
        
        setInterval(() => {
            console.log('🔄 [Financial Overview] Auto-reloading page (fallback)...');
            location.reload();
        }, 30000);
    }
}, 1000);  // Wait 1 second for Echo to initialize
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
</style>

@endpush

@endsection
