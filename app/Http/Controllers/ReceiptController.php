<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Services\QrGenerator;

class ReceiptController extends Controller
{
    public function show(string $reference)
    {
        $bet = Bet::with(['fight', 'teller'])
            ->where('reference', $reference)
            ->firstOrFail();

        $barcode = QrGenerator::generateBarcode($bet->reference);
        $qr      = QrGenerator::generateQr($bet->reference);

        return view('receipt', compact('bet', 'barcode', 'qr'));
    }
}
