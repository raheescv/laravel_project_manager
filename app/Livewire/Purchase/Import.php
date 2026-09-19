<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\CreateAction;
use App\Exports\Templates\PurchaseInvoiceImportTemplate;
use App\Imports\RawSheetImport;
use App\Livewire\Concerns\MatchesInvoiceLines;
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
    use MatchesInvoiceLines;
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

    public function render()
    {
        return view('livewire.purchase.import');
    }
}
