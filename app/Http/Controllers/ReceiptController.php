<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Setting;
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

        // Get the display title from settings
        $settings = Setting::first();
        $displayTitle = $settings ? $settings->display_title : 'SABONG BETTING SYSTEM';

        return view('receipt', compact('bet', 'barcode', 'qr', 'displayTitle'));
    }

    public function payout(string $reference)
    {
        $bet = Bet::with(['fight', 'teller', 'payout'])
            ->where('reference', $reference)
            ->firstOrFail();

        if (!$bet->payout) {
            abort(404, 'Payout not yet calculated.');
        }

        // Ensure fight exists before accessing it
        if (!$bet->fight) {
            abort(404, 'Associated fight not found.');
        }

        // Only winners can view payout receipt
        if ($bet->side !== $bet->fight->winner) {
            abort(404, 'No payout available for this bet.');
        }

        // Get the display title from settings
        $settings = Setting::first();
        $displayTitle = $settings ? $settings->display_title : 'SABONG BETTING SYSTEM';

        return view('payout-receipt', compact('bet', 'displayTitle'));
    }
}
