<?php

use App\Services\InvoiceScan\InvoiceScanException;
use App\Services\InvoiceScan\TableReader;

/**
 * Rebuilding an invoice's item table out of positioned words.
 *
 * The fixture is the shape a real vendor invoice has: a letterhead, an address
 * block, a phone line, the item rows, and a totals strip — none of it marked up
 * as a table, all of it just text at coordinates. What is being pinned here is
 * that the item rows are picked out of that and land in the right columns,
 * because everything downstream (the mapping, the matching, what reaches the
 * cart) is built on this grid being right.
 */
function invoiceWord(float $x, float $y, float $w, string $text, float $h = 8.0): array
{
    return ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'text' => $text];
}

/**
 * @param  array<int, array{0:string,1:string,2:string,3:string,4:string}>  $lines
 */
function invoiceDocument(array $lines, array $extra = []): array
{
    // Columns at the x positions a real invoice uses: serial, description, qty,
    // rate and a right-aligned line total.
    $words = [
        invoiceWord(87, 12, 111, 'MAKEIDA MIDAS LLP', 11),
        invoiceWord(441, 15, 116, 'GST IN: 32ABRFM1674L1ZT'),
        invoiceWord(87, 59, 98, 'Ph : 0495 2725102, 401502'),
        invoiceWord(76, 183, 126, 'FASHONIC BEAUTY LOUNGE'),
    ];

    $y = 309.0;
    foreach ($lines as [$serial, $name, $qty, $rate, $total]) {
        $words[] = invoiceWord(7, $y, 9, $serial);
        $words[] = invoiceWord(28, $y, 64, $name);
        $words[] = invoiceWord(201, $y, 14, $qty);
        $words[] = invoiceWord(349 - strlen($rate) * 4, $y, strlen($rate) * 4, $rate);
        $words[] = invoiceWord(560 - strlen($total) * 4, $y, strlen($total) * 4, $total);
        $y += 13.7;
    }

    // The totals strip: figures with no description, which is exactly what has
    // to stay out of the table.
    $words[] = invoiceWord(505, $y + 40, 55, '22120.00');
    $words[] = invoiceWord(10, $y + 55, 207, 'Twenty Two Thousand One Hundred And Twenty RS Only.');

    return [
        'pages' => [['number' => 1, 'width' => 612, 'height' => 792, 'words' => array_merge($words, $extra)]],
        'pageCount' => 1,
    ];
}

it('puts each cell of an item row in its own column', function (): void {
    $table = (new TableReader())->read(invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'IKONIC HEATPROTECTIVE GL', '3.00', '349.58', '1237.50'],
        ['3', 'LOREAL DEVELOPER 40 VOL', '2.00', '630.51', '1488.00'],
    ]));

    expect($table['rows'])->toHaveCount(3)
        ->and($table['bands'])->toHaveCount(5)
        ->and($table['rows'][0])->toBe(['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'])
        ->and($table['rows'][1])->toBe(['2', 'IKONIC HEATPROTECTIVE GL', '3.00', '349.58', '1237.50']);
});

it('leaves the letterhead and the totals strip out of the table', function (): void {
    $table = (new TableReader())->read(invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
    ]));

    $flattened = collect($table['rows'])->map(fn ($row) => implode(' ', $row))->implode(' | ');

    expect($table['rows'])->toHaveCount(2)
        ->and($flattened)->not->toContain('MAKEIDA')
        ->and($flattened)->not->toContain('2725102')
        ->and($flattened)->not->toContain('22120.00')
        ->and($table['skipped'])->toBeGreaterThan(0);
});

/**
 * The letterhead is the dangerous one: "Ph : 0495 2725102, 401502" has words
 * and three figures, so a naive test calls it an item row — and because it runs
 * across the gap between the description and the next column, measuring the
 * columns with it welds the two into one.
 */
it('does not let a phone number in the letterhead merge two columns', function (): void {
    $table = (new TableReader())->read(invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
        ['3', 'LOREAL SHADE CARD', '1.00', '5084.75', '6000.00'],
    ]));

    expect($table['bands'])->toHaveCount(5)
        ->and($table['rows'][0][1])->toBe('HL MAKEUP TRAY')
        ->and($table['rows'][0][2])->toBe('1.00');
});

it('reads the column headings when the invoice prints them as text', function (): void {
    $document = invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
    ], [
        invoiceWord(7, 292, 14, 'Sl'),
        invoiceWord(28, 292, 47, 'Description'),
        invoiceWord(201, 292, 14, 'Qty'),
        invoiceWord(325, 292, 20, 'Rate'),
        invoiceWord(536, 292, 24, 'Amount'),
    ]);

    expect((new TableReader())->read($document)['columns'])
        ->toBe(['Sl', 'Description', 'Qty', 'Rate', 'Amount']);
});

it('numbers the columns when the headings are drawn as a picture', function (): void {
    $table = (new TableReader())->read(invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
    ]));

    expect($table['columns'])->toBe(['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Column 5']);
});

it('keeps the columns a vendor template pinned rather than measuring its own', function (): void {
    $bands = [
        ['x0' => 0.0, 'x1' => 20.0, 'numeric' => true],
        ['x0' => 21.0, 'x1' => 300.0, 'numeric' => false],
        ['x0' => 301.0, 'x1' => 612.0, 'numeric' => true],
    ];

    $table = (new TableReader())->read(invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
    ]), $bands);

    expect($table['bands'])->toBe($bands)
        ->and($table['rows'][0])->toBe(['1', 'HL MAKEUP TRAY 1.00', '190.68 225.00']);
});

it('says so plainly when there is no item table to find', function (): void {
    $document = [
        'pages' => [[
            'number' => 1,
            'width' => 612,
            'height' => 792,
            'words' => [
                invoiceWord(87, 12, 111, 'MAKEIDA MIDAS LLP', 11),
                invoiceWord(87, 30, 98, 'CALICUT-KERALA-673004'),
            ],
        ]],
        'pageCount' => 1,
    ];

    expect(fn () => (new TableReader())->read($document))
        ->toThrow(InvoiceScanException::class);
});

/** OCR draws the rules between columns as pipes, right in the gaps. */
it('ignores the table rules a scan reads as pipe characters', function (): void {
    $document = invoiceDocument([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '60.00'],
    ], [
        invoiceWord(145, 309, 2, '|', 10),
        invoiceWord(145, 322.7, 2, '|', 10),
        invoiceWord(310, 309, 2, '|', 10),
        invoiceWord(310, 322.7, 2, '|', 10),
    ]);

    $table = (new TableReader())->read($document);

    expect($table['bands'])->toHaveCount(5)
        ->and($table['rows'][0])->toBe(['1', 'HL MAKEUP TRAY', '1.00', '190.68', '225.00']);
});
