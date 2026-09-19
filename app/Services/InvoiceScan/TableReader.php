<?php

namespace App\Services\InvoiceScan;

/**
 * Rebuild the item table of an invoice from positioned words.
 *
 * An invoice table is not marked up anywhere in the file — it is just text that
 * happens to line up. So we use the alignment: words at the same height are a
 * row, and the x ranges those words occupy, merged across every row, are the
 * columns. That works the same whether the coordinates came out of a PDF or off
 * a scan, which is why OCR needs no separate parser.
 *
 * The output is deliberately the same shape RawSheetImport returns for a
 * spreadsheet — a header list and rows of cells — so the mapping and matching
 * the sheet importer already does works unchanged on a PDF.
 */
class TableReader
{
    /** Words closer together than this fraction of a line height are one cell. */
    private const COLUMN_GAP = 0.9;

    /** How far apart two text columns may sit and still be one wrapped column. */
    private const TEXT_MERGE_GAP = 1.8;

    /** A row needs this many numeric cells before it can be an item row. */
    private const MIN_NUMERIC_CELLS = 3;

    /**
     * Nothing here reaches for the framework on purpose: this is the one piece
     * of the scanner that is pure geometry, and it is worth being able to test
     * it on a handful of coordinates without booting an application.
     */
    public function __construct(private int $maxRows = 500) {}

    /**
     * @param  array  $document  as returned by PdfWordExtractor / OcrWordExtractor
     * @return array{
     *     columns: array<int, string>,
     *     bands: array<int, array{x0: float, x1: float, numeric: bool}>,
     *     rows: array<int, array<int, string>>,
     *     meta: array<int, array{page: int, y: float, h: float, conf: float|null}>,
     *     pageCount: int,
     *     skipped: int
     * }
     */
    public function read(array $document, ?array $bands = null): array
    {
        $rows = $this->rowsOf($document);

        if ($rows === []) {
            throw new InvoiceScanException('No text was found in this invoice.');
        }

        $lineHeight = $this->median(array_map(fn ($row) => $row['h'], $rows)) ?: 8.0;
        $itemRows = $this->itemRowsOf($rows, $lineHeight);

        if (count($itemRows) < 1) {
            throw new InvoiceScanException('No item table was found in this invoice. Check that the upload is the invoice itself, not a summary or a delivery note.');
        }

        $bands = $bands ?: $this->bandsOf($itemRows, $lineHeight);

        $cells = [];
        $meta = [];
        $limit = $this->maxRows;

        foreach ($itemRows as $row) {
            if (count($cells) >= $limit) {
                break;
            }

            $line = $this->distribute($row, $bands);

            // A row that lands in one or two bands is a note or a page footer
            // that happened to carry numbers, not an item.
            if (count(array_filter($line, fn ($value) => $value !== '')) < 3) {
                continue;
            }

            $cells[] = $line;
            $meta[] = [
                'page' => $row['page'],
                'y' => $row['y'],
                'h' => $row['h'],
                'conf' => $row['conf'],
            ];
        }

        if ($cells === []) {
            throw new InvoiceScanException('The item table could not be read from this invoice.');
        }

        return [
            'columns' => $this->labels($rows, $itemRows, $bands),
            'bands' => $bands,
            'rows' => $cells,
            'meta' => $meta,
            'pageCount' => (int) ($document['pageCount'] ?? 1),
            'skipped' => count($rows) - count($cells),
        ];
    }

    /**
     * Words grouped into lines of text, pages stacked one under the other.
     *
     * @return array<int, array{page:int, y:float, h:float, conf:float|null, words:array}>
     */
    private function rowsOf(array $document): array
    {
        $rows = [];
        $offset = 0.0;

        foreach ($document['pages'] ?? [] as $page) {
            $words = array_values(array_filter($page['words'] ?? [], fn ($word) => ! $this->isRuleGlyph($word)));
            if ($words === []) {
                $offset += (float) ($page['height'] ?? 0);

                continue;
            }

            $heights = array_map(fn ($word) => (float) $word['h'], $words);
            $tolerance = max(1.0, ($this->median($heights) ?: 8.0) * 0.5);

            usort($words, fn ($a, $b) => [$a['y'], $a['x']] <=> [$b['y'], $b['x']]);

            $current = null;
            foreach ($words as $word) {
                $centre = (float) $word['y'] + (float) $word['h'] / 2;

                if ($current !== null && abs($centre - $current['centre']) <= $tolerance) {
                    $current['words'][] = $word;
                    $current['centre'] = ($current['centre'] * (count($current['words']) - 1) + $centre) / count($current['words']);

                    continue;
                }

                if ($current !== null) {
                    $rows[] = $this->finishRow($current, (int) ($page['number'] ?? 1), $offset);
                }

                $current = ['centre' => $centre, 'words' => [$word]];
            }

            if ($current !== null) {
                $rows[] = $this->finishRow($current, (int) ($page['number'] ?? 1), $offset);
            }

            $offset += (float) ($page['height'] ?? 0);
        }

        return $rows;
    }

    private function finishRow(array $row, int $page, float $offset): array
    {
        $words = $row['words'];
        usort($words, fn ($a, $b) => $a['x'] <=> $b['x']);

        $confidences = array_filter(array_map(fn ($word) => $word['conf'] ?? null, $words), fn ($value) => $value !== null);

        // The true top and bottom of the line, not the median glyph height:
        // re-reading a cell of a scan crops this rectangle out of the image,
        // and a box even a few pixels short slices the digits in half.
        $top = min(array_map(fn ($word) => (float) $word['y'], $words));
        $bottom = max(array_map(fn ($word) => (float) $word['y'] + (float) $word['h'], $words));

        return [
            'page' => $page,
            'y' => $top + $offset,
            'h' => max($bottom - $top, $this->median(array_map(fn ($word) => (float) $word['h'], $words)) ?: 8.0),
            'conf' => $confidences === [] ? null : (float) min($confidences),
            'words' => $words,
        ];
    }

    /**
     * Item rows carry both a description and several figures.
     *
     * The two halves of that test are what keep the address block (words, no
     * figures) and the totals block (figures, no words) out of the table.
     */
    private function looksLikeItemRow(array $row, float $lineHeight): bool
    {
        $numeric = 0;
        $words = false;

        foreach ($row['words'] as $word) {
            if ($this->isNumeric($word['text'])) {
                $numeric++;
            } elseif (preg_match('/\p{L}{2,}/u', $word['text'])) {
                $words = true;
            }
        }

        // A cell of a scanned invoice can hold several words, so count the
        // figures inside the text too rather than only whole numeric cells.
        if ($numeric < self::MIN_NUMERIC_CELLS) {
            $numeric = preg_match_all('/(?<![\d.])\d[\d,]*(?:\.\d+)?(?![\d.])/', implode(' ', array_column($row['words'], 'text')));
        }

        return $words && $numeric >= self::MIN_NUMERIC_CELLS;
    }

    private function isNumeric(string $text): bool
    {
        return (bool) preg_match('/^[\p{Sc}]?-?[\d][\d,]*(?:\.\d+)?%?$/u', trim($text));
    }

    /**
     * Is this "word" really a line of the table's own grid?
     *
     * OCR reads the vertical rules between columns as pipes and stray capital
     * I's, and they land exactly in the gaps the column detection measures — a
     * single one of them welds two columns together. A PDF has no such words,
     * so this only ever fires on a scan.
     */
    private function isRuleGlyph(array $word): bool
    {
        $text = trim((string) $word['text']);

        if ($text === '') {
            return true;
        }

        if (preg_match('/^[|!\[\]{}()\\\\\/_]+$/u', $text)) {
            return true;
        }

        // A tall, hairline "I" or "l" is a rule; the same letter inside a word
        // is not, and a word is never this much taller than it is wide.
        $height = (float) ($word['h'] ?? 0);
        $width = (float) ($word['w'] ?? 0);

        return preg_match('/^[Il]$/u', $text) && $height > 0 && $width / $height < 0.45;
    }

    /**
     * Pick the rows that are really the item table.
     *
     * "Has words and figures" alone is not enough — a letterhead phone line
     * ("Ph : 0495 2725102, 401502") passes it, and one stray row is enough to
     * bridge two real columns into one when the bands are measured. What the
     * item rows actually have in common is each other: they sit in one block,
     * one line apart, and they put their figures at the same x every time. So
     * the candidates are grouped into blocks and the biggest block wins, plus
     * any later block that uses the same columns — which is how the items on
     * page two of a long invoice come along.
     *
     * @return array<int, array>
     */
    private function itemRowsOf(array $rows, float $lineHeight): array
    {
        $candidates = array_values(array_filter($rows, fn ($row) => $this->looksLikeItemRow($row, $lineHeight)));

        if (count($candidates) < 2) {
            return $candidates;
        }

        $blocks = [];
        $current = [];

        foreach ($candidates as $row) {
            $previous = end($current) ?: null;

            if ($previous && $lineHeight * 3.5 >= $row['y'] - $previous['y']) {
                $current[] = $row;

                continue;
            }

            if ($current !== []) {
                $blocks[] = $current;
            }
            $current = [$row];
        }

        if ($current !== []) {
            $blocks[] = $current;
        }

        usort($blocks, fn ($a, $b) => count($b) <=> count($a));
        $winner = array_shift($blocks);
        $signature = $this->signature($winner, $lineHeight);

        foreach ($blocks as $block) {
            if ($this->matchesSignature($block, $signature, $lineHeight)) {
                $winner = array_merge($winner, $block);
            }
        }

        usort($winner, fn ($a, $b) => $a['y'] <=> $b['y']);

        return $winner;
    }

    /** Which x positions a block of rows puts its figures at. */
    private function signature(array $block, float $lineHeight): array
    {
        $buckets = [];

        foreach ($block as $row) {
            foreach ($row['words'] as $word) {
                if ($this->isNumeric($word['text'])) {
                    $buckets[$this->bucket((float) $word['x'] + (float) $word['w'] / 2, $lineHeight)] = true;
                }
            }
        }

        return $buckets;
    }

    private function matchesSignature(array $block, array $signature, float $lineHeight): bool
    {
        $buckets = $this->signature($block, $lineHeight);

        if ($buckets === []) {
            return false;
        }

        $hits = count(array_intersect_key($buckets, $signature));

        return $hits / count($buckets) >= 0.6;
    }

    private function bucket(float $x, float $lineHeight): int
    {
        return (int) floor($x / max(4.0, $lineHeight));
    }

    /**
     * The x ranges the item rows actually occupy, merged into columns.
     *
     * Right-aligned money columns overlap each other row to row, so merging
     * overlapping ranges finds them exactly. The gap rule is what separates two
     * columns that never overlap, and it is deliberately tight: on a real
     * invoice the quantity and free-quantity columns sit barely a character
     * apart.
     *
     * @return array<int, array{x0: float, x1: float, numeric: bool}>
     */
    private function bandsOf(array $itemRows, float $lineHeight): array
    {
        $intervals = [];
        foreach ($itemRows as $row) {
            foreach ($row['words'] as $word) {
                $intervals[] = [
                    'x0' => (float) $word['x'],
                    'x1' => (float) $word['x'] + (float) $word['w'],
                    'numeric' => $this->isNumeric($word['text']),
                ];
            }
        }

        usort($intervals, fn ($a, $b) => $a['x0'] <=> $b['x0']);

        $gap = $lineHeight * self::COLUMN_GAP;
        $bands = [];

        foreach ($intervals as $interval) {
            $last = array_key_last($bands);

            if ($last !== null && $gap > $interval['x0'] - $bands[$last]['x1']) {
                $bands[$last]['x1'] = max($bands[$last]['x1'], $interval['x1']);
                $bands[$last]['numeric'] += $interval['numeric'] ? 1 : 0;
                $bands[$last]['count']++;

                continue;
            }

            $bands[] = [
                'x0' => $interval['x0'],
                'x1' => $interval['x1'],
                'numeric' => $interval['numeric'] ? 1 : 0,
                'count' => 1,
            ];
        }

        $bands = $this->mergeWrappedTextBands($bands, $lineHeight);

        return array_map(fn ($band) => [
            'x0' => round($band['x0'], 2),
            'x1' => round($band['x1'], 2),
            'numeric' => $band['count'] > 0 && $band['numeric'] / $band['count'] >= 0.6,
        ], $bands);
    }

    /**
     * A description read off a scan arrives as separate words, and a wide gap
     * inside it would otherwise split the column in two. Only text columns are
     * folded together, and only when they are close, so a genuine neighbour
     * never gets swallowed.
     */
    private function mergeWrappedTextBands(array $bands, float $lineHeight): array
    {
        $gap = $lineHeight * self::TEXT_MERGE_GAP;
        $merged = [];

        foreach ($bands as $band) {
            $last = array_key_last($merged);
            $isText = $band['count'] > 0 && $band['numeric'] / $band['count'] < 0.4;
            $lastIsText = $last !== null && $merged[$last]['count'] > 0 && $merged[$last]['numeric'] / $merged[$last]['count'] < 0.4;

            if ($last !== null && $isText && $lastIsText && $gap > $band['x0'] - $merged[$last]['x1']) {
                $merged[$last]['x1'] = max($merged[$last]['x1'], $band['x1']);
                $merged[$last]['numeric'] += $band['numeric'];
                $merged[$last]['count'] += $band['count'];

                continue;
            }

            $merged[] = $band;
        }

        return $merged;
    }

    /** Put every word of a row into the column its centre falls in. */
    private function distribute(array $row, array $bands): array
    {
        $line = array_fill(0, count($bands), []);

        foreach ($row['words'] as $word) {
            $centre = (float) $word['x'] + (float) $word['w'] / 2;
            $index = $this->bandFor($centre, $bands);

            if ($index !== null) {
                $line[$index][] = $word['text'];
            }
        }

        return array_map(fn ($parts) => trim(implode(' ', $parts)), $line);
    }

    private function bandFor(float $centre, array $bands): ?int
    {
        $nearest = null;
        $distance = INF;

        foreach ($bands as $index => $band) {
            if ($centre >= $band['x0'] && $centre <= $band['x1']) {
                return $index;
            }

            $gap = $centre < $band['x0'] ? $band['x0'] - $centre : $centre - $band['x1'];
            if ($gap < $distance) {
                $distance = $gap;
                $nearest = $index;
            }
        }

        return $nearest;
    }

    /**
     * Column headings, when the invoice has any as text.
     *
     * Plenty of invoices — this vendor's included — draw the heading strip as an
     * image, so there is nothing to read. Those columns are numbered instead and
     * the user maps them once; the mapping is then remembered for that vendor.
     *
     * @return array<int, string>
     */
    private function labels(array $rows, array $itemRows, array $bands): array
    {
        $labels = array_map(fn ($index) => 'Column '.($index + 1), array_keys($bands));
        $firstItem = $itemRows[0];

        $candidates = array_filter(
            $rows,
            fn ($row) => $row['page'] === $firstItem['page'] && $row['y'] < $firstItem['y'] && count($row['words']) >= max(3, count($bands) - 3)
        );

        $header = collect($candidates)->sortByDesc('y')->first();

        if (! $header) {
            return $labels;
        }

        $hits = 0;
        $found = $labels;

        foreach ($header['words'] as $word) {
            $centre = (float) $word['x'] + (float) $word['w'] / 2;
            foreach ($bands as $index => $band) {
                if ($centre >= $band['x0'] && $centre <= $band['x1']) {
                    $found[$index] = $this->isNumeric($word['text']) ? $found[$index] : trim($word['text']);
                    $hits++;
                    break;
                }
            }
        }

        // Half the columns have to line up before we believe this row is the
        // heading strip and not the last line of the address.
        return $hits >= max(2, (int) ceil(count($bands) / 2)) ? $found : $labels;
    }

    /** @param array<int, float> $values */
    private function median(array $values): ?float
    {
        $values = array_values(array_filter($values, fn ($value) => $value > 0));

        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
