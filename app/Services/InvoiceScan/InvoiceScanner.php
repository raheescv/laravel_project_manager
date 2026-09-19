<?php

namespace App\Services\InvoiceScan;

/**
 * Read an uploaded purchase invoice into a grid of cells.
 *
 * PDFs go through pdf.js, photos and scans through tesseract, and both come out
 * as the header-and-rows shape a spreadsheet import already understands.
 */
class InvoiceScanner
{
    public function __construct(
        private PdfWordExtractor $pdf,
        private OcrWordExtractor $ocr,
    ) {}

    private function reader(): TableReader
    {
        return new TableReader((int) config('invoice_scan.max_rows', 500));
    }

    /**
     * @param  array|null  $bands  column ranges saved for this vendor, when there are any
     * @return array{columns: array, bands: array, rows: array, meta: array, pageCount: int, skipped: int, source: string}
     */
    public function scan(string $path, string $extension, ?array $bands = null): array
    {
        $extension = strtolower($extension);

        if ($this->pdf->supports($extension)) {
            $table = $this->reader()->read($this->pdf->extract($path), $bands);
            $table['source'] = 'pdf';

            return $table;
        }

        if (! $this->ocr->supports($extension)) {
            throw new InvoiceScanException('Upload the invoice as a PDF, or as a JPG or PNG photo of it.');
        }

        $table = $this->reader()->read($this->ocr->extract($path), $bands);
        $table['source'] = 'ocr';

        return $this->refine($path, $table);
    }

    public function canReadImages(): bool
    {
        return $this->ocr->available();
    }

    /**
     * Read every numeric cell of a scan a second time, on its own.
     *
     * Whole-image OCR is good at finding the grid and bad at the figures in it.
     * Now that the grid is known, each money cell is cropped out and read with
     * the alphabet restricted to digits, which is what takes the numbers from
     * roughly-right to right. A cell the second pass cannot read keeps the
     * first pass's value and is flagged low confidence for the review grid.
     */
    private function refine(string $path, array $table): array
    {
        if (! config('invoice_scan.ocr.refine_numeric', true)) {
            return $table;
        }

        $numericBands = array_keys(array_filter($table['bands'], fn ($band) => $band['numeric']));

        if ($numericBands === [] || $table['meta'] === []) {
            return $table;
        }

        $top = min(array_map(fn ($meta) => $meta['y'], $table['meta']));
        $bottom = max(array_map(fn ($meta) => $meta['y'] + $meta['h'], $table['meta']));

        foreach ($numericBands as $column) {
            $band = $table['bands'][$column];

            $words = $this->ocr->readNumericColumn($path, [
                'x' => $band['x0'],
                'y' => $top,
                'w' => $band['x1'] - $band['x0'],
                'h' => $bottom - $top,
            ]);

            if ($words === []) {
                continue;
            }

            $found = [];
            foreach ($words as $word) {
                $row = $this->rowAt($word['y'] + $word['h'] / 2, $table['meta']);

                if ($row !== null) {
                    $found[$row]['text'][] = $word['text'];
                    $found[$row]['conf'][] = $word['conf'];
                }
            }

            foreach ($table['rows'] as $index => $row) {
                if (($row[$column] ?? '') === '') {
                    continue;
                }

                if (! isset($found[$index])) {
                    $table['low_confidence'][$index][] = $column;

                    continue;
                }

                $table['rows'][$index][$column] = implode('', $found[$index]['text']);

                if (min($found[$index]['conf']) < 70) {
                    $table['low_confidence'][$index][] = $column;
                }
            }
        }

        return $table;
    }

    /** Which item row a y coordinate belongs to, if any. */
    private function rowAt(float $centre, array $meta): ?int
    {
        foreach ($meta as $index => $row) {
            if ($centre >= $row['y'] - $row['h'] * 0.25 && $centre <= $row['y'] + $row['h'] * 1.25) {
                return $index;
            }
        }

        return null;
    }
}
