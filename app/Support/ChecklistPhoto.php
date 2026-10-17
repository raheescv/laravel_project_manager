<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One pipeline for every photo taken on a hand-over checklist — the web tab's item,
 * move-out and fixture photos, and the technician app's uploads.
 *
 * Phone and tablet cameras hand over 3–8 MB shots, often as HEIC and often rotated
 * only by an EXIF flag. Each photo is turned upright, scaled down to MAX_EDGE on its
 * longest side and re-encoded as a JPEG, so it shows the right way up in every
 * browser, the PDF and the apps, and the unit's folder stays small.
 */
class ChecklistPhoto
{
    /** Largest upload accepted, in kilobytes — matches Livewire's temporary-upload cap. */
    public const MAX_KILOBYTES = 12288;

    /** Longest side, in pixels, a stored photo is scaled down to. */
    public const MAX_EDGE = 2000;

    public const QUALITY = 82;

    /** @return array<int, string> */
    public static function rules(): array
    {
        return ['file', 'mimes:jpg,jpeg,png,webp,gif,heic,heif', 'max:'.self::MAX_KILOBYTES];
    }

    /** @return array<string, string> */
    public static function messages(string $attribute): array
    {
        return [
            "$attribute.file" => 'The photo did not upload — please try again.',
            "$attribute.mimes" => 'Upload a photo (JPG, PNG, WEBP or HEIC).',
            "$attribute.max" => 'The photo must not be larger than '.(self::MAX_KILOBYTES / 1024).' MB.',
        ];
    }

    /**
     * Store the photo on the public disk and return its path.
     *
     * @throws RuntimeException when a HEIC photo can't be decoded on this server.
     */
    public static function store(UploadedFile $photo, string $directory): string
    {
        $jpeg = self::encodeWithImagick($photo->getRealPath()) ?? self::encodeWithGd($photo->getRealPath());

        if ($jpeg === null) {
            if (in_array(strtolower((string) $photo->guessExtension()), ['heic', 'heif'], true)) {
                throw new RuntimeException('This HEIC photo could not be read on the server. Set the camera to "Most Compatible" or upload a JPG.');
            }

            return $photo->store($directory, 'public');
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        return $path;
    }

    /** Store the photo, then drop the one it replaces. */
    public static function replace(UploadedFile $photo, string $directory, ?string $old): string
    {
        $path = self::store($photo, $directory);
        if ($old !== $path) {
            self::delete($old);
        }

        return $path;
    }

    /** Delete stored photos from the public disk, skipping blanks and missing files. */
    public static function delete(?string ...$paths): void
    {
        foreach (array_filter($paths) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private static function encodeWithImagick(string $file): ?string
    {
        if (! class_exists(\Imagick::class)) {
            return null;
        }

        try {
            $image = new \Imagick($file);
            $image->setIteratorIndex(0);
            $image = $image->getImage();
            $image->autoOrient();

            if (max($image->getImageWidth(), $image->getImageHeight()) > self::MAX_EDGE) {
                $image->resizeImage(self::MAX_EDGE, self::MAX_EDGE, \Imagick::FILTER_LANCZOS, 1, true);
            }

            $image->setImageBackgroundColor('white');
            $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(self::QUALITY);
            $image->setInterlaceScheme(\Imagick::INTERLACE_JPEG);
            $image->stripImage();

            $blob = $image->getImageBlob();
            $image->clear();

            return $blob ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function encodeWithGd(string $file): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $source = @imagecreatefromstring((string) file_get_contents($file));
            if ($source === false) {
                return null;
            }

            $exif = function_exists('exif_read_data') ? @exif_read_data($file) : false;
            $source = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => imagerotate($source, 180, 0),
                6 => imagerotate($source, -90, 0),
                8 => imagerotate($source, 90, 0),
                default => $source,
            };

            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_EDGE / max($width, $height));
            $canvas = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, imagesx($canvas), imagesy($canvas), $width, $height);

            ob_start();
            imagejpeg($canvas, null, self::QUALITY);
            $blob = ob_get_clean();

            imagedestroy($source);
            imagedestroy($canvas);

            return $blob ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
