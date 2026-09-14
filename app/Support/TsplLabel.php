<?php

namespace App\Support;

use GdImage;
use RuntimeException;

/**
 * Turns rendered labels into TSPL, the command language of TSC label printers.
 *
 * On macOS, QZ Tray's PDF printing swaps the width and height of any label that
 * is wider than tall (JDK-8372952), so jewellery tags come out rotated and off
 * centre. TSPL skips that page handling: each label is drawn as a 1-bit bitmap
 * at the printer's own resolution, and SIZE carries the exact stock size.
 */
class TsplLabel
{
    /** TSC desktop printers print 203 dots per inch. */
    public const DPI = 203;

    /** Browsershot only takes a whole device scale, so labels render above print resolution and are resampled down. */
    public const RENDER_SCALE = 3;

    public static function dots(float $mm): int
    {
        return (int) round($mm / 25.4 * self::DPI);
    }

    /** CSS pixels (96 per inch) for a length in mm. */
    public static function cssPixels(float $mm): float
    {
        return $mm / 25.4 * 96;
    }

    /**
     * The whole CSS pixel row each label is laid out on before the screenshot. A
     * label height in mm is rarely a whole pixel, so plainly stacked labels start a
     * fraction of a pixel apart and every label after the first is resampled a
     * little differently. Rounded first so 19.0000001 stays 19.
     */
    public static function rowPixels(float $heightMm): int
    {
        return (int) ceil(round(self::cssPixels($heightMm), 3));
    }

    /**
     * @param  string  $png  screenshot of the labels stacked top to bottom, one rowPixels() row each, rendered at RENDER_SCALE
     * @param  array{flip?: bool, invert?: bool, offset_x?: float, offset_y?: float, gap?: float|null}  $options
     */
    public static function fromScreenshot(string $png, float $widthMm, float $heightMm, array $options = []): string
    {
        $sheet = @imagecreatefromstring($png);

        if (! $sheet) {
            throw new RuntimeException('The rendered label image could not be read.');
        }

        $pixelsPerMm = 96 / 25.4 * self::RENDER_SCALE;
        $sourceWidth = (int) round($widthMm * $pixelsPerMm);
        $sourceHeight = $heightMm * $pixelsPerMm;
        // Every label starts on a whole-pixel row, so the second one onwards slices exactly like the first.
        $rowHeight = self::rowPixels($heightMm) * self::RENDER_SCALE;
        $count = max(1, (int) round(imagesy($sheet) / $rowHeight));

        $width = self::dots($widthMm);
        $height = self::dots($heightMm);

        $commands = 'SIZE '.self::number($widthMm).' mm,'.self::number($heightMm)." mm\r\n";
        if (isset($options['gap'])) {
            $commands .= 'GAP '.self::number($options['gap'])." mm,0 mm\r\n";
        }
        $commands .= 'DIRECTION '.(empty($options['flip']) ? 0 : 1)."\r\n";
        // The printer moves the whole print along the roll, so even a big nudge never
        // crops the label. Flipping turns the image round, so "down" is the other way.
        $shift = self::dots((float) ($options['offset_y'] ?? 0));
        $commands .= 'SHIFT '.(empty($options['flip']) ? $shift : -$shift)."\r\n";
        $commands .= "REFERENCE 0,0\r\n";

        // A run of identical labels (a cart line with quantity 5) prints as copies of one bitmap.
        $previous = null;
        $copies = 0;

        for ($i = 0; $i < $count; $i++) {
            $label = self::label($sheet, $i * $rowHeight, $sourceWidth, $sourceHeight, $width, $height, $options);
            $bitmap = self::bitmap($label, $width, $height, ! empty($options['invert']));

            if ($bitmap === $previous) {
                $copies++;

                continue;
            }

            if ($previous !== null) {
                $commands .= self::printBitmap($previous, $width, $height, $copies);
            }

            $previous = $bitmap;
            $copies = 1;
        }

        return $commands.self::printBitmap($previous, $width, $height, $copies);
    }

    /**
     * One label cut from the stack, resampled to printer dots on white and nudged sideways.
     */
    protected static function label(GdImage $sheet, int $sourceY, int $sourceWidth, float $sourceHeight, int $width, int $height, array $options): GdImage
    {
        // The last label can end a pixel past the screenshot; never read outside it.
        $available = min($sourceHeight, imagesy($sheet) - $sourceY);
        $slice = self::blank($width, $height);
        imagecopyresampled($slice, $sheet, 0, 0, 0, $sourceY, $width, (int) round($height * $available / $sourceHeight), $sourceWidth, (int) round($available));

        // A sideways nudge moves the image inside the label; along the roll SHIFT does it.
        $offsetX = self::dots((float) ($options['offset_x'] ?? 0));

        if ($offsetX === 0) {
            return $slice;
        }

        $label = self::blank($width, $height);
        imagecopy($label, $slice, max(0, $offsetX), 0, max(0, -$offsetX), 0, $width - abs($offsetX), $height);

        return $label;
    }

    protected static function blank(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        return $image;
    }

    /**
     * Packed rows, 8 dots to a byte. TSPL prints a dot for a 0 bit; `invert` is
     * for printers whose firmware prints the 1 bits instead.
     */
    protected static function bitmap(GdImage $label, int $width, int $height, bool $invert): string
    {
        $data = '';

        for ($y = 0; $y < $height; $y++) {
            $ink = 0;

            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($label, $x, $y);
                $luminance = (($rgb >> 16) & 0xFF) * 299 + (($rgb >> 8) & 0xFF) * 587 + ($rgb & 0xFF) * 114;

                if ($luminance < 128000) {
                    $ink |= 0x80 >> ($x & 7);
                }

                if (($x & 7) === 7 || $x === $width - 1) {
                    $data .= chr($invert ? $ink : ~$ink & 0xFF);
                    $ink = 0;
                }
            }
        }

        return $data;
    }

    protected static function printBitmap(string $bitmap, int $width, int $height, int $copies): string
    {
        return "CLS\r\n"
            .'BITMAP 0,0,'.intdiv($width + 7, 8).','.$height.',0,'.$bitmap."\r\n"
            .'PRINT 1,'.$copies."\r\n";
    }

    protected static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
