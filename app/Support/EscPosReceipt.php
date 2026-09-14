<?php

namespace App\Support;

use GdImage;
use RuntimeException;

/**
 * Turns a rendered receipt into ESC/POS, the command set of Epson and most other
 * thermal receipt printers: the page is scaled to the printer's dot width, sent
 * as a 1-bit raster, then the paper is cut.
 */
class EscPosReceipt
{
    /** Browsershot only takes a whole device scale, so the receipt renders above print resolution and is resampled down. */
    public const RENDER_SCALE = 3;

    /** Printable dots per line: 80 mm paper at 203 dpi, 80 mm at 180 dpi, and 58 mm paper. */
    public const WIDTHS = [576, 512, 384];

    /** Rows per raster command, well inside any receipt printer's input buffer. */
    protected const BAND = 256;

    /**
     * @param  array{cut?: bool, drawer?: bool}  $options
     */
    public static function fromScreenshot(string $png, int $dots = 576, array $options = []): string
    {
        $page = @imagecreatefromstring($png);

        if (! $page) {
            throw new RuntimeException('The rendered receipt image could not be read.');
        }

        $height = max(1, (int) round(imagesy($page) * $dots / imagesx($page)));
        $receipt = imagecreatetruecolor($dots, $height);
        imagefill($receipt, 0, 0, imagecolorallocate($receipt, 255, 255, 255));
        imagecopyresampled($receipt, $page, 0, 0, 0, 0, $dots, $height, imagesx($page), imagesy($page));

        // ESC @ resets the printer; ESC p pulses the cash drawer on pin 2.
        $commands = "\x1B\x40";
        if (! empty($options['drawer'])) {
            $commands .= "\x1B\x70\x00\x19\xFA";
        }

        // GS v 0 prints a raster band: width in bytes, height in dots, both little-endian.
        $widthBytes = intdiv($dots + 7, 8);
        for ($top = 0; $top < $height; $top += self::BAND) {
            $rows = min(self::BAND, $height - $top);
            $commands .= "\x1D\x76\x30\x00".pack('v', $widthBytes).pack('v', $rows).self::raster($receipt, $top, $rows, $dots);
        }

        // GS V 66 feeds to the cutter and cuts; without a cutter, ESC d feeds past the tear bar.
        return $commands.(($options['cut'] ?? true) ? "\x1D\x56\x42\x00" : "\x1B\x64\x04");
    }

    /**
     * Packed rows, 8 dots to a byte, where a 1 bit prints a dot. The threshold sits
     * a little past half grey so thin receipt type survives the downscale.
     */
    protected static function raster(GdImage $receipt, int $top, int $rows, int $dots): string
    {
        $data = '';

        for ($y = $top; $y < $top + $rows; $y++) {
            $ink = 0;

            for ($x = 0; $x < $dots; $x++) {
                $rgb = imagecolorat($receipt, $x, $y);
                $luminance = (($rgb >> 16) & 0xFF) * 299 + (($rgb >> 8) & 0xFF) * 587 + ($rgb & 0xFF) * 114;

                if ($luminance < 160000) {
                    $ink |= 0x80 >> ($x & 7);
                }

                if (($x & 7) === 7 || $x === $dots - 1) {
                    $data .= chr($ink);
                    $ink = 0;
                }
            }
        }

        return $data;
    }
}
