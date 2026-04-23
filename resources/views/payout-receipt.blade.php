<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Receipt {{ $bet->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            width: 82.5mm;
            max-height: 82.5mm;
            margin: 0 auto;
            padding: 3mm;
            background: white;
            color: black;
            overflow: hidden;
        }

        .center   { text-align: center; }
        .bold     { font-weight: bold; }
        .large    { font-size: 14px; }
        .xlarge   { font-size: 16px; }
        .divider  { border-top: 1px dashed #000; margin: 2mm 0; }
        .row      { display: flex; justify-content: space-between; margin: 1mm 0; }
        .label    { color: #555; font-size: 10px; }
        .value    { font-weight: bold; text-align: right; font-size: 10px; }

        .won { color: #22cc00; font-size: 12px; font-weight: bold; }
        .lost { color: #cc0000; font-size: 12px; font-weight: bold; }

        .barcode-img {
            width: 100%;
            max-width: 76.5mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        @media print {
            body { margin: 0; padding: 2mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="center">
        <p class="bold xlarge">🐓 {{ strtoupper($displayTitle ?? 'SABONG BETTING SYSTEM') }}</p>
        <p style="font-size:9px; color:#555; margin-top:0.5mm;">Official Payout Receipt</p>
    </div>

    <div class="divider"></div>

    {{-- FIGHT INFO --}}
    <div class="row">
        <span class="label">Fight #</span>
        <span class="value">{{ $bet->fight->fight_number }}</span>
    </div>

    <div class="row">
        <span class="label">Your Side</span>
        <span class="value" style="color: {{ $bet->side === 'meron' ? '#cc0000' : '#0000cc' }};">
            {{ strtoupper($bet->side) }}
        </span>
    </div>

    <div class="row">
        <span class="label">Winner</span>
        <span class="value">{{ strtoupper($bet->fight->winner) }}</span>
    </div>

    <div class="divider"></div>

    {{-- PAYOUT INFO --}}
    <div class="row">
        <span class="label">Result</span>
        <span class="won">YOU WON!</span>
    </div>

    <div class="row">
        <span class="label">Bet Amount</span>
        <span class="value">₱{{ number_format($bet->amount, 2) }}</span>
    </div>

    <div class="row">
        <span class="label">Gross Payout</span>
        <span class="value">₱{{ number_format($bet->payout->gross_payout, 2) }}</span>
    </div>

    <div class="row">
        <span class="label">Multiplier</span>
        <span class="value">
            @php
                $multiplier = $bet->payout->winning_side_multiplier;
                if (!$multiplier) {
                    // Fallback: calculate multiplier if not stored
                    $meronTotal = $bet->fight->meronTotal();
                    $walaTotal = $bet->fight->walaTotal();
                    $totalPool = $meronTotal + $walaTotal;
                    $netPool = $totalPool * 0.95;
                    $winningSideTotal = $bet->fight->winner === 'meron' ? $meronTotal : $walaTotal;
                    $multiplier = $winningSideTotal > 0 ? ($netPool / $winningSideTotal) * 100 : 0;
                }
            @endphp
            {{ number_format($multiplier, 2) }}x
        </span>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span class="label bold" style="font-size: 12px;">NET PAYOUT</span>
        <span class="value bold" style="font-size: 14px; color: #22cc00;">
            ₱{{ number_format($bet->payout->net_payout, 2) }}
        </span>
    </div>

    <div class="divider"></div>

    {{-- REFERENCE --}}
    <div class="row">
        <span class="label">Reference #</span>
        <span class="value">{{ $bet->reference }}</span>
    </div>

    <div class="row">
        <span class="label">Date</span>
        <span class="value">
            {{ \Carbon\Carbon::parse($bet->created_at)->format('M d, Y h:i A') }}
        </span>
    </div>

    <div class="divider"></div>

    {{-- FOOTER --}}
    <div class="center" style="font-size: 9px; color: #555; margin-top: 1mm;">
        <p>Payout Receipt</p>
        <p>Status: <strong>{{ ucfirst($bet->payout->status) }}</strong></p>
    </div>

    {{-- AUTO PRINT --}}
    <script>
        window.onload = function () {
            window.print();
        };
    </script>

</body>
</html>
