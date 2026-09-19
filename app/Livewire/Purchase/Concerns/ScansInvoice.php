<?php

namespace App\Livewire\Purchase\Concerns;

use App\Livewire\Concerns\MatchesInvoiceLines;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\PurchaseScanTemplate;
use App\Services\InvoiceScan\InvoiceScanException;
use App\Services\InvoiceScan\InvoiceScanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Fill the purchase cart from the vendor's own invoice.
 *
 * The vendor emails a PDF (or the delivery man hands over a paper copy that
 * gets photographed); this reads the item table out of it, finds each line in
 * the catalogue, and drops the ones it is sure about straight into the cart the
 * user is already looking at. Nothing is saved along the way — the cart is the
 * cart, and the ordinary Save Draft / Submit buttons still do the saving — so a
 * scan is never more committing than typing the lines by hand would have been.
 *
 * It lives on the purchase page itself rather than in a component of its own:
 * the Vue front end binds to whichever Livewire component is on the page, and a
 * second one would take that binding away from it.
 *
 * The sibling of this flow is /purchase/import, which does the same job from a
 * spreadsheet and saves a draft instead. Both share [[MatchesInvoiceLines]], so
 * a clipped description resolves the same way whichever door it came in.
 */
trait ScansInvoice
{
    use MatchesInvoiceLines;

    public bool $scanOpen = false;

    /** 1 = upload, 2 = map the columns, 3 = review what was found. */
    public int $scanStep = 1;

    public $scanFile;

    public string $scanFileName = '';

    /** 'pdf' or 'ocr' — a template measured on one is no use on the other. */
    public string $scanSource = 'pdf';

    /** Column headings, when the invoice prints them as text. */
    public array $scanColumns = [];

    /** Where each column sits, so the layout can be remembered for this vendor. */
    public array $scanBands = [];

    /** The item table exactly as it was read, one array of cells per row. */
    public array $scanRows = [];

    /** field => column index. */
    public array $scanMapping = [];

    /**
     * The column holding each line's printed total, if the invoice has one.
     *
     * Not an import field — nothing is taken from it. It is the check digit for
     * the whole scan: quantity × rate less discount plus tax has to come back
     * to what the vendor printed, and on a photographed invoice that is what
     * catches a misread figure OCR was perfectly confident about.
     */
    public string $scanTotalColumn = '';

    public string $scanSearch = '';

    public array $scanResults = [];

    public ?int $scanResolvingIndex = null;

    /** Row index => column indexes OCR was unsure of. */
    public array $scanLowConfidence = [];

    /** The resolved lines shown for review. */
    public array $scanItems = [];

    /** Line indexes the user wants in the cart. */
    public array $scanSelected = [];

    public bool $scanRemember = true;

    public ?int $scanTemplateId = null;

    public bool $scanTemplateApplied = false;

    public string $scanError = '';

    public int $scanSkipped = 0;

    public int $scanPages = 1;

    /* --------------------------------------- what MatchesInvoiceLines needs -- */

    public string $matchBy = 'auto';

    public $defaultTax = 0;

    /* ------------------------------------------------------------- opening -- */

    public function openInvoiceScan(): void
    {
        abort_unless(Auth::user()?->can('purchase.scan invoice'), 403);

        $this->resetInvoiceScan();
        $this->scanOpen = true;
    }

    public function closeInvoiceScan(): void
    {
        $this->scanOpen = false;
        $this->resetInvoiceScan();
    }

    private function resetInvoiceScan(): void
    {
        $this->reset([
            'scanStep', 'scanFile', 'scanFileName', 'scanColumns', 'scanBands', 'scanRows',
            'scanMapping', 'scanTotalColumn', 'scanLowConfidence', 'scanItems', 'scanSelected',
            'scanTemplateId', 'scanTemplateApplied', 'scanError', 'scanSkipped', 'scanPages',
            'scanSearch', 'scanResults', 'scanResolvingIndex',
        ]);

        $this->scanMapping = array_fill_keys(array_keys($this->fields), '');
    }

    /* ------------------------------------------------------ step 1: upload -- */

    public function updatedScanFile(): void
    {
        abort_unless(Auth::user()?->can('purchase.scan invoice'), 403);

        $this->scanError = '';

        // 12MB is Livewire's own ceiling for a temporary upload — asking for
        // more here only moves the rejection somewhere less explicable.
        $this->validate([
            'scanFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,bmp,tif,tiff', 'max:12288'],
        ], [], ['scanFile' => 'invoice']);

        $extension = strtolower($this->scanFile->getClientOriginalExtension() ?: $this->scanFile->extension());
        $path = $this->scanFile->getRealPath();
        $copied = null;

        // node and tesseract read a path on this machine; a temporary upload
        // kept on a remote disk has none, so fetch it down first.
        if (! $path || ! is_readable($path)) {
            $copied = tempnam(sys_get_temp_dir(), 'invoice-scan-').'.'.$extension;
            file_put_contents($copied, $this->scanFile->get());
            $path = $copied;
        }

        try {
            $table = app(InvoiceScanner::class)->scan($path, $extension);
        } catch (InvoiceScanException $exception) {
            $this->scanError = $exception->getMessage();
            $this->reset(['scanFile', 'scanFileName']);

            return;
        } catch (\Throwable $exception) {
            Log::error('Invoice scan failed', ['message' => $exception->getMessage()]);
            $this->scanError = 'The invoice could not be read. Please try another file.';
            $this->reset(['scanFile', 'scanFileName']);

            return;
        } finally {
            if ($copied) {
                @unlink($copied);
            }
        }

        $this->scanFileName = $this->scanFile->getClientOriginalName();
        $this->scanColumns = $table['columns'];
        $this->scanBands = $table['bands'];
        $this->scanRows = $table['rows'];
        $this->scanLowConfidence = $table['low_confidence'] ?? [];
        $this->scanSource = $table['source'];
        $this->scanSkipped = $table['skipped'];
        $this->scanPages = $table['pageCount'];

        if ($this->applyVendorTemplate()) {
            $this->resolveScannedRows();

            return;
        }

        $this->autoMapScan();
    }

    /**
     * Use the layout saved for this vendor, when it still fits.
     *
     * This is what makes the second invoice from a vendor a two-click job: the
     * columns are already named, so the upload goes straight to the review.
     */
    private function applyVendorTemplate(): bool
    {
        $vendor = (int) ($this->purchases['account_id'] ?? 0);

        if (! $vendor) {
            return false;
        }

        $template = PurchaseScanTemplate::where('account_id', $vendor)
            ->where('source', $this->scanSource)
            ->first();

        if (! $template || ! $template->fits($this->scanBands)) {
            return false;
        }

        $mapping = $template->mapping;
        $this->scanTotalColumn = (string) ($mapping['__total'] ?? '');
        unset($mapping['__total']);

        $this->scanMapping = array_merge(array_fill_keys(array_keys($this->fields), ''), $mapping);
        $this->matchBy = $template->match_by;
        $this->defaultTax = $template->default_tax;
        $this->scanTemplateId = $template->id;
        $this->scanTemplateApplied = true;

        return true;
    }

    /** Guess a column for every field from the headings, when there are any. */
    private function autoMapScan(): void
    {
        $normalised = [];
        foreach ($this->scanColumns as $index => $label) {
            $normalised[$index] = preg_replace('/[^a-z0-9]/', '', strtolower($label));
        }

        $taken = [];
        foreach ($this->fields as $field => $meta) {
            $match = '';
            foreach ($meta['aliases'] as $alias) {
                foreach ($normalised as $index => $value) {
                    if (in_array($index, $taken, true) || $value === '' || str_starts_with($value, 'column')) {
                        continue;
                    }
                    if ($value === $alias || str_contains($value, $alias)) {
                        $match = $index;
                        break 2;
                    }
                }
            }
            $this->scanMapping[$field] = $match === '' ? '' : (string) $match;
            if ($match !== '') {
                $taken[] = $match;
            }
        }

        // The printed line total is almost always the last money column, and
        // guessing it costs nothing — it is only ever used to check arithmetic.
        $numeric = array_keys(array_filter($this->scanBands, fn ($band) => $band['numeric']));
        $last = end($numeric);
        $this->scanTotalColumn = $last === false || in_array((string) $last, $taken, true) ? '' : (string) $last;

        $this->scanStep = 2;
    }

    /* ------------------------------------------------------ step 2: mapping -- */

    /** A few rows of the file, to show under the column pickers. */
    public function getScanPreviewProperty(): array
    {
        return array_slice($this->scanRows, 0, 4);
    }

    public function resolveScannedRows(): void
    {
        if (($this->scanMapping['product_name'] ?? '') === '' && ($this->scanMapping['product_code'] ?? '') === '' && ($this->scanMapping['barcode'] ?? '') === '') {
            $this->scanError = 'Point at least one column at the product — its name, its code or its barcode.';

            return;
        }

        $this->scanError = '';

        $rows = [];
        foreach ($this->scanRows as $offset => $row) {
            $values = [];
            foreach ($this->scanMapping as $field => $column) {
                $values[$field] = $column === '' || $column === null ? null : trim((string) ($row[(int) $column] ?? ''));
            }

            if (collect($values)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $rows[] = $values + ['__line' => $offset + 1];
        }

        $catalogue = $this->lookupProducts($rows);

        $items = [];
        foreach ($rows as $values) {
            $items[] = $this->makeItem($values, $catalogue);
        }

        $this->applyPartialNameMatches($items);

        foreach ($items as $index => $item) {
            $item = $this->applyValueChecks($this->calculate($item));
            $item['uncertain'] = $this->uncertain($item, $index);
            $items[$index] = $item;
        }

        $this->scanItems = $items;
        // Everything that matched cleanly is ticked; anything the user still
        // has to look at stays unticked, so "Add to cart" is never a surprise.
        $this->scanSelected = array_keys(array_filter($items, fn ($item) => $item['status'] === 'ok'));
        $this->scanStep = 3;
    }

    /**
     * Should this line be looked at before it is trusted?
     *
     * Two different doubts, both worth the same yellow flag: OCR told us it was
     * unsure of a figure, or the line's own arithmetic does not tie out. The
     * second is the useful one on a scan — a misread quantity or rate shows up
     * as qty × rate no longer making the printed line total, which catches the
     * digit errors OCR is confident about and wrong.
     */
    private function uncertain(array $item, int $index): ?string
    {
        if (isset($this->scanLowConfidence[$index])) {
            return 'Some figures on this line were hard to read.';
        }

        if ($this->scanTotalColumn === '') {
            return null;
        }

        $printed = $this->number($this->scanRows[$item['line'] - 1][(int) $this->scanTotalColumn] ?? null, 0);

        if ($printed <= 0 || $item['total'] <= 0) {
            return null;
        }

        return abs($printed - $item['total']) > max(0.05, $printed * 0.01)
            ? 'The figures do not add up to the '.number_format($printed, 2).' printed on this line.'
            : null;
    }

    /* ------------------------------------------------------- step 3: review -- */

    public function toggleScanLine(int $index): void
    {
        if (in_array($index, $this->scanSelected, true)) {
            $this->scanSelected = array_values(array_diff($this->scanSelected, [$index]));

            return;
        }

        if (($this->scanItems[$index]['product_id'] ?? null)) {
            $this->scanSelected[] = $index;
        }
    }

    public function toggleAllScanLines(): void
    {
        $ready = array_keys(array_filter($this->scanItems, fn ($item) => $item['product_id']));

        $this->scanSelected = count($this->scanSelected) === count($ready) ? [] : $ready;
    }

    /** Pick one of the candidates offered for an ambiguous line. */
    public function chooseScanCandidate(int $index, int $productId): void
    {
        $item = $this->scanItems[$index] ?? null;
        $product = $item ? Product::find($productId) : null;

        if (! $item || ! $product) {
            return;
        }

        $this->scanItems[$index] = $this->applyValueChecks($this->calculate(
            $this->bindProduct($item, collect([$product]), $item['matched_on'] ?? 'name')
        ));

        if (! in_array($index, $this->scanSelected, true)) {
            $this->scanSelected[] = $index;
        }
    }

    public function resolveScanLine(?int $index): void
    {
        $this->scanResolvingIndex = $index;
        $this->scanSearch = $index === null ? '' : (string) ($this->scanItems[$index]['raw_name'] ?? '');
        $this->scanResults = [];

        if ($index !== null) {
            $this->searchScanProducts();
        }
    }

    public function searchScanProducts(): void
    {
        $term = trim($this->scanSearch);

        if (mb_strlen($term) < 2) {
            $this->scanResults = [];

            return;
        }

        $this->scanResults = Product::where(function ($query) use ($term): void {
            $query->where('name', 'like', '%'.$this->escapeLike($term).'%')
                ->orWhere('code', $term)
                ->orWhere('barcode', $term);
        })
            ->limit(15)
            ->get(['id', 'name', 'code', 'barcode', 'cost'])
            ->toArray();
    }

    /** Attach the product the user picked out of the search to a line. */
    public function bindScanProduct(int $index, int $productId): void
    {
        $this->chooseScanCandidate($index, $productId);

        $this->scanResolvingIndex = null;
        $this->scanSearch = '';
        $this->scanResults = [];
    }

    /* --------------------------------------------------------- into the cart -- */

    public function addScannedToCart(): void
    {
        abort_unless(Auth::user()?->can('purchase.scan invoice'), 403);

        $lines = array_filter(
            $this->scanItems,
            fn ($item, $index) => in_array($index, $this->scanSelected, true) && $item['product_id'],
            ARRAY_FILTER_USE_BOTH
        );

        if ($lines === []) {
            $this->dispatch('error', ['message' => 'Tick at least one line to add.']);

            return;
        }

        $products = Product::whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');
        $added = 0;

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);

            if (! $product) {
                continue;
            }

            $this->addScannedLine($product, $line);
            $added++;
        }

        $this->mainCalculator();
        $this->rememberVendorTemplate();

        $this->scanOpen = false;
        $this->resetInvoiceScan();

        $this->dispatch('success', ['message' => $added.' '.str('item')->plural($added).' added from the invoice']);
        $this->dispatch('livewire-data-updated');
    }

    /**
     * Put one scanned line in the cart at the invoice's own figures.
     *
     * addToCart() is the cart's own door — it resolves the product's units and
     * honours the merge-duplicates setting — but it fills a new line with the
     * catalogue cost and the default quantity. The invoice is what is being
     * entered, so those are then replaced by what the vendor actually billed,
     * allowing for the row having been merged into a line that was already
     * there.
     */
    private function addScannedLine(Product $product, array $line): void
    {
        $default = (float) (Configuration::where('key', 'purchase_default_quantity')->value('value') ?? '1');

        $key = $this->addToCart($product);

        $this->items[$key]['quantity'] = round(((float) $this->items[$key]['quantity']) - $default + (float) $line['quantity'], 3);
        $this->items[$key]['discount'] = round(((float) $this->items[$key]['discount']) + (float) $line['discount'], 2);
        $this->items[$key]['unit_price'] = (float) $line['unit_price'];
        $this->items[$key]['tax'] = (float) $line['tax'];

        $this->singleCartCalculator($key);
    }

    /** Keep this layout for the vendor, so their next invoice needs no mapping. */
    private function rememberVendorTemplate(): void
    {
        $vendor = (int) ($this->purchases['account_id'] ?? 0);

        if (! $this->scanRemember || ! $vendor || $this->scanBands === []) {
            return;
        }

        $mapping = array_filter($this->scanMapping, fn ($column) => $column !== '');

        if ($this->scanTotalColumn !== '') {
            $mapping['__total'] = $this->scanTotalColumn;
        }

        $template = PurchaseScanTemplate::firstOrNew([
            'account_id' => $vendor,
            'source' => $this->scanSource,
        ]);

        $template->fill([
            'bands' => $this->scanBands,
            'mapping' => $mapping,
            'match_by' => $this->matchBy,
            'default_tax' => $this->number($this->defaultTax, 0),
            'last_used_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $template->used_count = (int) $template->used_count + 1;
        $template->created_by ??= Auth::id();
        $template->save();
    }
}
