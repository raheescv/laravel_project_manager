<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait OptimizesUploadedImage
{
    /**
     * Center-crop to a square, downscale to 400px and re-encode as a compressed
     * WEBP so every avatar stays tiny (typically < 30KB) regardless of the source
     * upload. Falls back to storing the original file if GD is unavailable.
     * Mirrors the customer-avatar pipeline used across the app.
     *
     * @param  \Illuminate\Http\UploadedFile  $photo
     * @return string Relative path on the `public` disk (e.g. users/<uuid>.webp).
     */
    protected function storeOptimizedImage($photo, string $directory = 'users'): string
    {
        $filename = $directory.'/'.Str::uuid()->toString().'.webp';

        if (! function_exists('imagecreatetruecolor')) {
            return $photo->store($directory, 'public');
        }

        try {
            $source = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
            if ($source === false) {
                return $photo->store($directory, 'public');
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $side = min($width, $height);
            $srcX = (int) (($width - $side) / 2);
            $srcY = (int) (($height - $side) / 2);

            $size = min(400, $side);
            $canvas = imagecreatetruecolor($size, $size);
            imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $size, $size, $side, $side);

            ob_start();
            imagewebp($canvas, null, 82);
            $contents = ob_get_clean();

            imagedestroy($source);
            imagedestroy($canvas);

            Storage::disk('public')->put($filename, $contents);

            return $filename;
        } catch (\Throwable $e) {
            return $photo->store($directory, 'public');
        }
    }
}
