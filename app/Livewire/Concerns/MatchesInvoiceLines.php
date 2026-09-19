<?php

namespace App\Livewire\Concerns;

use App\Models\Product;

/**
 * Bind invoice lines to catalogue products.
 *
 * Shared by the two ways an invoice gets into this system: the spreadsheet
 * uploader at /purchase/import, and the PDF/photo scanner that fills the cart
 * on /purchase/create. Both face the same problem — a vendor's own wording for
 * a product, often clipped by their printing, has to find the right row in our
 * catalogue — so both use the same ladder and the same rules about when a match
 * is good enough to keep.
 *
 * The host component supplies:
 *   public string $matchBy;   'auto' | 'code' | 'barcode' | 'name'
 *   public $defaultTax;       tax % to fall back on
 */
trait MatchesInvoiceLines
{
    /**
     * Every field the sheet can carry, with the header names we auto detect.
     *
     * @return array<string, array{label: string, hint: string, aliases: array<int, string>}>
     */
    public function getFieldsProperty(): array
    {
        return [
            'product_code' => [
                'label' => 'Product Code',
                'hint' => 'Matches products.code',
                'aliases' => ['productcode', 'code', 'itemcode', 'sku', 'articleno', 'artno', 'article', 'itemno', 'partno'],
            ],
            'barcode' => [
                'label' => 'Barcode',
                'hint' => 'Matches products.barcode',
                'aliases' => ['barcode', 'ean', 'upc', 'barcodeno'],
            ],
            'product_name' => [
                'label' => 'Product Name',
                'hint' => 'Used when no code or barcode matches',
                'aliases' => ['productname', 'name', 'product', 'description', 'item', 'itemname', 'particulars', 'itemdescription'],
            ],
            'batch' => [
                'label' => 'Batch',
                'hint' => 'Optional batch or lot number',
                'aliases' => ['batch', 'batchno', 'lot', 'lotno'],
            ],
            'quantity' => [
                'label' => 'Quantity',
                'hint' => 'Defaults to 1 when empty',
                'aliases' => ['quantity', 'qty', 'qnty', 'pcs', 'pieces', 'units', 'nos'],
            ],
            'unit_price' => [
                'label' => 'Unit Price',
                'hint' => 'Cost of one unit before discount',
                'aliases' => ['unitprice', 'price', 'rate', 'cost', 'unitcost', 'unitrate', 'purchaserate'],
            ],
            'discount' => [
                'label' => 'Discount',
                'hint' => 'Amount off the line, not a percentage',
                'aliases' => ['discount', 'disc', 'discountamount', 'discamount'],
            ],
            'tax' => [
                'label' => 'Tax %',
                'hint' => 'Percentage applied after discount',
                'aliases' => ['tax', 'vat', 'gst', 'taxpercent', 'taxpercentage', 'vatpercent'],
            ],
        ];
    }

    /**
     * One query per identifier type, so a 500 line sheet costs three queries.
     *
     * Grouped, not keyed: several products can share a name (or a code), and a
     * line that matches more than one must be flagged rather than silently
     * bound to whichever row the database returned last.
     *
     * @return array{code: array, barcode: array, name: array}
     */
    private function lookupProducts(array $rows): array
    {
        $codes = collect($rows)->pluck('product_code')->filter()->map(fn ($v) => (string) $v)->unique()->values();
        $barcodes = collect($rows)->pluck('barcode')->filter()->map(fn ($v) => (string) $v)->unique()->values();
        $names = collect($rows)->pluck('product_name')->filter()->map(fn ($v) => (string) $v)->unique()->values();

        $group = fn ($products, string $column) => $products->groupBy(fn ($p) => strtolower((string) $p->{$column}));

        $byCode = $codes->isEmpty() ? collect() : $group(Product::whereIn('code', $codes->all())->get($this->catalogueColumns()), 'code');
        $byBarcode = $barcodes->isEmpty() ? collect() : $group(Product::whereIn('barcode', $barcodes->all())->get($this->catalogueColumns()), 'barcode');
        $byName = $names->isEmpty() ? collect() : $group(Product::whereIn('name', $names->all())->get($this->catalogueColumns()), 'name');

        return ['code' => $byCode, 'barcode' => $byBarcode, 'name' => $byName];
    }

    private function catalogueColumns(): array
    {
        return ['id', 'code', 'barcode', 'name', 'unit_id', 'cost', 'tax', 'expense_account_id'];
    }

    /**
     * Second pass for lines no exact identifier could place.
     *
     * Vendor invoices clip their description column, so "LEEPOSH HYDRA FACIAL
     * SER" never equals the catalogue's "LEEPOSH HYDRA FACIAL SERUM". A prefix
     * LIKE catches that; a name that still resolves to several products is
     * marked ambiguous for the user to pick, never guessed at.
     */
    private function applyPartialNameMatches(array &$items): void
    {
        if ($this->matchBy !== 'auto' && $this->matchBy !== 'name') {
            return;
        }

        $pending = collect($items)
            ->filter(fn ($item) => ! $item['product_id'] && $item['status'] !== 'ambiguous' && $item['raw_name'])
            ->pluck('raw_name')
            ->map(fn ($name) => (string) $name)
            ->unique()
            ->values();

        if ($pending->isEmpty()) {
            return;
        }

        $candidates = collect();
        foreach ($pending->chunk(25) as $chunk) {
            $candidates = $candidates->merge(
                Product::where(function ($query) use ($chunk): void {
                    foreach ($chunk as $name) {
                        $query->orWhere('name', 'like', $this->escapeLike($name).'%');
                    }
                })->limit(200)->get($this->catalogueColumns())
            );
        }

        if ($candidates->isEmpty()) {
            return;
        }

        foreach ($items as $index => $item) {
            if ($item['product_id'] || $item['status'] === 'ambiguous' || ! $item['raw_name']) {
                continue;
            }

            $needle = strtolower((string) $item['raw_name']);
            $hits = $candidates->filter(fn ($p) => str_starts_with(strtolower((string) $p->name), $needle))->values();

            if ($hits->isEmpty()) {
                continue;
            }

            $items[$index] = $this->bindProduct($item, $hits, 'name~');
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Attach a product to a line, or flag the line when the match is not unique.
     *
     * When a name fits several products the sheet's own rate usually settles it:
     * the variants of a clipped description ("…SERUM 30ML / 50ML / 100ML") are
     * priced differently, so a rate equal to exactly one candidate's cost is a
     * far better signal than picking the first row.
     */
    private function bindProduct(array $item, $hits, string $matchedOn): array
    {
        $byCost = $hits->count() > 1
            ? $hits->filter(fn ($p) => $this->sameRate($p->cost, $item['unit_price']))->values()
            : $hits;

        if ($byCost->count() === 1 && $hits->count() > 1) {
            $hits = $byCost;
            $item['by_cost'] = true;
        }

        if ($hits->count() > 1) {
            $item['status'] = 'ambiguous';
            $item['message'] = $hits->count().' products '.match ($matchedOn) {
                'code' => 'share this code',
                'barcode' => 'share this barcode',
                'name~' => 'start with this name',
                default => 'share this name',
            }.' — pick the right one.';
            // nearest cost first: the line's own rate is the best clue to which
            // variant it is, so the plausible candidates lead the short list.
            $rate = (float) $item['unit_price'];
            $item['candidate_count'] = $hits->count();
            $item['candidates'] = $hits
                ->sortBy(fn ($p) => [abs((float) $p->cost - $rate), (string) $p->name])
                ->take(4)
                ->map->only(['id', 'name', 'code', 'cost'])
                ->values()
                ->all();

            return $item;
        }

        $product = $hits->first();

        $item['product_id'] = $product->id;
        $item['name'] = $product->name;
        $item['code'] = $product->code ?: $item['raw_code'];
        $item['barcode'] = $product->barcode ?: $item['raw_barcode'];
        $item['unit_id'] = $product->unit_id;
        $item['account_id'] = $product->expense_account_id;
        $item['matched_on'] = $matchedOn;
        $item['product_cost'] = (float) $product->cost;
        $item['status'] = 'ok';
        $item['message'] = null;
        $item['candidates'] = [];
        $item['candidate_count'] = 0;

        return $item;
    }

    /** Two money values are the same line rate once rounded to the stored scale. */
    public function sameRate($a, $b): bool
    {
        return round((float) $a, 2) === round((float) $b, 2);
    }

    /**
     * Is the gap between the catalogue cost and the invoice rate worth showing?
     *
     * Vendor rates are rounded to two decimals, so a few paise on a 20,000 line
     * is arithmetic, not a price change. Half a percent is the threshold.
     */
    public function hasCostVariance(array $item): bool
    {
        if ($item['status'] !== 'ok' || ! $item['product_cost']) {
            return false;
        }

        $delta = abs((float) $item['unit_price'] - (float) $item['product_cost']);

        return $delta >= 0.01 && $delta / (float) $item['product_cost'] >= 0.005;
    }

    private function makeItem(array $values, array $catalogue): array
    {
        $quantity = $this->number($values['quantity'] ?? null, 1);
        $unitPrice = $this->number($values['unit_price'] ?? null, 0);
        $discount = $this->number($values['discount'] ?? null, 0);
        $tax = $values['tax'] === null || $values['tax'] === '' ? null : $this->number($values['tax'], 0);

        $item = [
            'line' => $values['__line'],
            'product_id' => null,
            'name' => $values['product_name'] ?: '—',
            'code' => $values['product_code'] ?: null,
            'barcode' => $values['barcode'] ?: null,
            'unit_id' => null,
            'account_id' => null,
            'raw_code' => $values['product_code'] ?: null,
            'raw_barcode' => $values['barcode'] ?: null,
            'raw_name' => $values['product_name'] ?: null,
            'matched_on' => null,
            'by_cost' => false,
            'candidates' => [],
            'candidate_count' => 0,
            'product_cost' => null,
            'batch' => $values['batch'] ?: null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax' => $tax,
            'status' => 'unmatched',
            'message' => 'No product matches '.collect([$values['product_code'], $values['barcode'], $values['product_name']])->filter()->first(),
        ];

        // Exact identifiers first, in the order the user chose; a partial name
        // match is a separate pass over whatever is left (applyPartialNameMatches).
        $order = match ($this->matchBy) {
            'code' => ['code'],
            'barcode' => ['barcode'],
            'name' => ['name'],
            default => ['code', 'barcode', 'name'],
        };

        foreach ($order as $key) {
            $needle = match ($key) {
                'code' => $values['product_code'] ?? null,
                'barcode' => $values['barcode'] ?? null,
                default => $values['product_name'] ?? null,
            };
            if ($needle === null || $needle === '') {
                continue;
            }
            $hits = $catalogue[$key][strtolower((string) $needle)] ?? null;
            if ($hits && $hits->isNotEmpty()) {
                $item = $this->bindProduct($item, $hits, $key);
                break;
            }
        }

        // Tax falls back to the product's own rate, then the sheet-wide default.
        if ($item['tax'] === null) {
            $item['tax'] = $item['product_id']
                ? (float) (Product::find($item['product_id'])?->tax ?? $this->number($this->defaultTax, 0))
                : $this->number($this->defaultTax, 0);
        }

        return $this->calculate($item);
    }

    /**
     * Re-run the identifier ladder for one line, keeping the user's edits.
     *
     * Correcting the rate on an ambiguous line is the natural way to say which
     * variant it is, so the cost tie-break has to get a second chance — the
     * first pass only ever saw the rate the sheet shipped with.
     */
    private function rematch(array $item): array
    {
        if ($item['product_id'] || (! $item['raw_code'] && ! $item['raw_barcode'] && ! $item['raw_name'])) {
            return $item;
        }

        $catalogue = $this->lookupProducts([[
            'product_code' => $item['raw_code'],
            'barcode' => $item['raw_barcode'],
            'product_name' => $item['raw_name'],
        ]]);

        $order = match ($this->matchBy) {
            'code' => ['code'],
            'barcode' => ['barcode'],
            'name' => ['name'],
            default => ['code', 'barcode', 'name'],
        };

        foreach ($order as $key) {
            $needle = match ($key) {
                'code' => $item['raw_code'],
                'barcode' => $item['raw_barcode'],
                default => $item['raw_name'],
            };
            if (! $needle) {
                continue;
            }
            $hits = $catalogue[$key][strtolower((string) $needle)] ?? null;
            if ($hits && $hits->isNotEmpty()) {
                return $this->bindProduct($item, $hits, $key);
            }
        }

        if (in_array($this->matchBy, ['auto', 'name'], true) && $item['raw_name']) {
            $hits = Product::where('name', 'like', $this->escapeLike((string) $item['raw_name']).'%')
                ->limit(50)
                ->get($this->catalogueColumns());

            if ($hits->isNotEmpty()) {
                return $this->bindProduct($item, $hits, 'name~');
            }
        }

        return $item;
    }

    /** A matched line still has to carry a sane quantity and price. */
    private function applyValueChecks(array $item): array
    {
        if ($item['status'] !== 'ok') {
            return $item;
        }
        if ((float) $item['quantity'] <= 0) {
            $item['status'] = 'invalid';
            $item['message'] = 'Quantity must be greater than zero.';
        } elseif ((float) $item['unit_price'] <= 0) {
            $item['status'] = 'invalid';
            $item['message'] = 'Unit price is missing or zero.';
        }

        return $item;
    }

    private function calculate(array $item): array
    {
        $gross = (float) $item['unit_price'] * (float) $item['quantity'];
        $net = $gross - (float) $item['discount'];
        $taxAmount = $net * (float) $item['tax'] / 100;

        $item['gross_amount'] = round($gross, 2);
        $item['net_amount'] = round($net, 2);
        $item['tax_amount'] = round($taxAmount, 2);
        $item['total'] = round($net + $taxAmount, 2);

        return $item;
    }

    private function revalidate(array $item): array
    {
        if (! $item['product_id']) {
            // an ambiguous line keeps its own status and message: it is not
            // "no match", it is "too many matches", and it resolves differently.
            $item['status'] = $item['status'] === 'ambiguous' ? 'ambiguous' : 'unmatched';

            return $item;
        }
        if ((float) $item['quantity'] <= 0) {
            $item['status'] = 'invalid';
            $item['message'] = 'Quantity must be greater than zero.';

            return $item;
        }
        if ((float) $item['unit_price'] <= 0) {
            $item['status'] = 'invalid';
            $item['message'] = 'Unit price is missing or zero.';

            return $item;
        }
        $item['status'] = 'ok';
        $item['message'] = null;

        return $item;
    }

    private function number($value, $fallback = 0): float
    {
        if ($value === null || $value === '') {
            return (float) $fallback;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : (float) $fallback;
    }
}
