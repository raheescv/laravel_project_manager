<?php

namespace App\Livewire\Product;

use App\Actions\OptionGroup\CreateAction;
use App\Actions\OptionGroup\DeleteAction;
use App\Actions\OptionGroup\UpdateAction;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Inventory → Option Groups: the list of reusable groups on the left, the
 * open group's rule and options on the right. A row's stock item is picked
 * from a search sheet (tap a product to set it).
 */
class OptionGroups extends Component
{
    /** 0 = a new group is being written. */
    public int $editingId = 0;

    public bool $editing = false;

    /** @var array{name: string, min_select: int|string, max_select: int|string|null, status: string, items: list<array<string, mixed>>} */
    public array $form = [];

    public string $search = '';

    /** The option row whose stock item is being picked, or null. */
    public ?int $stockRow = null;

    public string $stockSearch = '';

    public function mount(): void
    {
        $this->resetForm();
        $first = OptionGroup::query()->orderBy('sort')->orderBy('name')->value('id');
        if ($first) {
            $this->edit((int) $first);
        }
    }

    public function newGroup(): void
    {
        abort_unless(auth()->user()?->can('option group.create'), 403);
        $this->resetForm();
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $group = OptionGroup::with('items.product:id,name')->find($id);
        if (! $group) {
            return;
        }
        $this->editingId = $group->id;
        $this->editing = true;
        $this->stockRow = null;
        $this->form = [
            'name' => $group->name,
            'min_select' => (int) $group->min_select,
            'max_select' => $group->max_select,
            'status' => $group->status,
            'items' => $group->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $this->money($item->price),
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $this->money($item->quantity),
                'next_group_id' => $item->next_group_id,
                'is_default' => (bool) $item->is_default,
            ])->all(),
        ];
    }

    /** One tap sets the common rules; the two numbers stay editable for anything else. */
    public function applyPreset(int $min, ?int $max): void
    {
        $this->form['min_select'] = $min;
        $this->form['max_select'] = $max;
    }

    public function addItem(): void
    {
        $this->form['items'][] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->form['items'][$index]);
        $this->form['items'] = array_values($this->form['items']);
        $this->stockRow = null;
    }

    public function moveItem(int $index, int $step): void
    {
        $target = $index + $step;
        if (! isset($this->form['items'][$index], $this->form['items'][$target])) {
            return;
        }
        [$this->form['items'][$index], $this->form['items'][$target]] = [$this->form['items'][$target], $this->form['items'][$index]];
    }

    public function openStock(int $index): void
    {
        $this->stockRow = $index;
        $this->stockSearch = '';
    }

    public function closeStock(): void
    {
        $this->stockRow = null;
    }

    public function pickStock(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->first(['id', 'name', 'size', 'color', 'variant_value']);
        if ($product && $this->stockRow !== null && isset($this->form['items'][$this->stockRow])) {
            $this->form['items'][$this->stockRow]['product_id'] = $product->id;
            $this->form['items'][$this->stockRow]['product_name'] = $product->displayName();
        }
        $this->stockRow = null;
    }

    public function clearStock(int $index): void
    {
        if (isset($this->form['items'][$index])) {
            $this->form['items'][$index]['product_id'] = null;
            $this->form['items'][$index]['product_name'] = null;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can($this->editingId ? 'option group.edit' : 'option group.create'), 403);
        try {
            DB::beginTransaction();
            $response = $this->editingId
                ? (new UpdateAction())->execute($this->form, $this->editingId)
                : (new CreateAction())->execute($this->form);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->edit($response['data']->id);
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->can('option group.delete'), 403);
        if (! $this->editingId) {
            return;
        }
        try {
            DB::beginTransaction();
            $response = (new DeleteAction())->execute($this->editingId);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->resetForm();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        $groups = OptionGroup::query()
            ->withCount(['items', 'products'])
            ->with(['items' => fn ($query) => $query->select(['id', 'option_group_id', 'name', 'sort'])])
            ->when(trim($this->search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))
            ->orderBy('sort')->orderBy('name')
            ->get();

        $stockResults = $this->stockRow === null ? collect() : Product::query()
            ->product()
            ->when(trim($this->stockSearch) !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'like', '%'.trim($this->stockSearch).'%')
                ->orWhere('code', 'like', '%'.trim($this->stockSearch).'%')
                ->orWhere('barcode', trim($this->stockSearch))))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'code', 'size', 'color', 'variant_value']);

        return view('livewire.product.option-groups', [
            'groups' => $groups,
            'followUps' => $groups->reject(fn (OptionGroup $group) => $group->id === $this->editingId)->pluck('name', 'id'),
            'stockResults' => $stockResults,
            'rule' => OptionGroup::ruleLabel((int) ($this->form['min_select'] ?: 0), ($this->form['max_select'] ?? '') === '' || $this->form['max_select'] === null ? null : (int) $this->form['max_select']),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = 0;
        $this->editing = false;
        $this->stockRow = null;
        $this->form = [
            'name' => '',
            'min_select' => 1,
            'max_select' => 1,
            'status' => 'active',
            'items' => [$this->blankItem(), $this->blankItem()],
        ];
    }

    /** @return array<string, mixed> */
    private function blankItem(): array
    {
        return ['id' => null, 'name' => '', 'price' => '0', 'product_id' => null, 'product_name' => null, 'quantity' => '1', 'next_group_id' => null, 'is_default' => false];
    }

    private function money(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
