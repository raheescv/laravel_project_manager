<?php

use App\Support\EscPosReceipt;

/**
 * ESC/POS for thermal receipt printers: the rendered receipt is scaled to the
 * printer's dot width and sent as 1-bit raster bands, then the paper is cut.
 */

/** A white receipt screenshot whose top-left quarter is black. */
function receiptScreenshot(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, intdiv($height, 2) - 1, imagecolorallocate($image, 0, 0, 0));

    ob_start();
    imagepng($image);

    return ob_get_clean();
}

/** GS v 0 header for a band: width in bytes, height in dots. */
function rasterHeader(int $widthBytes, int $rows): string
{
    return bin2hex("\x1D\x76\x30\x00".pack('v', $widthBytes).pack('v', $rows));
}

it('resets the printer, rasters the receipt in bands, then cuts', function (): void {
    $escPos = bin2hex(EscPosReceipt::fromScreenshot(receiptScreenshot(576, 600)));

    expect($escPos)->toStartWith('1b40'.rasterHeader(72, 256))
        ->and(substr_count($escPos, rasterHeader(72, 256)))->toBe(2)
        ->and($escPos)->toContain(rasterHeader(72, 88))
        ->and($escPos)->toEndWith('1d564200');
});

it('scales the receipt to the printer width', function (): void {
    $escPos = bin2hex(EscPosReceipt::fromScreenshot(receiptScreenshot(1152, 100), 576));

    expect($escPos)->toStartWith('1b40'.rasterHeader(72, 50));
});

it('prints a dot for a 1 bit', function (): void {
    $escPos = EscPosReceipt::fromScreenshot(receiptScreenshot(576, 16));
    $firstRow = substr($escPos, 2 + 8, 72);

    expect(bin2hex($firstRow[0]))->toBe('ff')
        ->and(bin2hex($firstRow[71]))->toBe('00');
});

it('opens the drawer and feeds instead of cutting when asked', function (): void {
    $escPos = bin2hex(EscPosReceipt::fromScreenshot(receiptScreenshot(384, 40), 384, ['drawer' => true, 'cut' => false]));

    expect($escPos)->toStartWith('1b40'.'1b700019fa'.rasterHeader(48, 40))
        ->and($escPos)->toEndWith('1b6404');
});
