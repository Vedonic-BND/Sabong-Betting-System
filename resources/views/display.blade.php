<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Display — Sabong Betting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #0a0a0a; }

        @keyframes pulse-meron {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50%       { box-shadow: 0 0 40px 10px rgba(239, 68, 68, 0.2); }
        }
        @keyframes pulse-wala {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            50%       { box-shadow: 0 0 40px 10px rgba(59, 130, 246, 0.2); }
        }
        @keyframes winner-pop {
            0%   { transform: scale(0.5); opacity: 0; }
            70%  { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .pulse-meron { animation: pulse-meron 2s infinite; }
        .pulse-wala  { animation: pulse-wala 2s infinite; }
        .winner-pop  { animation: winner-pop 0.6s cubic-bezier(.36,.07,.19,.97) forwards; }
        .fade-in     { animation: fade-in 0.4s ease forwards; }

        .meron-side { background: linear-gradient(135deg, #1a0000, #2d0000); border: 1px solid rgba(239,68,68,0.3); }
        .wala-side  { background: linear-gradient(135deg, #00001a, #00002d); border: 1px solid rgba(59,130,246,0.3); }
    </style>
</head>
<body class="min-h-screen flex flex-col text-white">

    {{-- HEADER --}}
    <header class="flex justify-center items-center px-10 py-6 border-b border-white/10 relative">
        <div class="flex items-center gap-4 text-center">
            <span class="text-5xl">🐓</span>
            <span class="text-white font-bold text-6xl tracking-wide">{{ $displayTitle ?? 'Sabong Betting System' }}</span>
        </div>
        <div id="clock" class="text-gray-400 text-2xl font-mono absolute right-10"></div>
    </header>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col items-center justify-center px-6 py-8 gap-8">

        {{-- FIGHT NUMBER + STATUS --}}
        <div class="text-center">
            <p id="fight-number" class="text-gray-400 text-4xl uppercase tracking-widest mb-4 font-bold">
                {{ $fight ? 'Fight #' . $fight->fight_number : 'No Active Fight' }}
            </p>
            <div id="status-badge" class="inline-block px-10 py-4 rounded-full text-2xl font-bold
                @if($fight)
                    @if($fight->status === 'open') bg-green-900 text-green-400 border border-green-700 text-2xl font-bold
                    @elseif($fight->status === 'closed') bg-yellow-900 text-yellow-400 border border-yellow-700 text-2xl font-bold
                    @else bg-gray-800 text-gray-400 border border-gray-600 text-2xl font-bold
                    @endif
                @else
                    bg-gray-800 text-gray-400 border border-gray-600
                @endif
            ">
                {{ $fight ? ucfirst($fight->status) : 'Waiting...' }}
            </div>
        </div>

        {{-- WINNER OVERLAY --}}
        <div id="winner-overlay" class="hidden w-full max-w-6xl">
            <div class="rounded-2xl p-16 text-center border min-h-96" id="winner-card">
                <p id="winner-label" class="text-gray-400 text-5xl uppercase tracking-widest mb-4">Winner</p>
                <p id="winner-text" class="text-[10rem] leading-[1.2] font-black uppercase tracking-wider mb-4 break-words"></p>
                <p id="fight-closed-text" class="text-gray-400 text-5xl">Fight closed</p>
            </div>
        </div>

        {{-- MERON VS WALA --}}
        <div id="betting-panel" class="w-full grid grid-cols-2 gap-8
            {{ $fight && $fight->winner ? 'hidden' : '' }}">

            {{-- MERON --}}
            <div id="meron-side" class="meron-side rounded-3xl p-20 flex flex-col items-center gap-8 pulse-meron">
                <p class="text-red-400 text-5xl uppercase tracking-widest font-bold">Meron</p>
                <p id="meron-status-label" style="display: none;" class="text-red-400 text-4xl uppercase tracking-widest font-bold">CLOSED</p>
                <p id="meron-total"
                    class="text-8xl font-black text-red-400 tabular-nums leading-none">
                    ₱{{ $fight ? number_format($fight->meronTotal(), 2) : '0.00' }}
                </p>
                <div class="w-full h-1 bg-red-900/50 mt-6"></div>
                <p id="meron-multiplier" class="text-red-300 text-6xl font-bold tabular-nums">
                    @php
                        $meronTotal = $fight ? $fight->meronTotal() : 0;
                        $walaTotal = $fight ? $fight->walaTotal() : 0;
                        $totalPool = $meronTotal + $walaTotal;
                        $netPool = $totalPool * 0.95;
                        $meronMultiplier = $meronTotal > 0 ? ($netPool / $meronTotal * 100) : 100;
                    @endphp
                    {{ number_format($meronMultiplier, 2) }}%
                </p>
                <p class="text-red-900 font-bold text-3xl">Payout Percentage</p>
            </div>

            {{-- WALA --}}
            <div id="wala-side" class="wala-side rounded-3xl p-20 flex flex-col items-center gap-8 pulse-wala">
                <p class="text-blue-400 text-5xl uppercase tracking-widest font-bold">Wala</p>
                <p id="wala-status-label" style="display: none;" class="text-blue-400 text-4xl uppercase tracking-widest font-bold">CLOSED</p>
                <p id="wala-total"
                    class="text-8xl font-black text-blue-400 tabular-nums leading-none">
                    ₱{{ $fight ? number_format($fight->walaTotal(), 2) : '0.00' }}
                </p>
                <div class="w-full h-1 bg-blue-900/50 mt-6"></div>
                <p id="wala-multiplier" class="text-blue-300 text-6xl font-bold tabular-nums">
                    @php
                        $meronTotal = $fight ? $fight->meronTotal() : 0;
                        $walaTotal = $fight ? $fight->walaTotal() : 0;
                        $totalPool = $meronTotal + $walaTotal;
                        $netPool = $totalPool * 0.95;
                        $walaMultiplier = $walaTotal > 0 ? ($netPool / $walaTotal * 100) : 100;
                    @endphp
                    {{ number_format($walaMultiplier, 2) }}%
                </p>
                <p class="text-blue-900 font-bold text-3xl">Payout Percentage</p>
            </div>

        </div>

    </main>

    <script>
        // ── Clock ────────────────────────────────────────────
        function updateClock() {
            document.getElementById('clock').textContent =
                new Date().toLocaleTimeString('en-PH', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // ── Helpers ──────────────────────────────────────────
        function formatMoney(amount) {
            return '₱' + parseFloat(amount).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function showWinner(winner, fightNumber) {
            const overlay  = document.getElementById('winner-overlay');
            const panel    = document.getElementById('betting-panel');
            const card     = document.getElementById('winner-card');
            const text     = document.getElementById('winner-text');
            const label    = document.getElementById('winner-label');
            const closedText = document.getElementById('fight-closed-text');

            panel.classList.add('hidden');
            overlay.classList.remove('hidden');
            overlay.classList.add('winner-pop');

            const isMeron = winner === 'meron';
            const isDraw  = winner === 'draw';
            const isWala  = winner === 'wala';

            text.textContent = winner.toUpperCase();

            if (isMeron) {
                card.className = 'rounded-2xl p-16 text-center border border-red-500 bg-red-950/60';
                text.className = 'text-[10rem] leading-[1.2] font-black uppercase tracking-wider mb-4 text-red-400';
            } else if (isWala) {
                card.className = 'rounded-2xl p-16 text-center border border-blue-500 bg-blue-950/60';
                text.className = 'text-[10rem] leading-[1.2] font-black uppercase tracking-wider mb-4 text-blue-400';
            } else if (!isDraw) {
                card.className = 'rounded-2xl p-16 text-center border border-blue-500 bg-blue-950/60';
                text.className = 'text-[10rem] leading-[1.2] font-black uppercase tracking-wider mb-4 text-blue-400';
                closedText.classList.add('hidden');
            } else {
                card.className = 'rounded-2xl p-16 text-center border border-gray-500 bg-gray-900';
                text.className = 'text-[10rem] leading-[1.2] font-black uppercase tracking-wider mb-2  text-gray-400';
                closedText.classList.add('hidden');
            }
        }

        function updateStatus(status) {
            const badge = document.getElementById('status-badge');
            const configs = {
                open:      { text: 'Open',      cls: 'bg-green-900 text-green-400 border border-green-700' },
                closed:    { text: 'Closed',     cls: 'bg-yellow-900 text-yellow-400 border border-yellow-700' },
                pending:   { text: 'Pending',    cls: 'bg-gray-800 text-gray-400 border border-gray-600' },
                done:      { text: 'Done',       cls: 'bg-gray-800 text-gray-400 border border-gray-600' },
                cancelled: { text: 'Cancelled',  cls: 'bg-red-900 text-red-400 border border-red-700' },
            };
            const config = configs[status] ?? configs.pending;
            badge.textContent = config.text;
            badge.className   = 'inline-block px-10 py-4 rounded-full text-2xl font-bold ' + config.cls;
        }

        // ── WebSocket ────────────────────────────────────────
        window.Echo.connector.pusher.connection.bind('connected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Live';
            s.className = 'text-xs px-2 py-0.5 rounded-full bg-green-900 text-green-400';
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Disconnected';
            s.className = 'text-xs px-2 py-0.5 rounded-full bg-red-900 text-red-400';
        });

        window.Echo.channel('fights')
            .listen('.fight.updated', (data) => {
                document.getElementById('fight-number').textContent =
                    'Fight #' + data.fight_number;
                updateStatus(data.status);

                // reset winner overlay if new fight opened
                if (data.status === 'open' || data.status === 'pending') {
                    document.getElementById('winner-overlay').classList.add('hidden');
                    document.getElementById('betting-panel').classList.remove('hidden');
                    document.getElementById('meron-total').textContent =
                        formatMoney(data.meron_total);
                    document.getElementById('wala-total').textContent =
                        formatMoney(data.wala_total);
                }
            })
            .listen('.bet.placed', (data) => {
                const meronTotal = parseFloat(data.meron_total ?? 0);
                const walaTotal  = parseFloat(data.wala_total ?? 0);
                const totalPool  = meronTotal + walaTotal;
                const netPool    = totalPool * 0.95;

                document.getElementById('meron-total').textContent = formatMoney(meronTotal);
                document.getElementById('wala-total').textContent  = formatMoney(walaTotal);

                // update multipliers
                document.getElementById('meron-multiplier').textContent =
                    meronTotal > 0 ? (netPool / meronTotal * 100).toFixed(2) + '%' : '100.00%';
                document.getElementById('wala-multiplier').textContent =
                    walaTotal > 0 ? (netPool / walaTotal * 100).toFixed(2) + '%' : '100.00%';

                addFeedItem(data.side, data.amount, data.teller);
            })
            .listen('.winner.declared', (data) => {
                updateStatus('done');
                showWinner(data.winner, data.fight_number);
            });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    // wait for Echo to be available
    function waitForEcho(callback) {
        if (typeof window.Echo !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForEcho(callback), 100);
        }
    }

    waitForEcho(function() {

        // ── WebSocket status ──────────────────────────
        window.Echo.connector.pusher.connection.bind('connected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Live';
            s.className   = 'text-xs px-2 py-0.5 rounded-full bg-green-900 text-green-400';
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            const s = document.getElementById('ws-status');
            s.textContent = '● Disconnected';
            s.className   = 'text-xs px-2 py-0.5 rounded-full bg-red-900 text-red-400';
        });

        window.Echo.connector.pusher.connection.bind('state_change', (states) => {
            console.log('WS state:', states.previous, '->', states.current);
        });

        window.Echo.connector.pusher.connection.bind('error', (err) => {
            console.error('WS error:', err);
        });

        // ── Listen for events ─────────────────────────
        window.Echo.channel('fights')
            .listen('.fight.updated', (data) => {
                document.getElementById('fight-number').textContent =
                    'Fight #' + data.fight_number;
                updateStatus(data.status);

                if (data.status === 'open' || data.status === 'pending') {
                    document.getElementById('winner-overlay').classList.add('hidden');
                    document.getElementById('betting-panel').classList.remove('hidden');
                    document.getElementById('meron-total').textContent =
                        formatMoney(data.meron_total);
                    document.getElementById('wala-total').textContent =
                        formatMoney(data.wala_total);

                    const total   = parseFloat(data.meron_total) + parseFloat(data.wala_total);
                    const netPool = total * 0.95;
                    const meron   = parseFloat(data.meron_total);
                    const wala    = parseFloat(data.wala_total);

                    document.getElementById('meron-multiplier').textContent =
                        meron > 0 ? (netPool / meron * 100).toFixed(2) + '%' : '—';
                    document.getElementById('wala-multiplier').textContent =
                        wala > 0 ? (netPool / wala * 100).toFixed(2) + '%' : '—';
                }
            })
            .listen('.bet.placed', (data) => {
                const meronTotal = parseFloat(data.meron_total ?? 0);
                const walaTotal  = parseFloat(data.wala_total ?? 0);
                const totalPool  = meronTotal + walaTotal;
                const netPool    = totalPool * 0.95;

                document.getElementById('meron-total').textContent = formatMoney(meronTotal);
                document.getElementById('wala-total').textContent  = formatMoney(walaTotal);

                document.getElementById('meron-multiplier').textContent =
                    meronTotal > 0 ? (netPool / meronTotal * 100).toFixed(2) + '%' : '—';
                document.getElementById('wala-multiplier').textContent =
                    walaTotal > 0 ? (netPool / walaTotal * 100).toFixed(2) + '%' : '—';
            })
            .listen('.winner.declared', (data) => {
                updateStatus('done');
                showWinner(data.winner, data.fight_number);
            });

        function updateSideStatus(meron_status, wala_status) {
            const meronSide = document.getElementById('meron-side');
            const walaSide = document.getElementById('wala-side');
            const meronLabel = document.getElementById('meron-status-label');
            const walaLabel = document.getElementById('wala-status-label');

            if (meron_status === 'closed') {
                meronSide.classList.add('opacity-50');
                meronSide.classList.remove('pulse-meron');
                meronLabel.style.display = 'block';
            } else {
                meronSide.classList.remove('opacity-50');
                meronSide.classList.add('pulse-meron');
                meronLabel.style.display = 'none';
            }

            if (wala_status === 'closed') {
                walaSide.classList.add('opacity-50');
                walaSide.classList.remove('pulse-wala');
                walaLabel.style.display = 'block';
            } else {
                walaSide.classList.remove('opacity-50');
                walaSide.classList.add('pulse-wala');
                walaLabel.style.display = 'none';
            }
        }

        // ── Listen for settings updates ───────────────
        window.Echo.channel('settings')
            .listen('.setting.updated', (data) => {
                document.querySelector('header span.text-white.font-bold').textContent = data.display_title;
            });

        // ── Listen for side status updates ───────────
        window.Echo.channel('fights')
            .listen('.side.status.updated', (data) => {
                updateSideStatus(data.meron_status, data.wala_status);
            });

    }); // end waitForEcho

}); // end DOMContentLoaded
</script>

</body>
</html>
