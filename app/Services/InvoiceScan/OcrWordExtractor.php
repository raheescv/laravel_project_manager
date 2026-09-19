<?php

namespace App\Services\InvoiceScan;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Positioned text out of a photographed or scanned invoice, via tesseract.
 *
 * Two passes, because one is not good enough for money. Whole-image OCR finds
 * the rows and columns well but misreads digits badly — on a clean 290dpi
 * render of a real vendor invoice it turned 190.68 into 150.68 and 915.25 into
 * 91825. Once TableReader knows where the columns are, every numeric cell is
 * read again on its own: cropped, blown up, alphabet restricted to digits. The
 * same columns then came back 14/14 exact. Confidence travels with each word so
 * the review grid can flag what tesseract was unsure of.
 */
class OcrWordExtractor
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tif', 'tiff', 'gif'];

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), self::IMAGE_EXTENSIONS, true);
    }

    public function available(): bool
    {
        try {
            $this->binary();

            return true;
        } catch (InvoiceScanException) {
            return false;
        }
    }

    /**
     * @return array{pages: array<int, array{number:int,width:float,height:float,words:array}>, pageCount:int}
     */
    public function extract(string $path): array
    {
        [$width, $height] = $this->dimensions($path);

        $words = $this->readTsv($this->run([
            $path, '-',
            '--psm', (string) config('invoice_scan.ocr.psm', '6'),
            '-l', (string) config('invoice_scan.ocr.language', 'eng'),
            'tsv',
        ]));

        if ($words === []) {
            throw new InvoiceScanException('No text could be read from this image. A sharper, straighter photo of the invoice usually fixes it.');
        }

        return [
            'pages' => [[
                'number' => 1,
                'width' => $width,
                'height' => $height,
                'words' => $words,
            ]],
            'pageCount' => 1,
        ];
    }

    /**
     * Read one rectangle of the image again, digits only.
     *
     * @param  array{x:float,y:float,w:float,h:float}  $box
     *                                                       Read one whole column again, digits only.
     *
     * A column at a time rather than a cell at a time: tesseract reads a strip
     * of figures far better than a lone number torn out of context, and one
     * call per column instead of one per cell takes a page from half a minute
     * to a couple of seconds. The words come back in the coordinates of the
     * original image so the caller can drop each one on its own row.
     * @param  array{x:float,y:float,w:float,h:float}  $box  the column strip, in image pixels
     * @return array<int, array{y:float,h:float,text:string,conf:float}>
     */
    public function readNumericColumn(string $path, array $box): array
    {
        $scale = max(1, (int) config('invoice_scan.ocr.refine_scale', 3));
        $pad = max(3, (int) round($box['h'] * 0.02));
        $crop = $this->crop($path, $box, $scale, $pad);

        if (! $crop) {
            return [];
        }

        try {
            $words = $this->readTsv($this->run([
                $crop, '-',
                '--psm', '6',
                '-l', (string) config('invoice_scan.ocr.language', 'eng'),
                '-c', 'tessedit_char_whitelist=0123456789.,-',
                'tsv',
            ]));
        } finally {
            @unlink($crop);
        }

        return array_map(fn ($word) => [
            'y' => $box['y'] - $pad + $word['y'] / $scale,
            'h' => $word['h'] / $scale,
            'text' => $word['text'],
            'conf' => $word['conf'],
        ], $words);
    }

    /** @return array<int, array{x:float,y:float,w:float,h:float,text:string,conf:float}> */
    private function readTsv(string $tsv): array
    {
        $lines = preg_split('/\R/', trim($tsv)) ?: [];
        $header = null;
        $words = [];

        foreach ($lines as $line) {
            $cells = explode("\t", $line);
            if ($header === null) {
                $header = array_flip($cells);

                continue;
            }

            $text = trim($cells[$header['text'] ?? 11] ?? '');
            $conf = (float) ($cells[$header['conf'] ?? 10] ?? -1);

            if ($text === '' || $conf < 0) {
                continue;
            }

            $words[] = [
                'x' => (float) ($cells[$header['left'] ?? 6] ?? 0),
                'y' => (float) ($cells[$header['top'] ?? 7] ?? 0),
                'w' => (float) ($cells[$header['width'] ?? 8] ?? 0),
                'h' => (float) ($cells[$header['height'] ?? 9] ?? 0),
                'text' => $text,
                'conf' => $conf,
            ];
        }

        return $words;
    }

    private function run(array $arguments): string
    {
        $process = new Process([$this->binary(), ...$arguments]);
        $process->setTimeout((float) config('invoice_scan.timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Invoice scan: ocr failed', ['error' => $process->getErrorOutput()]);

            throw new InvoiceScanException('This image could not be read.');
        }

        return $process->getOutput();
    }

    /** Grayscale, upscaled crop of one cell — written to a temp file for tesseract. */
    private function crop(string $path, array $box, int $scale, int $pad = 3): ?string
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) {
            return null;
        }

        // Margin: tesseract reads a glyph that touches the edge of its crop as
        // a different glyph, and a measured box can sit a pixel or two inside
        // the ink.
        $rect = [
            'x' => max(0, (int) round($box['x'] - $pad)),
            'y' => max(0, (int) round($box['y'] - $pad)),
            'width' => (int) round($box['w'] + $pad * 2),
            'height' => (int) round($box['h'] + $pad * 2),
        ];

        $cropped = @imagecrop($image, $rect);
        imagedestroy($image);

        if (! $cropped) {
            return null;
        }

        // A crop of a palette PNG is itself a palette image, and imagescale()
        // simply returns false for one of those under IMG_BICUBIC — which is
        // how every refined cell came back empty the first time round.
        imagepalettetotruecolor($cropped);
        imagefilter($cropped, IMG_FILTER_GRAYSCALE);

        $width = imagesx($cropped) * $scale;
        $height = imagesy($cropped) * $scale;
        $scaled = imagescale($cropped, $width, $height, IMG_BICUBIC) ?: imagescale($cropped, $width, $height);
        imagedestroy($cropped);

        if (! $scaled) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'invoice-cell-').'.png';
        imagepng($scaled, $file);
        imagedestroy($scaled);

        return $file;
    }

    /** @return array{0: float, 1: float} */
    private function dimensions(string $path): array
    {
        $size = @getimagesize($path);

        if (! $size) {
            throw new InvoiceScanException('This file is not an image we can read.');
        }

        return [(float) $size[0], (float) $size[1]];
    }

    private function binary(): string
    {
        $binary = config('invoice_scan.tesseract_binary') ?: trim((string) shell_exec('which tesseract'));

        if (! $binary) {
            throw new InvoiceScanException('Reading a photo of an invoice needs tesseract on the server. Upload the vendor\'s PDF instead, or set INVOICE_SCAN_TESSERACT_BINARY in .env.');
        }

        return $binary;
    }
}
