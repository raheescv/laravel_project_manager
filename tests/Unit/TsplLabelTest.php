<?php

use App\Support\TsplLabel;

/**
 * TSPL for TSC label printers: each rendered label becomes a 1-bit bitmap at
 * 203 dpi, and a run of identical labels prints as copies of one bitmap.
 */

/**
 * A screenshot of 10 mm wide labels stacked the way RendersTsplLabels lays them
 * out, one whole-pixel row each. `true` paints a black block over the left half
 * of that label, clear of its top and bottom edges the way real label content is.
 */
function stackedLabelScreenshot(array $darkLeftHalf, float $heightMm = 5): string
{
    $pixelsPerMm = 96 / 25.4 * TsplLabel::RENDER_SCALE;
    $row = TsplLabel::rowPixels($heightMm) * TsplLabel::RENDER_SCALE;
    $width = (int) ceil(10 * $pixelsPerMm);
    $image = imagecreatetruecolor($width, $row * count($darkLeftHalf));
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

    foreach ($darkLeftHalf as $i => $dark) {
        if ($dark) {
            $top = $i * $row + (int) round(0.2 * $heightMm * $pixelsPerMm);
            $bottom = $i * $row + (int) round(0.8 * $heightMm * $pixelsPerMm);
            imagefilledrectangle($image, 0, $top, intdiv($width, 2) - 1, $bottom, imagecolorallocate($image, 0, 0, 0));
        }
    }

    ob_start();
    imagepng($image);

    return ob_get_clean();
}

/** A middle bitmap row of the first label (10 bytes for a 10 mm = 80 dot label). */
function middleBitmapRow(string $tspl): string
{
    $start = strpos($tspl, 'BITMAP 0,0,10,40,0,') + strlen('BITMAP 0,0,10,40,0,');

    return substr($tspl, $start + 20 * 10, 10);
}

it('sends the exact stock size and one bitmap per distinct label', function (): void {
    $tspl = TsplLabel::fromScreenshot(stackedLabelScreenshot([true, false]), 10, 5);

    expect($tspl)->toStartWith("SIZE 10 mm,5 mm\r\nDIRECTION 0\r\nSHIFT 0\r\nREFERENCE 0,0\r\nCLS\r\n")
        ->and(substr_count($tspl, 'BITMAP 0,0,10,40,0,'))->toBe(2)
        ->and(substr_count($tspl, "PRINT 1,1\r\n"))->toBe(2);
});

it('gives every label a whole-pixel row, never a pixel more than it needs', function (): void {
    // 13 mm is 49.13 CSS pixels; 19 pixels expressed in mm comes back as 19.0000001.
    expect(TsplLabel::rowPixels(13))->toBe(50)
        ->and(TsplLabel::rowPixels(19 * 25.4 / 96))->toBe(19);
});

it('prints the second label onwards exactly like the first, as copies of one bitmap', function (): void {
    // A 13 mm tag is not a whole pixel tall, so slicing on the label height put tag 2 and 3 a fraction of a pixel off.
    $tspl = TsplLabel::fromScreenshot(stackedLabelScreenshot([true, true, true], 13), 10, 13);

    expect(substr_count($tspl, 'BITMAP '))->toBe(1)
        ->and($tspl)->toEndWith("PRINT 1,3\r\n");
});

it('prints a dot for a 0 bit, or for a 1 bit when inverted', function (): void {
    $row = middleBitmapRow(TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5));
    $inverted = middleBitmapRow(TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5, ['invert' => true]));

    expect(bin2hex($row[0]))->toBe('00')
        ->and(bin2hex($row[9]))->toBe('ff')
        ->and(bin2hex($inverted[0]))->toBe('ff')
        ->and(bin2hex($inverted[9]))->toBe('00');
});

it('nudges the label by the offsets', function (): void {
    // 2 mm right is 16 dots: the first two bytes of the row turn blank.
    $row = middleBitmapRow(TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5, ['offset_x' => 2]));

    expect(bin2hex(substr($row, 0, 3)))->toBe('ffff00');
});

it('moves the print along the roll with SHIFT, the other way round when flipped', function (): void {
    // 5 mm is 40 dots at 203 dpi. The bitmap itself is untouched, so nothing is cropped.
    $plain = TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5);
    $down = TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5, ['offset_y' => 5]);
    $flipped = TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5, ['offset_y' => 5, 'flip' => true]);

    expect($down)->toContain("DIRECTION 0\r\nSHIFT 40\r\n")
        ->and($flipped)->toContain("DIRECTION 1\r\nSHIFT -40\r\n")
        ->and(middleBitmapRow($down))->toBe(middleBitmapRow($plain));
});

it('sets the gap and flips the print when asked', function (): void {
    $tspl = TsplLabel::fromScreenshot(stackedLabelScreenshot([true]), 10, 5, ['gap' => 2.5, 'flip' => true]);

    expect($tspl)->toStartWith("SIZE 10 mm,5 mm\r\nGAP 2.5 mm,0 mm\r\nDIRECTION 1\r\n");
});
