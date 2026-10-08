<?php

use App\Support\PrintImage;
use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

/**
 * Stored photos are embedded in PDFs at print size: scaled down, turned upright
 * from their EXIF orientation, and cached so a reprint does not scale again.
 */
beforeEach(function (): void {
    $this->directory = 'print-image-test-'.uniqid();
    File::ensureDirectoryExists(public_path('storage/'.$this->directory));
});

afterEach(function (): void {
    File::deleteDirectory(public_path('storage/'.$this->directory));
});

/** Writes a solid-colour image to the public disk and returns its stored path. */
function storePrintTestImage(string $directory, string $name, int $width, int $height, ?int $orientation = null): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 40));

    ob_start();
    str_ends_with($name, '.png') ? imagepng($image) : imagejpeg($image);
    $bytes = (string) ob_get_clean();

    if ($orientation !== null) {
        // A minimal EXIF block holding only the Orientation tag, spliced in after
        // the JPEG's start-of-image marker — GD cannot write EXIF itself.
        $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
        $exif = "Exif\x00\x00".$tiff;
        $bytes = "\xFF\xD8\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($bytes, 2);
    }

    File::put(public_path("storage/{$directory}/{$name}"), $bytes);

    return "{$directory}/{$name}";
}

/** @return array{0: int, 1: int, 2: string} width, height, mime of a data URI */
function printTestImageSize(string $dataUri): array
{
    $info = getimagesizefromstring(base64_decode(substr($dataUri, strpos($dataUri, ',') + 1)));

    return [$info[0], $info[1], $info['mime']];
}

it('scales a large photo down to print size', function (): void {
    $uri = PrintImage::dataUri(storePrintTestImage($this->directory, 'photo.jpg', 1600, 2844));

    expect($uri)->toStartWith('data:image/jpeg;base64,')
        ->and(printTestImageSize($uri))->toBe([270, PrintImage::MAX_EDGE, 'image/jpeg']);
});

it('keeps a png a png so signatures stay transparent', function (): void {
    $uri = PrintImage::dataUri(storePrintTestImage($this->directory, 'signature.png', 791, 335));

    expect($uri)->toStartWith('data:image/png;base64,')
        ->and(printTestImageSize($uri))->toBe([PrintImage::MAX_EDGE, 203, 'image/png']);
});

it('turns a photo upright from its exif orientation', function (): void {
    $uri = PrintImage::dataUri(storePrintTestImage($this->directory, 'sideways.jpg', 600, 200, orientation: 6));

    expect(printTestImageSize($uri))->toBe([160, PrintImage::MAX_EDGE, 'image/jpeg']);
});

it('embeds an image that is already small as it is', function (): void {
    $path = storePrintTestImage($this->directory, 'icon.png', 40, 40);

    expect(PrintImage::dataUri($path))
        ->toBe('data:image/png;base64,'.base64_encode(File::get(public_path('storage/'.$path))));
});

it('reuses the scaled copy on a reprint', function (): void {
    $path = storePrintTestImage($this->directory, 'photo.jpg', 2000, 1000);
    $first = PrintImage::dataUri($path);
    $cached = File::glob(storage_path('framework/cache/print-images/*.jpg'));

    expect(PrintImage::dataUri($path))->toBe($first)
        ->and($cached)->not->toBeEmpty();
});

it('returns null for a missing or empty path', function (): void {
    expect(PrintImage::dataUri(null))->toBeNull()
        ->and(PrintImage::dataUri($this->directory.'/missing.jpg'))->toBeNull();
});
