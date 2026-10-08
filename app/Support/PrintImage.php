<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Stored images as data URIs for PDFs, scaled down to print size first.
 *
 * Phone photos land in storage at 1600×2800 and ~1 MB, but print at a few
 * centimetres — embedding them whole made the checklist HTML 12 MB and its
 * render several times slower. Scaled copies are cached on disk, keyed by the
 * source file's path, size and mtime, so a document printed twice scales once.
 */
class PrintImage
{
    /** Longest edge, in pixels, an embedded image keeps: ~300 dpi at 4 cm. */
    public const MAX_EDGE = 480;

    /**
     * @param  string|null  $relative  Path on the public disk, as stored on the model.
     */
    public static function dataUri(?string $relative): ?string
    {
        if (! $relative) {
            return null;
        }

        $path = public_path('storage/'.ltrim(preg_replace('#^public/#', '', $relative), '/'));
        if (! is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'png';
        if ($extension === 'svg') {
            return 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($path));
        }

        [$mime, $bytes] = self::scaled($path, $extension) ?? ['image/'.$extension, (string) file_get_contents($path)];

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    /**
     * The print-size copy, from the disk cache or freshly scaled; null to embed
     * the original (already small enough, or not something GD can read).
     *
     * @return array{0: string, 1: string}|null [mime, bytes]
     */
    private static function scaled(string $path, string $extension): ?array
    {
        $size = @getimagesize($path);
        $orientation = self::orientation($path, $extension);

        if (! $size || (max($size[0], $size[1]) <= self::MAX_EDGE && $orientation <= 1)) {
            return null;
        }

        $keepsAlpha = $extension !== 'jpg' && $extension !== 'jpeg';
        $mime = $keepsAlpha ? 'image/png' : 'image/jpeg';
        $cached = storage_path('framework/cache/print-images/'
            .sha1($path.'|'.filesize($path).'|'.filemtime($path).'|'.self::MAX_EDGE).($keepsAlpha ? '.png' : '.jpg'));

        if (is_file($cached)) {
            return [$mime, (string) file_get_contents($cached)];
        }

        try {
            $source = @imagecreatefromstring((string) file_get_contents($path));
            if ($source === false) {
                return null;
            }

            $source = self::upright($source, $orientation);
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_EDGE / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($keepsAlpha) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
            }
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            $keepsAlpha ? imagepng($canvas, null, 6) : imagejpeg($canvas, null, 85);
            $bytes = (string) ob_get_clean();

            imagedestroy($source);
            imagedestroy($canvas);

            File::ensureDirectoryExists(dirname($cached));
            File::put($cached, $bytes);

            return [$mime, $bytes];
        } catch (Throwable) {
            return null;
        }
    }

    /** EXIF orientation (1–8) of a JPEG; 1 when there is none. */
    private static function orientation(string $path, string $extension): int
    {
        if (! in_array($extension, ['jpg', 'jpeg'], true) || ! function_exists('exif_read_data')) {
            return 1;
        }

        return (int) (@exif_read_data($path)['Orientation'] ?? 1);
    }

    /**
     * Phones save photos sideways and record the turn in EXIF; GD ignores EXIF and
     * the re-encoded copy drops it, so the turn is applied to the pixels here.
     */
    private static function upright(\GdImage $image, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        // Counter-clockwise, as imagerotate() takes it; 5 and 7 are mirrored first.
        $degrees = match ($orientation) {
            3 => 180,
            6, 7 => 270,
            5, 8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $degrees, 0);
        imagedestroy($image);

        return $rotated;
    }
}
