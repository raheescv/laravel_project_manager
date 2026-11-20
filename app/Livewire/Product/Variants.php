<?php

namespace App\Livewire\Product;

use App\Actions\Product\UpdateAction;
use App\Actions\Product\Variant\CreateVariantsAction;
use App\Models\Product;
use App\Support\ProductColours;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The product page's "Variants" tab ("Swatch Board"): pick the sizes and the
 * (optional) colours a product comes in, tick the combinations actually stocked,
 * then create each one as its own product. Existing family members show locked.
 *
 * Cells are addressed by an opaque key (see cellKey()) because sizes such as
 * "1.5" or "S/M" would break Livewire's dot-separated wire:model paths.
 */
class Variants extends Component
{
    /** How many suggestion chips the "Quick add" card shows. */
    private const QUICK_ADD = 7;

    public int $product_id;

    public bool $isVariant = false;

    /** @var array{id: int, name: string, code: ?string, size: string, colour: string} */
    public array $head = [];

    /** @var array<int, array{id: int, label: string, colour: string, current: bool}> family members, for a variant's page */
    public array $siblings = [];

    /** @var array<int, string> */
    public array $sizes = [];

    /** @var array<int, string> '' is "No colour" */
    public array $colours = [];

    /** @var array<string, string> colour name => hex */
    public array $hex = [];

    /** @var array<string, array{size: string, colour: string}> */
    public array $cells = [];

    /** @var array<string, array{id: int, barcode_number: string, cost: string, mrp: string}> cells that already exist as products, as saved */
    public array $existing = [];

    /** @var array<string, array{barcode_number: string, cost: string, mrp: string}> the table's editable copy of */
    public array $edits = [];

    /** @var array<string, array{barcode_number: string, cost: string, mrp: string, opening_stock: string}> newly ticked cells */
    public array $picked = [];

    public string $newSize = '';

    public string $baseSize = '';

    /** @var array{cost: string, mrp: string, opening_stock: string} */
    public array $bulk = ['cost' => '', 'mrp' => '', 'opening_stock' => ''];

    /** The price table is grouped by 'colour' or by 'size'; each group can share one price. */
    public string $groupBy = 'colour';

    /** @var array<string, array{cost?: string, mrp?: string, opening_stock?: string}> group key => values to apply */
    public array $groupBulk = [];

    public function mount(int $product_id): void
    {
        $this->product_id = $product_id;
        $this->load();
    }

    public function load(): void
    {
        $product = Product::find($this->product_id);
        if (! $product) {
            return;
        }

        $this->isVariant = $product->isVariant();
        $head = $this->isVariant ? $product->parent : $product;
        $this->head = [
            'id' => $head->id,
            'name' => (string) $head->name,
            'code' => $head->code,
            'size' => trim((string) $head->size),
            'colour' => trim((string) $head->color),
        ];
        $family = Product::query()
            ->where(fn ($query) => $query->where('id', $head->id)->orWhere('parent_id', $head->id))
            ->orderByRaw('parent_id IS NOT NULL')->orderBy('id')
            ->get(['id', 'parent_id', 'size', 'color', 'barcode', 'barcode_number', 'cost', 'mrp']);

        if ($this->isVariant) {
            $this->siblings = $family->map(fn (Product $member) => [
                'id' => $member->id,
                'label' => Product::variantLabel($member->size, $member->color) ?: $member->name,
                'colour' => trim((string) $member->color),
                'current' => $member->id === $product->id,
            ])->all();

            return;
        }

        $this->hex = ProductColours::palette();
        $this->sizes = [];
        $this->colours = [];
        $this->cells = [];
        $this->existing = [];
        $this->edits = [];
        $this->picked = [];
        $this->bulk = ['cost' => (string) (float) $head->cost, 'mrp' => (string) (float) $head->mrp, 'opening_stock' => ''];

        foreach ($family as $member) {
            $size = trim((string) $member->size);
            if ($size === '') {
                continue;
            }
            $colour = trim((string) $member->color);
            $this->rememberSize($size);
            $this->rememberColour($colour);
            $key = $this->registerCell($size, $colour);
            $values = [
                'barcode_number' => (string) $member->barcode_number,
                'cost' => $this->money($member->cost),
                'mrp' => $this->money($member->mrp),
            ];
            $this->existing[$key] = ['id' => $member->id] + $values;
            $this->edits[$key] = $values;
        }
    }

    /** Existing variants whose barcode, cost or MRP was changed in the table. */
    public function changedKeys(): array
    {
        return array_values(array_filter(array_keys($this->existing), function (string $key): bool {
            $edit = $this->edits[$key] ?? [];
            $original = $this->existing[$key];

            return trim((string) ($edit['barcode_number'] ?? '')) !== $original['barcode_number']
                || $this->money($edit['cost'] ?? '') !== $original['cost']
                || $this->money($edit['mrp'] ?? '') !== $original['mrp'];
        }));
    }

    private function money(mixed $value): string
    {
        return is_numeric(trim((string) $value)) ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : trim((string) $value);
    }

    /** The original product needs a size before it can anchor a family. */
    public function saveBaseSize(): void
    {
        $this->authorizeEdit();
        $size = $this->clean($this->baseSize);
        if ($size === '') {
            $this->dispatch('error', ['message' => 'Type the size of '.$this->head['name'].' first.']);

            return;
        }
        Product::whereKey($this->head['id'])->first()?->update(['size' => $size, 'updated_by' => Auth::id()]);
        $this->baseSize = '';
        $this->load();
        $this->dispatch('success', ['message' => 'Size saved. Now add the other sizes.']);
    }

    public function addSize(): void
    {
        $size = $this->clean($this->newSize);
        $this->newSize = '';
        if ($size === '' || $this->indexOf($this->sizes, $size) !== null) {
            return;
        }
        $this->rememberSize($size);
        if ($this->colours === ['']) {
            $this->toggle($this->registerCell($size, ''));
        }
    }

    public function removeSize(string $size): void
    {
        foreach ($this->colours as $colour) {
            if (isset($this->existing[$this->cellKey($size, $colour)])) {
                return;
            }
        }
        foreach ($this->colours as $colour) {
            unset($this->picked[$this->cellKey($size, $colour)]);
        }
        $this->sizes = array_values(array_filter($this->sizes, fn (string $item) => $item !== $size));
    }

    public function addColour(string $name, string $hex = ''): void
    {
        $name = $this->clean($name);
        if ($name === '' || in_array(mb_strtolower($name), ['none', 'no colour', 'no color'], true)) {
            return;
        }
        $known = $this->indexOf(array_keys($this->hex), $name);
        $name = $known !== null ? array_keys($this->hex)[$known] : $name;
        if (preg_match('/^#[0-9a-f]{6}$/i', $hex) && $known === null) {
            $this->hex[$name] = strtolower($hex);
        }
        $this->rememberColour($name);
    }

    public function addNoColour(): void
    {
        $this->rememberColour('');
    }

    public function removeColour(string $colour): void
    {
        foreach ($this->sizes as $size) {
            if (isset($this->existing[$this->cellKey($size, $colour)])) {
                return;
            }
        }
        foreach ($this->sizes as $size) {
            unset($this->picked[$this->cellKey($size, $colour)]);
        }
        $this->colours = array_values(array_filter($this->colours, fn (string $item) => $item !== $colour));
        if (! $this->colours) {
            $this->colours = [''];
        }
    }

    public function toggle(string $key): void
    {
        if (! isset($this->cells[$key]) || isset($this->existing[$key])) {
            return;
        }
        if (isset($this->picked[$key])) {
            unset($this->picked[$key]);

            return;
        }
        $this->picked[$key] = ['barcode_number' => '', 'cost' => $this->bulk['cost'], 'mrp' => $this->bulk['mrp'], 'opening_stock' => ''];
    }

    /** "All sizes" on a colour card; a second tap clears the ones not yet created. */
    public function toggleColour(string $colour): void
    {
        $keys = array_map(fn (string $size) => $this->registerCell($size, $colour), $this->sizes);
        $open = array_filter($keys, fn (string $key) => ! isset($this->existing[$key]));
        $allOn = $open && count(array_filter($open, fn (string $key) => isset($this->picked[$key]))) === count($open);
        foreach ($open as $key) {
            if ($allOn) {
                unset($this->picked[$key]);
            } elseif (! isset($this->picked[$key])) {
                $this->toggle($key);
            }
        }
    }

    /** Cost and MRP go to every row, made or new; opening stock only to new ones (made ones already have stock). */
    public function applyToAll(): void
    {
        foreach (array_keys($this->edits) as $key) {
            foreach (['cost', 'mrp'] as $field) {
                if (trim((string) $this->bulk[$field]) !== '') {
                    $this->edits[$key][$field] = trim((string) $this->bulk[$field]);
                }
            }
        }
        foreach (array_keys($this->picked) as $key) {
            $this->picked[$key]['cost'] = $this->bulk['cost'];
            $this->picked[$key]['mrp'] = $this->bulk['mrp'];
            if ($this->bulk['opening_stock'] !== '') {
                $this->picked[$key]['opening_stock'] = $this->bulk['opening_stock'];
            }
        }
    }

    public function setGroupBy(string $groupBy): void
    {
        $this->groupBy = $groupBy === 'size' ? 'size' : 'colour';
        $this->groupBulk = [];
    }

    /** Give every new variant in one colour (or size) group the group's cost / MRP / stock; blanks are left alone. */
    public function applyToGroup(string $groupKey): void
    {
        $values = $this->groupBulk[$groupKey] ?? [];
        foreach (array_keys($this->edits) as $key) {
            if ($this->groupKeyOf($key) !== $groupKey) {
                continue;
            }
            foreach (['cost', 'mrp'] as $field) {
                if (trim((string) ($values[$field] ?? '')) !== '') {
                    $this->edits[$key][$field] = trim((string) $values[$field]);
                }
            }
        }
        foreach ($this->orderedPickedKeys() as $key) {
            if ($this->groupKeyOf($key) !== $groupKey) {
                continue;
            }
            foreach (['cost', 'mrp', 'opening_stock'] as $field) {
                if (trim((string) ($values[$field] ?? '')) !== '') {
                    $this->picked[$key][$field] = trim((string) $values[$field]);
                }
            }
        }
    }

    /**
     * The price table's groups in board order, each with its made and new cells.
     *
     * @return array<int, array{key: string, value: string, existing: array<int, string>, picked: array<int, string>}>
     */
    public function groups(): array
    {
        $values = $this->groupBy === 'size' ? $this->sizes : $this->colours;
        $others = $this->groupBy === 'size' ? $this->colours : $this->sizes;
        $groups = [];
        foreach ($values as $value) {
            $existing = [];
            $picked = [];
            foreach ($others as $other) {
                $key = $this->groupBy === 'size' ? $this->cellKey($value, $other) : $this->cellKey($other, $value);
                if (isset($this->existing[$key])) {
                    $existing[] = $key;
                } elseif (isset($this->picked[$key])) {
                    $picked[] = $key;
                }
            }
            if ($existing || $picked) {
                $groups[] = ['key' => $this->groupKey($value), 'value' => $value, 'existing' => $existing, 'picked' => $picked];
            }
        }

        return $groups;
    }

    private function groupKeyOf(string $cellKey): string
    {
        $cell = $this->cells[$cellKey] ?? ['size' => '', 'colour' => ''];

        return $this->groupKey($this->groupBy === 'size' ? $cell['size'] : $cell['colour']);
    }

    private function groupKey(string $value): string
    {
        return 'g'.substr(md5($this->groupBy.'|'.Product::normaliseVariantValue($value)), 0, 12);
    }

    public function cancel(): void
    {
        $this->load();
    }

    /**
     * Save the table: changed barcodes / prices of made variants first, then the
     * new variants, all in one transaction so a bad row leaves everything as it was.
     */
    public function save(): void
    {
        $changed = $this->changedKeys();
        $newKeys = $this->orderedPickedKeys();
        if ($changed) {
            $this->authorizeEdit();
        }
        if ($newKeys) {
            abort_unless(auth()->user()?->can('product.create'), 403);
        }
        if (! $changed && ! $newKeys) {
            return;
        }

        $rows = [];
        foreach ($newKeys as $key) {
            $rows[] = [
                'size' => $this->cells[$key]['size'],
                'color' => $this->cells[$key]['colour'],
            ] + $this->picked[$key];
        }

        try {
            DB::transaction(function () use ($changed, $rows): void {
                foreach ($changed as $key) {
                    $this->updateMadeVariant($key);
                }
                if ($rows) {
                    $response = (new CreateVariantsAction())->execute($this->head['id'], $rows, (int) Auth::id());
                    if (! $response['success']) {
                        throw new \Exception($response['message']);
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);

            return;
        }

        ProductColours::remember(array_values(array_filter(array_map(
            fn (string $colour) => $colour === '' ? null : ['name' => $colour, 'hex' => $this->hex[$colour] ?? ''],
            $this->colours,
        ))));
        $parts = array_filter([
            $rows ? 'created '.count($rows).' variant'.(count($rows) === 1 ? '' : 's') : null,
            $changed ? 'updated '.count($changed) : null,
        ]);
        $this->load();
        $this->dispatch('success', ['message' => ucfirst(implode(' and ', $parts)).'.']);
    }

    /** Push one made variant's edited barcode / cost / MRP through the normal product update. */
    private function updateMadeVariant(string $key): void
    {
        $product = Product::find($this->existing[$key]['id']);
        if (! $product) {
            throw new \Exception(Product::variantLabel($this->cells[$key]['size'], $this->cells[$key]['colour']).' no longer exists.');
        }
        $edit = $this->edits[$key];
        foreach (['cost', 'mrp'] as $field) {
            if (! is_numeric(trim((string) $edit[$field])) || (float) $edit[$field] < 0) {
                throw new \Exception(Product::variantLabel($product->size, $product->color).': '.strtoupper($field === 'mrp' ? 'MRP' : $field).' must be a number of 0 or more.');
            }
        }
        $data = array_merge($product->toArray(), [
            'barcode_number' => trim((string) $edit['barcode_number']),
            'cost' => (float) $edit['cost'],
            'mrp' => (float) $edit['mrp'],
        ]);
        $response = (new UpdateAction())->execute($data, $product->id, (int) Auth::id());
        if (! $response['success']) {
            throw new \Exception(Product::variantLabel($product->size, $product->color).': '.$response['message']);
        }
    }

    /** @return array<int, string> picked keys in board order (sizes, then colours) */
    public function orderedPickedKeys(): array
    {
        $keys = [];
        foreach ($this->sizes as $size) {
            foreach ($this->colours as $colour) {
                $key = $this->cellKey($size, $colour);
                if (isset($this->picked[$key])) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    public function cellKey(string $size, string $colour): string
    {
        return 'c'.substr(md5(Product::normaliseVariantValue($size).'|'.Product::normaliseVariantValue($colour)), 0, 12);
    }

    /** Dark text on light swatches, white on dark ones. */
    public function inkFor(?string $hex): string
    {
        if (! $hex || ! preg_match('/^#([0-9a-f]{6})$/i', $hex, $match)) {
            return '#111827';
        }
        $n = hexdec($match[1]);
        $luminance = (0.2126 * ($n >> 16) + 0.7152 * (($n >> 8) & 255) + 0.0722 * ($n & 255)) / 255;

        return $luminance > 0.6 ? '#111827' : '#ffffff';
    }

    public function render()
    {
        $used = array_map(fn (string $colour) => mb_strtolower(ProductColours::displayName($colour)), $this->colours);
        $suggestions = array_values(array_filter(
            array_keys($this->hex),
            fn (string $name) => ! in_array(mb_strtolower($name), $used, true),
        ));

        return view('livewire.product.variants', [
            'quickAdd' => array_slice($suggestions, 0, self::QUICK_ADD),
            'chooserShades' => array_values(array_filter(
                ProductColours::SUGGESTED,
                fn (array $shade) => ! in_array(mb_strtolower($shade[0]), $used, true),
            )),
            'orderedPicked' => $this->orderedPickedKeys(),
            'groups' => $this->groups(),
            'changed' => $this->changedKeys(),
            'sizeOnly' => $this->colours === [''],
        ]);
    }

    private function authorizeEdit(): void
    {
        abort_unless(auth()->user()?->can('product.edit'), 403);
    }

    private function registerCell(string $size, string $colour): string
    {
        $key = $this->cellKey($size, $colour);
        $this->cells[$key] ??= ['size' => $size, 'colour' => $colour];

        return $key;
    }

    /** Every size × colour cell must be registered, or a tap on it is ignored, whichever of the two arrived first. */
    private function rememberSize(string $size): void
    {
        if ($this->indexOf($this->sizes, $size) === null) {
            $this->sizes[] = $size;
        }
        foreach ($this->colours as $colour) {
            $this->registerCell($size, $colour);
        }
    }

    private function rememberColour(string $colour): void
    {
        if ($this->indexOf($this->colours, $colour) === null) {
            $this->colours[] = $colour;
        }
        if ($colour !== '' && ! isset($this->hex[$colour])) {
            $this->hex[$colour] = ProductColours::hexFor($colour, $this->hex);
        }
        foreach ($this->sizes as $size) {
            $this->registerCell($size, $colour);
        }
    }

    /** Case-insensitive position of [$value] in [$list], or null. */
    private function indexOf(array $list, string $value): ?int
    {
        foreach ($list as $index => $item) {
            if (Product::normaliseVariantValue($item) === Product::normaliseVariantValue($value)) {
                return $index;
            }
        }

        return null;
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/[<>"&|]/', '', $value));
    }
}
