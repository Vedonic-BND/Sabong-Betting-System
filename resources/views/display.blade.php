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
    <header class="flex justify-between items-center px-10 py-4 border-b border-white/10">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🐓</span>
            <span class="text-white font-bold text-lg tracking-wide">Sabong Betting System</span>
        </div>
        <div id="clock" class="text-gray-400 text-sm font-mono"></div>
    </header>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col items-center justify-center px-6 py-8 gap-8">

        {{-- FIGHT NUMBER + STATUS --}}
        <div class="text-center">
            <p id="fight-number" class="text-gray-400 text-sm uppercase tracking-widest mb-1">
                {{ $fight ? 'Fight #' . $fight->fight_number : 'No Active Fight' }}
            </p>
            <div id="status-badge" class="inline-block px-5 py-1.5 rounded-full text-sm font-semibold
                @if($fight)
                    @if($fight->status === 'open') bg-green-900 text-green-400 border border-green-700
                    @elseif($fight->status === 'closed') bg-yellow-900 text-yellow-400 border border-yellow-700
                    @else bg-gray-800 text-gray-400 border border-gray-600
                    @endif
                @else
                    bg-gray-800 text-gray-400 border border-gray-600
                @endif
            ">
                {{ $fight ? ucfirst($fight->status) : 'Waiting...' }}
            </div>
        </div>

        {{-- WINNER OVERLAY --}}
        <div id="winner-overlay" class="hidden w-full max-w-2xl">
            <div class="rounded-2xl p-8 text-center border"
                id="winner-card">
                <p class="text-gray-400 text-sm uppercase tracking-widest mb-2">Winner</p>
                <p id="winner-text" class="text-6xl font-black uppercase tracking-wider mb-2"></p>
                <p class="text-gray-400 text-sm">Fight closed</p>
            </div>
        </div>

        {{-- MERON VS WALA --}}
        <div id="betting-panel" class="w-full max-w-4xl grid grid-cols-2 gap-4
            {{ $fight && $fight->winner ? 'hidden' : '' }}">

            {{-- MERON --}}
            <div class="meron-side rounded-2xl p-8 flex flex-col items-center gap-3 pulse-meron">
                <p class="text-red-400 text-xs uppercase tracking-widest font-semibold">Meron</p>
                <p id="meron-total"
                    class="text-5xl font-black text-red-400 tabular-nums">
                    ₱{{ $fight ? number_format($fight->meronTotal(), 2) : '0.00' }}
                </p>
                <div class="w-full h-px bg-red-900/50 mt-2"></div>
                <p id="meron-multiplier" class="text-red-300 text-2xl font-bold tabular-nums">
                    @if($fight && $fight->meronTotal() > 0)
                        {{ number_format((($fight->meronTotal() + $fight->walaTotal()) * 0.95) / $fight->meronTotal() * 100, 2) }}%
                    @else
                        —
                    @endif
                </p>
                <p class="text-red-900 text-xs">Multiplier</p>
            </div>

            {{-- WALA --}}
            <div class="wala-side rounded-2xl p-8 flex flex-col items-center gap-3 pulse-wala">
                <p class="text-blue-400 text-xs uppercase tracking-widest font-semibold">Wala</p>
                <p id="wala-total"
                    class="text-5xl font-black text-blue-400 tabular-nums">
                    ₱{{ $fight ? number_format($fight->walaTotal(), 2) : '0.00' }}
                </p>
                <div class="w-full h-px bg-blue-900/50 mt-2"></div>
                <p id="wala-multiplier" class="text-blue-300 text-2xl font-bold tabular-nums">
                    @if($fight && $fight->walaTotal() > 0)
                        {{ number_format((($fight->meronTotal() + $fight->walaTotal()) * 0.95) / $fight->walaTotal() * 100, 2) }}%
                    @else
                        —
                    @endif
                </p>
                <p class="text-blue-900 text-xs">Multiplier</p>
            </div>

        </div>

        {{-- LIVE BET FEED --}}
        <div class="w-full max-w-4xl">
            <div class="flex justify-between items-center mb-3">
                <p class="text-gray-500 text-xs uppercase tracking-widest">Live Bets</p>
                <span id="ws-status"
                    class="text-xs px-2 py-0.5 rounded-full bg-gray-800 text-gray-500">
                    Connecting...
                </span>
            </div>
            <ul id="live-feed"
                class="space-y-2 max-h-48 overflow-y-auto">
                <li class="text-gray-600 text-sm text-center py-4">
                    Waiting for bets...
                </li>
            </ul>
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

        function addFeedItem(side, amount, teller) {
            const feed = document.getElementById('live-feed');

            // remove placeholder
            const placeholder = feed.querySelector('.text-center');
            if (placeholder) placeholder.remove();

            const isMeron = side === 'meron';
            const li = document.createElement('li');
            li.className = 'fade-in flex justify-between items-center px-4 py-2 rounded-lg ' +
                (isMeron ? 'bg-red-950/60 border border-red-900/30' : 'bg-blue-950/60 border border-blue-900/30');
            li.innerHTML = `
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold uppercase px-2 py-0.5 rounded
                        ${isMeron ? 'bg-red-900 text-red-300' : 'bg-blue-900 text-blue-300'}">
                        ${side}
                    </span>
                    <span class="text-gray-400 text-xs">${teller}</span>
                </div>
                <span class="font-bold tabular-nums ${isMeron ? 'text-red-400' : 'text-blue-400'}">
                    ${formatMoney(amount)}
                </span>
            `;

            feed.prepend(li);

            // keep max 6 items
            while (feed.children.length > 6) {
                feed.removeChild(feed.lastChild);
            }
        }

        function showWinner(winner, fightNumber) {
            const overlay  = document.getElementById('winner-overlay');
            const panel    = document.getElementById('betting-panel');
            const card     = document.getElementById('winner-card');
            const text     = document.getElementById('winner-text');

            panel.classList.add('hidden');
            overlay.classList.remove('hidden');
            overlay.classList.add('winner-pop');

            const isMeron = winner === 'meron';
            const isDraw  = winner === 'draw';

            text.textContent = winner.toUpperCase();

            if (isMeron) {
                card.className = 'rounded-2xl p-8 text-center border border-red-500 bg-red-950/60';
                text.className = 'text-6xl font-black uppercase tracking-wider mb-2 text-red-400';
            } else if (!isDraw) {
                card.className = 'rounded-2xl p-8 text-center border border-blue-500 bg-blue-950/60';
                text.className = 'text-6xl font-black uppercase tracking-wider mb-2 text-blue-400';
            } else {
                card.className = 'rounded-2xl p-8 text-center border border-gray-500 bg-gray-900';
                text.className = 'text-6xl font-black uppercase tracking-wider mb-2 text-gray-400';
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
            badge.className   = 'inline-block px-5 py-1.5 rounded-full text-sm font-semibold ' + config.cls;
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
                    meronTotal > 0 ? (netPool / meronTotal * 100).toFixed(2) + '%' : '—';
                document.getElementById('wala-multiplier').textContent =
                    walaTotal > 0 ? (netPool / walaTotal * 100).toFixed(2) + '%' : '—';

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

                addFeedItem(data.side, data.amount, data.teller);
            })
            .listen('.winner.declared', (data) => {
                updateStatus('done');
                showWinner(data.winner, data.fight_number);
            });

    }); // end waitForEcho

}); // end DOMContentLoaded
</script>

</body>
</html>
