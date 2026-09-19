<?php

namespace App\Services\InvoiceScan;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Positioned text out of a PDF, via tools/pdf-words.mjs (pdfjs-dist).
 *
 * Why not a PHP library: the pure-PHP parsers hand back whatever the producer
 * happened to emit in one text-showing operator, and this vendor's invoice puts
 * a whole row in one — "HL MAKEUP TRAY 39231090 300.00" at a single x. Column
 * positions are the entire signal in an invoice table, so we read the glyph
 * positions properly instead. node is already a dependency here (Browsershot).
 */
class PdfWordExtractor
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'pdf';
    }

    /**
     * @return array{pages: array<int, array{number:int,width:float,height:float,words:array<int, array{x:float,y:float,w:float,h:float,text:string}>}>, pageCount:int}
     */
    public function extract(string $path): array
    {
        $node = $this->binary();

        $process = new Process([
            $node,
            base_path('tools/pdf-words.mjs'),
            $path,
            (string) config('invoice_scan.max_pages', 20),
        ], base_path());

        $process->setTimeout((float) config('invoice_scan.timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Invoice scan: pdf extraction failed', [
                'error' => $process->getErrorOutput(),
                'exit' => $process->getExitCode(),
            ]);

            throw new InvoiceScanException('This PDF could not be read. It may be password protected or damaged.');
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload) || ! isset($payload['pages'])) {
            throw new InvoiceScanException('This PDF could not be read.');
        }

        if (collect($payload['pages'])->sum(fn ($page) => count($page['words'] ?? [])) === 0) {
            // A scan saved as a PDF: no glyphs at all, only a picture of the
            // invoice. Rasterising it here would need another binary, so say
            // plainly what to upload instead.
            throw new InvoiceScanException('This PDF holds no text — it is a scan. Save the page as a JPG or PNG and upload that, so it can be read by OCR.');
        }

        return $payload;
    }

    private function binary(): string
    {
        $node = config('invoice_scan.node_binary') ?: trim((string) shell_exec('which node'));

        if (! $node) {
            throw new InvoiceScanException('node was not found on the server, so PDFs cannot be read. Set INVOICE_SCAN_NODE_BINARY in .env.');
        }

        return $node;
    }
}
