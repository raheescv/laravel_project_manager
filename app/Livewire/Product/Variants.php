<?php

namespace App\Livewire\Product;

use App\Actions\Product\UpdateAction;
use App\Actions\Product\Variant\CreateVariantsAction;
use App\Models\Product;
use App\Support\ProductColours;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * The product page's "Variants" tab, in three steps:
 *
 *   1. What is it made of — name the two layers (Design / Base / Flavour…,
 *      saved as variant_option; Size / Flavour / Weight…, saved as
 *      variant_size_option), list their values, and say whether it comes in
 *      colours.
 *   2. Which ones exist — a grid of first layer × second layer (per colour), or
 *      size × colour for a family without a first layer. Tap a box to make it.
 *   3. Prices — barcode, cost, MRP and opening stock, grouped.
 *
 * Every ticked box is created as its own product; made ones show locked. A
 * layer that is not used holds one '' entry ("No design", "No size",
 * "No colour"), so a shoe can skip designs and a drink can skip sizes.
 *
 * Cells are addressed by an opaque key (see cellKey()) because values such as
 * "1.5" or "S/M" would break Livewire's dot-separated wire:model paths.
 */
class Variants extends Component
{
    /** How many suggestion chips the "Quick add" card shows. */
    private const QUICK_ADD = 7;

    public int $product_id;

    public bool $isVariant = false;

    /** @var array{id: int, name: string, size: string, design: string} */
    public array $head = [];

    /** @var array<int, array{id: int, label: string, colour: string, current: bool}> family members, for a variant's page */
    public array $siblings = [];

    /** What the family's first layer is called: "Design", "Flavour", "Model"… */
    public string $option = Product::DEFAULT_VARIANT_OPTION;

    public string $customOption = '';

    /** What the family's second layer is called: "Size", "Flavour", "Weight"… */
    public string $sizeOption = Product::DEFAULT_SIZE_OPTION;

    public string $customSizeOption = '';

    /** @var array<int, string> '' is "No design" */
    public array $designs = [''];

    /** 1 · what is it made of, 2 · which ones exist, 3 · prices. */
    public int $step = 1;

    /** The colour whose design × size grid step 2 shows. */
    public string $activeColour = '';

    /** @var array<int, string> '' is "No size" */
    public array $sizes = [''];

    /** @var array<int, string> '' is "No colour" */
    public array $colours = [''];

    /** "Does it come in colours?" — off for packs and drinks, so colours never show. */
    public bool $coloursOn = false;

    /** Step 1 shows both layers; off while the family uses only one (just sizes, or just flavours). */
    public bool $twoLayers = false;

    /** @var array<string, string> colour name => hex */
    public array $hex = [];

    /** @var array<string, array{design: string, size: string, colour: string}> */
    public array $cells = [];

    /** @var array<string, array{id: int, barcode_number: string, cost: string, mrp: string}> cells that already exist as products, as saved */
    public array $existing = [];

    /** @var array<string, array{barcode_number: string, cost: string, mrp: string}> the table's editable copy of */
    public array $edits = [];

    /** @var array<string, array{barcode_number: string, cost: string, mrp: string, opening_stock: string}> newly ticked cells */
    public array $picked = [];

    public string $newDesign = '';

    public string $newSize = '';

    public string $baseDesign = '';

    public string $baseSize = '';

    /** @var array{cost: string, mrp: string, opening_stock: string} */
    public array $bulk = ['cost' => '', 'mrp' => '', 'opening_stock' => ''];

    /** @var array<string, array{cost?: string, mrp?: string, opening_stock?: string}> group key => values to apply */
    public array $groupBulk = [];

    public function mount(int $product_id): void
    {
        $this->product_id = $product_id;
        $this->load();
        // A family that already has variants opens on its grid; a new one starts by naming its layers.
        $this->step = count($this->existing) > 1 ? 2 : 1;
    }

    public function load(): void
    {
        $product = Product::find($this->product_id);
        if (! $product) {
            return;
        }

        $this->isVariant = $product->isVariant();
        $head = $this->isVariant ? $product->parent : $product;
        $this->option = $head->optionName();
        $this->sizeOption = $head->sizeName();
        $this->head = [
            'id' => $head->id,
            'name' => (string) $head->name,
            'size' => trim((string) $head->size),
            'design' => trim((string) $head->variant_value),
        ];
        $family = $this->family(['id', 'parent_id', 'name', 'variant_value', 'size', 'color', 'barcode', 'barcode_number', 'cost', 'mrp']);

        if ($this->isVariant) {
            $this->siblings = $family->map(fn (Product $member) => [
                'id' => $member->id,
                'label' => $member->variantName() ?: $member->name,
                'colour' => trim((string) $member->color),
                'current' => $member->id === $product->id,
            ])->all();

            return;
        }

        $this->hex = ProductColours::palette();
        $this->designs = [];
        $this->sizes = [];
        $this->colours = [];
        $this->cells = [];
        $this->existing = [];
        $this->edits = [];
        $this->picked = [];
        $this->bulk = ['cost' => (string) (float) $head->cost, 'mrp' => (string) (float) $head->mrp, 'opening_stock' => ''];

        foreach ($family as $member) {
            $design = trim((string) $member->variant_value);
            $size = trim((string) $member->size);
            if ($design === '' && $size === '') {
                continue;
            }
            $colour = trim((string) $member->color);
            $this->remember($this->designs, $design);
            $this->remember($this->sizes, $size);
            $this->remember($this->colours, $colour);
            $key = $this->cellKey($size, $colour, $design);
            $this->cells[$key] = ['design' => $design, 'size' => $size, 'colour' => $colour];
            $values = [
                'barcode_number' => (string) $member->barcode_number,
                'cost' => $this->money($member->cost),
                'mrp' => $this->money($member->mrp),
            ];
            $this->existing[$key] = ['id' => $member->id] + $values;
            $this->edits[$key] = $values;
        }

        foreach (['designs', 'sizes', 'colours'] as $layer) {
            if (! $this->{$layer}) {
                $this->{$layer} = [''];
            }
        }
        foreach ($this->colours as $colour) {
            if ($colour !== '' && ! isset($this->hex[$colour])) {
                $this->hex[$colour] = ProductColours::hexFor($colour, $this->hex);
            }
        }
        if ($this->indexOf($this->colours, $this->activeColour) === null) {
            $this->activeColour = $this->colours[0];
        }
        $this->coloursOn = $this->coloursOn || $this->colours !== [''];
        $this->twoLayers = $this->twoLayers || ($this->designs !== [''] && $this->sizes !== ['']);
        $this->registerAll();
    }

    /**
     * Step 1's "One layer / Two layers". Back to one only while the layer that
     * would hide has no values of its own.
     */
    public function useTwoLayers(bool $on): void
    {
        if (! $on && $this->designs !== [''] && $this->sizes !== ['']) {
            $this->dispatch('error', ['message' => 'Remove the '.Str::plural(mb_strtolower($this->option)).' or the '.Str::plural(mb_strtolower($this->sizeOption)).' first: both layers have values.']);

            return;
        }
        $this->twoLayers = $on;
    }

    /**
     * Swap which layer comes first — the one the till asks for first (Flavour
     * then Base instead of Base then Flavour). Every family member trades its
     * design for its size, and the two layer names trade places.
     */
    public function swapLayers(): void
    {
        $this->authorizeEdit();
        if ($this->picked) {
            $this->dispatch('error', ['message' => 'Create or cancel the new variants first, then swap.']);

            return;
        }
        $family = $this->family();
        $tooLong = $family->first(fn (Product $member) => mb_strlen(trim((string) $member->size)) > 100);
        if ($tooLong) {
            $this->dispatch('error', ['message' => '"'.Str::limit($tooLong->size, 30).'" is too long for the first layer (100 characters at most).']);

            return;
        }
        $first = $this->sizeOption;
        $second = $this->option;
        DB::transaction(fn () => $family->each(fn (Product $member) => $member->update([
            'variant_value' => trim((string) $member->size) ?: null,
            'size' => trim((string) $member->variant_value) ?: null,
            'size_category' => null,
            'variant_option' => $first === Product::DEFAULT_VARIANT_OPTION ? null : $first,
            'variant_size_option' => $second === Product::DEFAULT_SIZE_OPTION ? null : $second,
            'updated_by' => Auth::id(),
        ])));
        $this->load();
        $this->dispatch('success', ['message' => mb_strtolower($first).' now comes first, then '.mb_strtolower($second).'.']);
    }

    /** With one layer, the layer in use: the first when the family has designs and no sizes, else the sizes. */
    public function singleLayer(): string
    {
        return $this->designs !== [''] && $this->sizes === [''] ? 'design' : 'size';
    }

    public function goToStep(int $step): void
    {
        $this->step = max(1, min(3, $step));
    }

    /**
     * Step 1's "Does it come in colours?". Turning it off drops the colours not
     * yet made; it is refused while a made variant still uses one.
     */
    public function useColours(bool $on): void
    {
        if ($on) {
            $this->coloursOn = true;

            return;
        }
        foreach ($this->colours as $colour) {
            if ($colour !== '' && $this->layerInUse('colour', $colour)) {
                $this->dispatch('error', ['message' => $this->head['name'].' already has variants in '.ProductColours::displayName($colour).'.']);

                return;
            }
        }
        foreach ($this->colours as $colour) {
            if ($colour !== '') {
                $this->unpick('colour', $colour);
            }
        }
        $this->colours = [''];
        $this->activeColour = '';
        $this->coloursOn = false;
        $this->registerAll();
    }

    public function selectColour(string $colour): void
    {
        $index = $this->indexOf($this->colours, $colour);
        if ($index !== null) {
            $this->activeColour = $this->colours[$index];
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

    /** The original product needs a design (flavour, model…) or a size before it can anchor a family. */
    public function saveBaseSize(): void
    {
        $this->authorizeEdit();
        $design = $this->clean($this->baseDesign);
        $size = $this->clean($this->baseSize);
        if ($design === '' && $size === '') {
            $this->dispatch('error', ['message' => 'Type the '.mb_strtolower($this->option).' or the '.mb_strtolower($this->sizeOption).' of '.$this->head['name'].' first.']);

            return;
        }
        Product::whereKey($this->head['id'])->first()?->update([
            'variant_value' => $design === '' ? null : $design,
            'size' => $size === '' ? null : $size,
            'updated_by' => Auth::id(),
        ]);
        $this->baseDesign = '';
        $this->baseSize = '';
        $this->load();
        $this->dispatch('success', ['message' => 'Saved. Now add the other '.Str::plural(mb_strtolower($this->option)).', '.Str::plural(mb_strtolower($this->sizeOption)).' and colours.']);
    }

    /** Name the family's first layer ("Design", "Base", "Flavour" or any typed name). Only the label changes. */
    public function setOption(string $name = ''): void
    {
        $this->option = $this->renameLayer('variant_option', Product::DEFAULT_VARIANT_OPTION, $name !== '' ? $name : $this->customOption) ?? $this->option;
        $this->customOption = '';
    }

    /** Name the family's second layer ("Size", "Flavour", "Weight" or any typed name); the values stay in `size`. */
    public function setSizeOption(string $name = ''): void
    {
        $this->sizeOption = $this->renameLayer('variant_size_option', Product::DEFAULT_SIZE_OPTION, $name !== '' ? $name : $this->customSizeOption) ?? $this->sizeOption;
        $this->customSizeOption = '';
    }

    /**
     * Store a layer name on the head and every variant (NULL for the default),
     * touching only the members that still carry another name.
     *
     * @return ?string the name now shown, or null when nothing was typed
     */
    private function renameLayer(string $column, string $default, string $name): ?string
    {
        $this->authorizeEdit();
        $name = Str::ucfirst(Str::limit($this->clean($name), 40, ''));
        if ($name === '') {
            return null;
        }
        $stored = Product::normaliseVariantValue($name) === mb_strtolower($default) ? null : $name;
        $this->family()
            ->reject(fn (Product $member) => $member->{$column} === $stored)
            ->each(fn (Product $member) => $member->update([$column => $stored, 'updated_by' => Auth::id()]));
        $shown = $stored ?? $default;
        $this->dispatch('success', ['message' => 'Now called '.mb_strtolower($shown).'.']);

        return $shown;
    }

    /**
     * For a family whose flavours / designs were typed in as sizes (before the
     * design layer existed): move every member's size into its design.
     */
    public function moveSizesToDesign(): void
    {
        $this->authorizeEdit();
        $family = $this->family();
        if ($family->contains(fn (Product $member) => trim((string) $member->variant_value) !== '')) {
            $this->dispatch('error', ['message' => 'This family already has '.Str::plural(mb_strtolower($this->option)).'.']);

            return;
        }
        DB::transaction(fn () => $family->each(fn (Product $member) => $member->update([
            'variant_value' => trim((string) $member->size) === '' ? null : trim((string) $member->size),
            'size' => null,
            'size_category' => null,
            'updated_by' => Auth::id(),
        ])));
        $this->load();
        $this->dispatch('success', ['message' => 'Moved to '.Str::plural(mb_strtolower($this->option)).'. Add '.Str::plural(mb_strtolower($this->sizeOption)).' under each one if it has them.']);
    }

    public function addDesign(): void
    {
        $design = $this->clean($this->newDesign);
        $this->newDesign = '';
        if ($design === '' || $this->indexOf($this->designs, $design) !== null) {
            return;
        }
        $this->dropUnusedBlank($this->designs, 'design');
        $this->remember($this->designs, $design);
        $this->registerAll();
        // With nothing under it (a drink in flavours only) the design is the variant itself.
        if ($this->sizes === [''] && $this->colours === ['']) {
            $this->toggle($this->cellKey('', '', $design));
        }
    }

    public function removeDesign(string $design): void
    {
        if ($this->layerInUse('design', $design)) {
            return;
        }
        $this->unpick('design', $design);
        $this->designs = array_values(array_filter($this->designs, fn (string $item) => $item !== $design)) ?: [''];
    }

    public function addSize(): void
    {
        $size = $this->clean($this->newSize);
        $this->newSize = '';
        if ($size === '' || $this->indexOf($this->sizes, $size) !== null) {
            return;
        }
        $this->dropUnusedBlank($this->sizes, 'size');
        $this->remember($this->sizes, $size);
        $this->registerAll();
        // A sizes-only family has nothing else to choose: the new size is ticked straight away.
        if ($this->designs === [''] && $this->colours === ['']) {
            $this->toggle($this->cellKey($size, ''));
        }
    }

    public function removeSize(string $size): void
    {
        if ($this->layerInUse('size', $size)) {
            return;
        }
        $this->unpick('size', $size);
        $this->sizes = array_values(array_filter($this->sizes, fn (string $item) => $item !== $size)) ?: [''];
        $this->registerAll();
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
        if ($this->layerInUse('colour', $colour)) {
            return;
        }
        $this->unpick('colour', $colour);
        $this->colours = array_values(array_filter($this->colours, fn (string $item) => $item !== $colour)) ?: [''];
        if ($this->indexOf($this->colours, $this->activeColour) === null) {
            $this->activeColour = $this->colours[0];
        }
        $this->registerAll();
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

    /** Step 2 lays the first layer across the top, or the colours when the family has no first layer. */
    public function gridByDesign(): bool
    {
        return $this->designs !== [''];
    }

    /** @return array<int, string> step 2's columns */
    public function gridColumns(): array
    {
        return $this->gridByDesign() ? $this->designs : $this->colours;
    }

    /** The cell at a step-2 row (size) and column (design, or colour). */
    public function gridKey(string $size, string $column): string
    {
        return $this->gridByDesign()
            ? $this->cellKey($size, $this->activeColour, $column)
            : $this->cellKey($size, $column);
    }

    /** "All" over a column; a second tap clears the ones not yet made. */
    public function toggleColumn(string $column): void
    {
        $this->toggleAll(array_map(fn (string $size) => $this->gridKey($size, $column), $this->sizes));
    }

    /** "All" along a row; a second tap clears the ones not yet made. */
    public function toggleRow(string $size): void
    {
        $this->toggleAll(array_map(fn (string $column) => $this->gridKey($size, $column), $this->gridColumns()));
    }

    /** @param  array<int, string>  $keys */
    private function toggleAll(array $keys): void
    {
        $open = array_filter($keys, fn (string $key) => isset($this->cells[$key]) && ! isset($this->existing[$key]));
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
        $this->applyValues($this->bulk);
    }

    /** Give every variant in one group the group's cost / MRP / stock. */
    public function applyToGroup(string $groupKey): void
    {
        $this->applyValues($this->groupBulk[$groupKey] ?? [], $groupKey);
    }

    /**
     * Copy cost / MRP onto made rows and cost / MRP / opening stock onto new
     * ones — all rows, or one group's. Blank values leave a field alone.
     *
     * @param  array<string, mixed>  $values
     */
    private function applyValues(array $values, ?string $groupKey = null): void
    {
        $inScope = fn (string $key) => $groupKey === null || $this->groupKeyOf($key) === $groupKey;
        $filled = array_filter(array_map(fn ($value) => trim((string) $value), $values), fn (string $value) => $value !== '');
        foreach (array_filter(array_keys($this->edits), $inScope) as $key) {
            $this->edits[$key] = array_merge($this->edits[$key], array_intersect_key($filled, array_flip(['cost', 'mrp'])));
        }
        foreach (array_filter(array_keys($this->picked), $inScope) as $key) {
            $this->picked[$key] = array_merge($this->picked[$key], array_intersect_key($filled, array_flip(['cost', 'mrp', 'opening_stock'])));
        }
    }

    /**
     * Step 3's groups in board order, each with its made and new cells: by
     * design, else by colour, else one group.
     *
     * @return array<int, array{key: string, value: string, existing: array<int, string>, picked: array<int, string>}>
     */
    public function groups(): array
    {
        $field = $this->groupField();
        $values = match ($field) {
            'design' => $this->designs,
            'colour' => $this->colours,
            default => [''],
        };
        $groups = [];
        $boardKeys = $this->boardKeys();
        foreach ($values as $value) {
            $existing = [];
            $picked = [];
            foreach ($boardKeys as $key) {
                if ($field !== 'all' && Product::normaliseVariantValue($this->cells[$key][$field]) !== Product::normaliseVariantValue($value)) {
                    continue;
                }
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

    /** 'design', 'colour' or 'all' */
    public function groupField(): string
    {
        return $this->gridByDesign() ? 'design' : ($this->colours !== [''] ? 'colour' : 'all');
    }

    private function groupKeyOf(string $cellKey): string
    {
        $field = $this->groupField();

        return $this->groupKey($field === 'all' ? '' : ($this->cells[$cellKey][$field] ?? ''));
    }

    private function groupKey(string $value): string
    {
        return 'g'.substr(md5($this->groupField().'|'.Product::normaliseVariantValue($value)), 0, 12);
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
                'variant_value' => $this->cells[$key]['design'],
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
        $label = $this->cellLabel($key);
        $product = Product::find($this->existing[$key]['id']);
        if (! $product) {
            throw new \Exception($label.' no longer exists.');
        }
        $edit = $this->edits[$key];
        foreach (['cost', 'mrp'] as $field) {
            if (! is_numeric(trim((string) $edit[$field])) || (float) $edit[$field] < 0) {
                throw new \Exception($label.': '.strtoupper($field === 'mrp' ? 'MRP' : $field).' must be a number of 0 or more.');
            }
        }
        $data = array_merge($product->toArray(), [
            'barcode_number' => trim((string) $edit['barcode_number']),
            'cost' => (float) $edit['cost'],
            'mrp' => (float) $edit['mrp'],
        ]);
        $response = (new UpdateAction())->execute($data, $product->id, (int) Auth::id());
        if (! $response['success']) {
            throw new \Exception($label.': '.$response['message']);
        }
    }

    /** "Floral / M / Navy" for a cell key. */
    public function cellLabel(string $key): string
    {
        $cell = $this->cells[$key] ?? ['design' => '', 'size' => '', 'colour' => ''];

        return Product::variantLabel($cell['size'], $cell['colour'], $cell['design']);
    }

    /** @return array<int, string> every cell key in board order (designs, then sizes, then colours) */
    private function boardKeys(): array
    {
        $keys = [];
        foreach ($this->designs as $design) {
            foreach ($this->sizes as $size) {
                foreach ($this->colours as $colour) {
                    $key = $this->cellKey($size, $colour, $design);
                    if (isset($this->cells[$key])) {
                        $keys[] = $key;
                    }
                }
            }
        }

        return $keys;
    }

    /** @return array<int, string> picked keys in board order */
    public function orderedPickedKeys(): array
    {
        return array_values(array_filter($this->boardKeys(), fn (string $key) => isset($this->picked[$key])));
    }

    /** The opaque key of one design × size × colour cell. */
    public function cellKey(string $size, string $colour, string $design = ''): string
    {
        return 'c'.substr(md5(
            Product::normaliseVariantValue($design).'|'.Product::normaliseVariantValue($size).'|'.Product::normaliseVariantValue($colour)
        ), 0, 12);
    }

    public function render()
    {
        $used = array_map(fn (string $colour) => mb_strtolower(ProductColours::displayName($colour)), $this->colours);
        $suggestions = array_values(array_filter(
            array_keys($this->hex),
            fn (string $name) => ! in_array(mb_strtolower($name), $used, true),
        ));
        $optionLower = mb_strtolower($this->option);

        return view('livewire.product.variants', [
            'quickAdd' => array_slice($suggestions, 0, self::QUICK_ADD),
            'chooserShades' => array_values(array_filter(
                ProductColours::SUGGESTED,
                fn (array $shade) => ! in_array(mb_strtolower($shade[0]), $used, true),
            )),
            'orderedPicked' => $this->orderedPickedKeys(),
            'groups' => $this->step === 3 ? $this->groups() : [],
            'changed' => $this->changedKeys(),
            'gridColumns' => $this->gridColumns(),
            'hasDesigns' => $this->designs !== [''],
            'hasSizes' => $this->sizes !== [''],
            'optionLower' => $optionLower,
            'optionPlural' => Str::plural($optionLower),
            'optionChoices' => Product::SUGGESTED_VARIANT_OPTIONS,
            'sizeLower' => mb_strtolower($this->sizeOption),
            'sizePlural' => Str::plural(mb_strtolower($this->sizeOption)),
            'sizeChoices' => Product::SUGGESTED_SIZE_OPTIONS,
            'designExample' => ['Flavour' => 'Mango', 'Base' => 'Red Bull', 'Model' => 'Pro', 'Style' => 'Slim'][$this->option] ?? 'Floral',
            'sizeExample' => ['Flavour' => 'Mango', 'Weight' => '250g', 'Volume' => '500ml', 'Pack' => '6 pack'][$this->sizeOption] ?? 'M',
            'groupField' => $this->groupField(),
            'canMoveSizes' => $this->twoLayers && $this->designs === [''] && $this->sizes !== [''] && $this->existing,
        ]);
    }

    private function authorizeEdit(): void
    {
        abort_unless(auth()->user()?->can('product.edit'), 403);
    }

    /**
     * @param  array<int, string>  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, Product> the head first, then its variants
     */
    private function family(array $columns = ['*'])
    {
        return Product::query()
            ->where(fn ($query) => $query->where('id', $this->head['id'])->orWhere('parent_id', $this->head['id']))
            ->orderByRaw('parent_id IS NOT NULL')->orderBy('id')
            ->get($columns);
    }

    /** Every design × size × colour cell must be registered, or a tap on it is ignored. */
    private function registerAll(): void
    {
        foreach ($this->designs as $design) {
            foreach ($this->sizes as $size) {
                foreach ($this->colours as $colour) {
                    $this->cells[$this->cellKey($size, $colour, $design)] ??= ['design' => $design, 'size' => $size, 'colour' => $colour];
                }
            }
        }
    }

    private function rememberColour(string $colour): void
    {
        if ($colour !== '') {
            $this->dropUnusedBlank($this->colours, 'colour');
            $this->coloursOn = true;
        }
        $this->remember($this->colours, $colour);
        if ($colour !== '' && ! isset($this->hex[$colour])) {
            $this->hex[$colour] = ProductColours::hexFor($colour, $this->hex);
        }
        $this->registerAll();
    }

    /** @param  array<int, string>  $list */
    private function remember(array &$list, string $value): void
    {
        if ($this->indexOf($list, $value) === null) {
            $list[] = $value;
        }
    }

    /**
     * The '' placeholder of a layer ("No size") goes once a real value arrives,
     * unless something made or picked still sits in it.
     *
     * @param  array<int, string>  $list
     */
    private function dropUnusedBlank(array &$list, string $field): void
    {
        if ($list === [''] && ! $this->layerInUse($field, '') && ! $this->layerPicked($field, '')) {
            $list = [];
        }
    }

    /** Whether a made variant uses this design / size / colour. */
    public function layerInUse(string $field, string $value): bool
    {
        return $this->keysWith(array_keys($this->existing), $field, $value) !== [];
    }

    private function layerPicked(string $field, string $value): bool
    {
        return $this->keysWith(array_keys($this->picked), $field, $value) !== [];
    }

    private function unpick(string $field, string $value): void
    {
        foreach ($this->keysWith(array_keys($this->picked), $field, $value) as $key) {
            unset($this->picked[$key]);
        }
    }

    /**
     * The cell keys among [$keys] whose design / size / colour is [$value].
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    private function keysWith(array $keys, string $field, string $value): array
    {
        $value = Product::normaliseVariantValue($value);

        return array_values(array_filter($keys, fn (string $key) => Product::normaliseVariantValue($this->cells[$key][$field] ?? '') === $value));
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
