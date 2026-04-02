<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorPNG;

class QrGenerator
{
    /**
     * Generate a base64 QR code using SVG (no imagick needed)
     */
    public static function generateQr(string $reference): string
    {
        $svg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($reference);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate a base64 barcode (Code 128) using GD
     */
    public static function generateBarcode(string $reference): string
    {
        $generator = new BarcodeGeneratorPNG();
        $barcode   = $generator->getBarcode(
            $reference,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            3,
            80
        );

        return 'data:image/png;base64,' . base64_encode($barcode);
    }
}
