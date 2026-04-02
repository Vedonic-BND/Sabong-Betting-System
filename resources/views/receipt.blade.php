<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $bet->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 4mm;
            background: white;
            color: black;
        }

        .center   { text-align: center; }
        .bold     { font-weight: bold; }
        .large    { font-size: 16px; }
        .xlarge   { font-size: 20px; }
        .divider  { border-top: 1px dashed #000; margin: 4mm 0; }
        .row      { display: flex; justify-content: space-between; margin: 1.5mm 0; }
        .label    { color: #555; }
        .value    { font-weight: bold; text-align: right; }

        .side-meron { color: #cc0000; font-size: 18px; font-weight: bold; }
        .side-wala  { color: #0000cc; font-size: 18px; font-weight: bold; }

        .barcode-img {
            width: 100%;
            max-width: 72mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .qr-img {
            width: 40mm;
            height: 40mm;
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
        <p class="bold xlarge">🐓 SABONG</p>
        <p class="bold large">BETTING SYSTEM</p>
        <p style="font-size:10px; color:#555; margin-top:1mm;">Official Bet Receipt</p>
    </div>

    <div class="divider"></div>

    {{-- FIGHT INFO --}}
    <div class="row">
        <span class="label">Fight #</span>
        <span class="value">{{ $bet->fight->fight_number }}</span>
    </div>

    <div class="row">
        <span class="label">Side</span>
        <span class="value {{ $bet->side === 'meron' ? 'side-meron' : 'side-wala' }}">
            {{ strtoupper($bet->side) }}
        </span>
    </div>

    <div class="row">
        <span class="label">Bet Amount</span>
        <span class="value" style="font-size:16px;">
            ₱{{ number_format($bet->amount, 2) }}
        </span>
    </div>

    <div class="divider"></div>

    {{-- REFERENCE --}}
    <div class="row">
        <span class="label">Reference #</span>
        <span class="value">{{ $bet->reference }}</span>
    </div>

    <div class="row">
        <span class="label">Teller</span>
        <span class="value">{{ $bet->teller->name }}</span>
    </div>

    <div class="row">
        <span class="label">Date</span>
        <span class="value">
            {{ \Carbon\Carbon::parse($bet->created_at)->format('M d, Y') }}
        </span>
    </div>

    <div class="row">
        <span class="label">Time</span>
        <span class="value">
            {{ \Carbon\Carbon::parse($bet->created_at)->format('h:i A') }}
        </span>
    </div>

    <div class="divider"></div>

    {{-- BARCODE --}}
    <div class="center" style="margin: 3mm 0;">
        <img src="{{ $barcode }}" class="barcode-img" alt="barcode" />
        <p style="font-size: 11px; margin-top: 1mm; letter-spacing: 2px;">
            {{ $bet->reference }}
        </p>
    </div>

    <div class="divider"></div>

    {{-- QR CODE --}}
    <div class="center" style="margin: 3mm 0;">
        <p style="font-size: 10px; color: #555; margin-bottom: 2mm;">Scan to verify</p>
        <img src="{{ $qr }}" class="qr-img" alt="qr code" />
    </div>

    <div class="divider"></div>

    {{-- FOOTER --}}
    <div class="center" style="font-size: 10px; color: #555; margin-top: 2mm;">
        <p>Keep this receipt.</p>
        <p>Present upon claiming payout.</p>
    </div>

    {{-- AUTO PRINT --}}
    <script>
        window.onload = function () {
            window.print();
        };
    </script>

</body>
</html>
