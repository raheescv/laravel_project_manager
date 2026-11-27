<?php

namespace App\Livewire\Product;

use App\Actions\Product\Option\SyncAction;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The product page's "Options" tab: which option groups the till asks for this
 * item and in what order. Every tap saves straight away (attach, move, remove).
 * A variant without groups of its own asks its family head's.
 */
class Options extends Component
{
    public int $product_id;

    public function mount(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function attach(int $groupId): void
    {
        $ids = $this->attachedIds();
        if (in_array($groupId, $ids, true)) {
            return;
        }
        $ids[] = $groupId;
        $this->sync($ids, 'Attached');
    }

    public function detach(int $groupId): void
    {
        $this->sync(array_values(array_diff($this->attachedIds(), [$groupId])), 'Removed');
    }

    public function move(int $groupId, int $step): void
    {
        $ids = $this->attachedIds();
        $index = array_search($groupId, $ids, true);
        if ($index === false || ! isset($ids[$index + $step])) {
            return;
        }
        [$ids[$index], $ids[$index + $step]] = [$ids[$index + $step], $ids[$index]];
        $this->sync($ids, 'Moved');
    }

    /** @param  list<int>  $ids */
    private function sync(array $ids, string $verb): void
    {
        abort_unless(auth()->user()?->can('product.edit'), 403);
        $current = $this->product()?->optionGroups->keyBy('id');
        $groups = array_map(fn (int $id) => [
            'option_group_id' => $id,
            'min_select' => $current?->get($id)?->pivot->min_select,
            'max_select' => $current?->get($id)?->pivot->max_select,
        ], $ids);

        try {
            DB::beginTransaction();
            $response = (new SyncAction())->execute($this->product_id, $groups);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $verb.'. '.$response['message'].'.']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /** @return list<int> */
    private function attachedIds(): array
    {
        return $this->product()?->optionGroups->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
    }

    private function product(): ?Product
    {
        return Product::with('optionGroups.items:id,option_group_id,name,price')->find($this->product_id);
    }

    public function render()
    {
        $product = $this->product();
        $attached = $product?->optionGroups ?? collect();
        $inherited = $product && $attached->isEmpty() && $product->parent_id
            ? Product::with('optionGroups.items:id,option_group_id,name,price')->find($product->parent_id)
            : null;

        return view('livewire.product.options', [
            'product' => $product,
            'attached' => $attached,
            'inherited' => $inherited,
            'available' => OptionGroup::query()->active()->with('items:id,option_group_id,name')
                ->whereNotIn('id', $attached->pluck('id'))->orderBy('sort')->orderBy('name')->get(),
        ]);
    }
}
