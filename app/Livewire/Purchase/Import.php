<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\CreateAction;
use App\Exports\Templates\PurchaseInvoiceImportTemplate;
use App\Imports\RawSheetImport;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Purchase invoice uploader.
 *
 * The vendor, date and invoice number are typed by hand; the item lines come
 * from the vendor's own spreadsheet. Columns are mapped on screen, every line
 * is resolved against the catalogue and can be corrected, and the result is
 * saved as a draft purchase the user can still review before completing it.
 */
class Import extends Component
{
    use WithFileUploads;

    /** Hard ceiling on the lines pulled out of one sheet. */
    public const MAX_ROWS = 500;

    public int $step = 1;

    /* ---------------------------------------------------------- invoice --- */

    public $account_id;

    public $vendor_name;

    public $vendor_balance;

    public $date;

    public $delivery_date;

    public $invoice_no;

    public $address;

    public $other_discount = 0;

    public $freight = 0;

    /* ------------------------------------------------------------- file --- */

    public $file;

    public $fileName;

    /** Header labels detected in the sheet, keyed by column index. */
    public array $columns = [];

    /** First few data rows, for the on screen file preview. */
    public array $previewRows = [];

    /** Number of data rows parsed out of the sheet (the rows themselves are cached). */
    public int $rowCount = 0;

    /** field => column index ('' when the field is not present in the file). */
    public array $mapping = [];

    public bool $truncated = false;

    /* ---------------------------------------------------------- options --- */

    public string $matchBy = 'auto';

    public $defaultTax = 0;

    public bool $skipUnmatched = true;

    /* ----------------------------------------------------------- review --- */

    /**
     * The lines of the CURRENT PAGE only, keyed by their index in the full set.
     *
     * The full set lives in the cache: Livewire ships every public property on
     * every round-trip, and a 400 line invoice serialised ~180KB each way,
     * which cost the better part of a second per keystroke. Paging the wire
     * state keeps an edit flat and fast however long the invoice is.
     */
    public array $items = [];

    /** Token for this upload's cached working set. */
    public string $sheetToken = '';

    public int $page = 1;

    /**
     * Indexes of the lines on screen.
     *
     * Held across edits on purpose: re-filtering after every keystroke would
     * make a row you just fixed disappear from under the cursor. The page is
     * re-cut only when the filter, the page or the line set itself changes.
     */
    public array $pageIndexes = [];

    public int $perPage = 50;

    public int $lineCount = 0;

    public int $readyCount = 0;

    public int $issueCount = 0;

    /** True when the cached working set is gone (expired or cleared). */
    public bool $expired = false;

    public string $rowFilter = 'all';

    public array $totals = [];

    /** Row currently open in the resolve panel. */
    public $resolvingIndex = null;

    public string $productSearch = '';

    public array $productResults = [];

    public string $rowMode = 'merge';

    public int $mergedRows = 0;

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

    public function mount(): void
    {
        $this->sheetToken = (string) Str::uuid();
        $this->date = date('Y-m-d');
        $this->delivery_date = date('Y-m-d');
        $this->rowMode = Configuration::where('key', 'purchase_item_row_mode')->value('value') ?? 'merge';
        $this->mapping = array_fill_keys(array_keys($this->fields), '');
        $this->recalculateTotals();
    }

    /* ================================================== step 1 — invoice == */

    public function updatedAccountId($value): void
    {
        $account = Account::find($value);
        $this->vendor_name = $account?->name;
        $this->vendor_balance = $account ? $account->ledger()->latest('id')->value('balance') : null;
    }

    public function goToUpload(): void
    {
        $this->validate([
            'account_id' => ['required'],
            'date' => ['required', 'date'],
            'invoice_no' => ['required', 'string', 'max:191'],
            'delivery_date' => ['nullable', 'date'],
        ], [], [
            'account_id' => 'vendor',
            'invoice_no' => 'invoice no',
        ]);

        $this->step = 2;
    }

    /* =================================================== step 2 — upload == */

    /**
     * Named `downloadTemplate`, not `sample`: Livewire's $wire proxy resolves a
     * component's state before its methods, so a method sharing a name with a
     * public property is unreachable from wire:click.
     */
    public function downloadTemplate()
    {
        return Excel::download(new PurchaseInvoiceImportTemplate(), 'purchase_invoice_items.xlsx');
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $this->readSheet();
    }

    public function removeFile(): void
    {
        Cache::forget($this->cacheKey('rows'));
        $this->reset(['file', 'fileName', 'columns', 'previewRows', 'rowCount', 'truncated']);
        $this->mapping = array_fill_keys(array_keys($this->fields), '');
    }

    private function readSheet(): void
    {
        $rows = (new RawSheetImport(self::MAX_ROWS))->read($this->file);

        $rows = array_values(array_filter($rows, fn ($row) => collect($row)->filter(fn ($cell) => $cell !== null && $cell !== '')->isNotEmpty()));

        if (count($rows) < 2) {
            $this->addError('file', 'The sheet needs a header row and at least one item row.');
            $this->removeFile();

            return;
        }

        $header = array_shift($rows);
        $this->columns = [];
        foreach ($header as $index => $label) {
            $label = trim((string) $label);
            $this->columns[$index] = $label !== '' ? $label : 'Column '.($index + 1);
        }

        $this->truncated = count($rows) >= self::MAX_ROWS;
        $this->putRawRows($rows);
        $this->previewRows = array_slice(array_values($rows), 0, 5);
        $this->fileName = $this->file->getClientOriginalName();
        $this->autoMap();
    }

    /** Guess a column for every field from the header labels. */
    private function autoMap(): void
    {
        $normalised = [];
        foreach ($this->columns as $index => $label) {
            $normalised[$index] = preg_replace('/[^a-z0-9]/', '', strtolower($label));
        }

        $taken = [];
        foreach ($this->fields as $field => $meta) {
            $match = '';
            foreach ($meta['aliases'] as $alias) {
                foreach ($normalised as $index => $value) {
                    if (in_array($index, $taken, true) || $value === '') {
                        continue;
                    }
                    if ($value === $alias) {
                        $match = $index;
                        break 2;
                    }
                }
            }
            if ($match === '') {
                foreach ($meta['aliases'] as $alias) {
                    foreach ($normalised as $index => $value) {
                        if (in_array($index, $taken, true) || $value === '') {
                            continue;
                        }
                        if (str_contains($value, $alias)) {
                            $match = $index;
                            break 2;
                        }
                    }
                }
            }
            $this->mapping[$field] = $match === '' ? '' : (string) $match;
            if ($match !== '') {
                $taken[] = $match;
            }
        }
    }

    /* ------------------------------------------------- cached working set --- */

    private function cacheKey(string $bucket): string
    {
        return "purchase-import:{$bucket}:".Auth::id().":{$this->sheetToken}";
    }

    /** Every resolved line, in order. Empty once the working set has expired. */
    private function lines(): array
    {
        return Cache::get($this->cacheKey('lines'), []);
    }

    /**
     * Persist the working set and refresh everything the browser needs to see:
     * the counts, the totals and the current page of rows.
     */
    private function putLines(array $lines): void
    {
        $lines = array_values($lines);
        Cache::put($this->cacheKey('lines'), $lines, now()->addHours(2));

        $this->lineCount = count($lines);
        $this->readyCount = count(array_filter($lines, fn ($item) => $item['status'] === 'ok'));
        $this->issueCount = $this->lineCount - $this->readyCount;
        $this->recalculateTotals($lines);
        $this->showPage($lines);
    }

    private function rawRows(): array
    {
        return Cache::get($this->cacheKey('rows'), []);
    }

    private function putRawRows(array $rows): void
    {
        Cache::put($this->cacheKey('rows'), array_values($rows), now()->addHours(2));
        $this->rowCount = count($rows);
    }

    /** Re-cut the page from the filter, then load it. */
    private function refreshPage(?array $lines = null): void
    {
        $lines ??= $this->lines();

        $filtered = match ($this->rowFilter) {
            'issues' => array_filter($lines, fn ($item) => $item['status'] !== 'ok'),
            'ready' => array_filter($lines, fn ($item) => $item['status'] === 'ok'),
            default => $lines,
        };

        $this->page = max(1, min($this->page, (int) max(1, ceil(count($filtered) / $this->perPage))));
        $this->pageIndexes = array_keys(array_slice($filtered, ($this->page - 1) * $this->perPage, $this->perPage, true));

        $this->showPage($lines);
    }

    /** Load the rows the page already points at, keyed by their true index. */
    private function showPage(?array $lines = null): void
    {
        $lines ??= $this->lines();

        $this->expired = $lines === [] && $this->lineCount > 0;

        $this->items = [];
        foreach ($this->pageIndexes as $index) {
            if (isset($lines[$index])) {
                $this->items[$index] = $lines[$index];
            }
        }
    }

    /** Rewrite one line in the working set. */
    private function putLine(int $index, array $line): void
    {
        $lines = $this->lines();
        if (! isset($lines[$index])) {
            return;
        }
        $lines[$index] = $line;
        $this->putLines($lines);
    }

    public function getPageCountProperty(): int
    {
        $total = match ($this->rowFilter) {
            'issues' => $this->issueCount,
            'ready' => $this->readyCount,
            default => $this->lineCount,
        };

        return (int) max(1, ceil($total / $this->perPage));
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->refreshPage();
    }

    public function updatedRowFilter(): void
    {
        $this->page = 1;
        $this->refreshPage();
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->refreshPage();
    }

    public function getMappedCountProperty(): int
    {
        return count(array_filter($this->mapping, fn ($value) => $value !== '' && $value !== null));
    }

    public function buildRows(): void
    {
        $hasIdentity = collect(['product_code', 'barcode', 'product_name'])
            ->contains(fn ($field) => ($this->mapping[$field] ?? '') !== '');

        if (! $hasIdentity) {
            $this->addError('mapping', 'Map at least one of Product Code, Barcode or Product Name so the lines can be matched.');

            return;
        }
        if (($this->mapping['unit_price'] ?? '') === '') {
            $this->addError('mapping', 'Map the Unit Price column — a purchase line cannot be priced without it.');

            return;
        }

        $this->resetErrorBag('mapping');

        $lines = $this->mergeDuplicateRows($this->resolveRows());

        $this->rowFilter = collect($lines)->contains(fn ($item) => $item['status'] !== 'ok') ? 'issues' : 'all';
        $this->page = 1;
        $this->putLines($lines);
        $this->refreshPage($lines);
        $this->step = 3;
    }

    /** Turn every raw sheet row into a resolved purchase line. */
    private function resolveRows(): array
    {
        $rows = [];
        foreach ($this->rawRows() as $offset => $row) {
            $values = [];
            foreach ($this->mapping as $field => $column) {
                $values[$field] = $column === '' || $column === null ? null : ($row[(int) $column] ?? null);
                if (is_string($values[$field])) {
                    $values[$field] = trim($values[$field]);
                }
            }
            if (collect($values)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }
            $rows[] = $values + ['__line' => $offset + 2];
        }

        $catalogue = $this->lookupProducts($rows);

        $items = [];
        foreach ($rows as $values) {
            $items[] = $this->makeItem($values, $catalogue);
        }

        $this->applyPartialNameMatches($items);

        foreach ($items as $index => $item) {
            $items[$index] = $this->applyValueChecks($this->calculate($item));
        }

        return $items;
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

    /** Fold repeated products into one line when the settings ask for merged rows. */
    private function mergeDuplicateRows(array $lines): array
    {
        $this->mergedRows = 0;
        if ($this->rowMode === 'separate') {
            return $lines;
        }

        $merged = [];
        $seen = [];
        foreach ($lines as $item) {
            $key = $item['product_id'];
            if ($key && isset($seen[$key])) {
                $target = $seen[$key];
                $merged[$target]['quantity'] = round($merged[$target]['quantity'] + $item['quantity'], 3);
                $merged[$target]['discount'] = round($merged[$target]['discount'] + $item['discount'], 2);
                $merged[$target]['merged_lines'][] = $item['line'];
                $merged[$target] = $this->calculate($merged[$target]);
                $this->mergedRows++;

                continue;
            }
            $item['merged_lines'] = [];
            $merged[] = $item;
            if ($key) {
                $seen[$key] = array_key_last($merged);
            }
        }

        return array_values($merged);
    }

    /* =================================================== step 3 — review == */

    public function updated($key): void
    {
        if (preg_match('/^items\.(\d+)\.(quantity|unit_price|discount|tax|batch)$/', $key, $matches)) {
            $index = (int) $matches[1];
            $field = $matches[2];
            $line = $this->items[$index] ?? null;
            if (! $line) {
                return;
            }
            if ($field !== 'batch' && ! is_numeric($line[$field])) {
                $line[$field] = 0;
            }
            $line = $this->calculate($line);
            if ($field === 'unit_price' && ! $line['product_id']) {
                $line = $this->calculate($this->rematch($line));
            }
            $this->putLine($index, $this->revalidate($line));
        }
        if (in_array($key, ['other_discount', 'freight'], true)) {
            if (! is_numeric($this->{$key})) {
                $this->{$key} = 0;
            }
            $this->recalculateTotals();
        }
    }

    public function removeItem(int $index): void
    {
        $lines = $this->lines();
        unset($lines[$index]);
        $this->putLines($lines);
        $this->refreshPage();
    }

    public function dropUnmatched(): void
    {
        $this->rowFilter = 'all';
        $this->page = 1;
        $this->putLines(array_filter($this->lines(), fn ($item) => $item['status'] === 'ok'));
        $this->refreshPage();
    }

    public function openResolve(int $index): void
    {
        $line = $this->lines()[$index] ?? null;
        if (! $line) {
            return;
        }
        $this->resolvingIndex = $index;
        $this->productSearch = (string) ($line['raw_name'] ?? $line['raw_code'] ?? '');
        $this->searchProducts();
    }

    public function closeResolve(): void
    {
        $this->reset(['resolvingIndex', 'productSearch', 'productResults']);
    }

    public function updatedProductSearch(): void
    {
        $this->searchProducts();
    }

    public function searchProducts(): void
    {
        $term = trim($this->productSearch);
        if (strlen($term) < 2) {
            $this->productResults = [];

            return;
        }

        $like = $this->escapeLike($term);
        $columns = ['id', 'name', 'code', 'barcode', 'cost', 'unit_id', 'tax', 'expense_account_id'];

        // Prefix first: products is indexed on (tenant_id, name), (tenant_id,
        // code) and (tenant_id, barcode), and only a trailing wildcard can use
        // them. The unindexed "contains" scan runs only if that came up short.
        $hits = Product::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "{$like}%")
                ->orWhere('code', 'like', "{$like}%")
                ->orWhere('barcode', 'like', "{$like}%"))
            ->limit(12)
            ->get($columns);

        if ($hits->count() < 12) {
            $found = $hits->pluck('id')->all();
            $hits = $hits->merge(
                Product::query()
                    ->whereNotIn('id', $found ?: [0])
                    ->where(fn ($query) => $query
                        ->where('name', 'like', "%{$like}%")
                        ->orWhere('code', 'like', "%{$like}%"))
                    ->limit(12 - $hits->count())
                    ->get($columns)
            );
        }

        $this->productResults = $hits->values()->toArray();
    }

    public function assignProduct(int $productId): void
    {
        $index = $this->resolvingIndex;
        $lines = $this->lines();
        if ($index === null || ! isset($lines[$index])) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $line = $lines[$index];
        $line['product_id'] = $product->id;
        $line['name'] = $product->name;
        $line['code'] = $product->code;
        $line['barcode'] = $product->barcode;
        $line['unit_id'] = $product->unit_id;
        $line['account_id'] = $product->expense_account_id;
        $line['matched_on'] = 'manual';
        $line['by_cost'] = false;
        $line['candidates'] = [];
        $line['candidate_count'] = 0;
        $line['product_cost'] = (float) $product->cost;
        if (! $line['unit_price']) {
            $line['unit_price'] = (float) $product->cost;
        }

        $lines[$index] = $this->revalidate($this->calculate($line));
        $this->putLines($lines);
        $this->closeResolve();
        $this->dispatch('success', ['message' => 'Line matched to '.$product->name]);
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

    /** Pick one of an ambiguous line's candidates without opening the overlay. */
    public function chooseCandidate(int $index, int $productId): void
    {
        if (! isset($this->lines()[$index])) {
            return;
        }

        $this->resolvingIndex = $index;
        $this->assignProduct($productId);
    }

    /** The page already holds exactly the rows to draw. */
    public function getVisibleItemsProperty(): array
    {
        return $this->items;
    }

    /* ======================================================= step 4 — save */

    public function save()
    {
        abort_unless(auth()->user()?->can('purchase.create'), 403);

        $this->validate([
            'account_id' => ['required'],
            'date' => ['required', 'date'],
            'invoice_no' => ['required', 'string', 'max:191'],
        ], [], ['account_id' => 'vendor', 'invoice_no' => 'invoice no']);

        $all = $this->lines();
        $lines = array_filter($all, fn ($item) => $item['status'] === 'ok');

        if (! count($all)) {
            $this->dispatch('error', ['message' => 'This upload has expired. Re-run the mapping step.']);

            return;
        }

        if (! $this->skipUnmatched && $this->issueCount) {
            $this->dispatch('error', ['message' => 'Resolve the '.$this->issueCount.' flagged line(s), or switch on "Skip unresolved lines".']);

            return;
        }
        if (! count($lines)) {
            $this->dispatch('error', ['message' => 'There is no valid line to import.']);

            return;
        }

        try {
            DB::beginTransaction();

            $data = [
                'invoice_no' => $this->invoice_no,
                'account_id' => $this->account_id,
                'branch_id' => session('branch_id'),
                'date' => $this->date,
                'delivery_date' => $this->delivery_date ?: $this->date,
                'address' => $this->address,
                // the purchase screen sends its own header aggregates; `total`
                // in particular is not derived by CreateAction, and grand_total
                // is generated from it.
                'gross_amount' => $this->totals['gross_amount'],
                'item_discount' => $this->totals['item_discount'],
                'tax_amount' => $this->totals['tax_amount'],
                'total' => $this->totals['total'],
                'other_discount' => $this->number($this->other_discount, 0),
                'freight' => $this->number($this->freight, 0),
                'status' => 'draft',
                'items' => array_map(fn ($item) => [
                    'product_id' => $item['product_id'],
                    'account_id' => $item['account_id'],
                    'unit_id' => $item['unit_id'] ?: 1,
                    'conversion_factor' => 1,
                    'batch' => $item['batch'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'],
                    'tax' => $item['tax'],
                ], array_values($lines)),
                'payments' => [],
            ];

            $response = (new CreateAction())->execute($data, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();

            Cache::forget($this->cacheKey('lines'));
            Cache::forget($this->cacheKey('rows'));

            session()->flash('success', count($lines).' line(s) imported into draft purchase '.$this->invoice_no);

            return redirect()->route('purchase::edit', $response['data']['id']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /* ---------------------------------------------------------- helpers --- */

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

    private function recalculateTotals(?array $all = null): void
    {
        $all ??= $this->lines();
        $lines = collect($all)->where('status', 'ok');

        $total = round($lines->sum('total'), 2);
        $this->totals = [
            'lines' => count($all),
            'quantity' => round($lines->sum('quantity'), 3),
            'gross_amount' => round($lines->sum('gross_amount'), 2),
            'item_discount' => round($lines->sum('discount'), 2),
            'tax_amount' => round($lines->sum('tax_amount'), 2),
            'total' => $total,
            'grand_total' => round($total - $this->number($this->other_discount, 0) + $this->number($this->freight, 0), 2),
        ];
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

    public function render()
    {
        return view('livewire.purchase.import');
    }
}
