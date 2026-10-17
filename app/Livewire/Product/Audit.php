<?php

namespace App\Livewire\Product;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * The product page's Audit tab: the product's change history as a sheet with
 * one column per field. Lazy, so the audits are only queried once the tab is
 * opened.
 */
#[Lazy]
class Audit extends Component
{
    /** Newest entries shown; a product's cost can change on every purchase. */
    public const LIMIT = 500;

    public int $product_id;

    public function mount(int $product_id): void
    {
        abort_unless(auth()->user()?->can('product.audit view'), 403);

        $this->product_id = $product_id;
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="placeholder-glow py-2">
            <span class="placeholder col-12 rounded mb-2" style="height: 2rem;"></span>
            <span class="placeholder col-12 rounded mb-2" style="height: 1.5rem;"></span>
            <span class="placeholder col-12 rounded mb-2" style="height: 1.5rem;"></span>
            <span class="placeholder col-8 rounded" style="height: 1.5rem;"></span>
        </div>
        HTML;
    }

    public function render()
    {
        return view('livewire.product.audit', [
            'audits' => $this->audits(),
        ]);
    }

    private function audits(): Collection
    {
        $product = Product::withTrashed()->find($this->product_id, ['id']);

        if (! $product) {
            return collect();
        }

        return $product->audits()
            ->with('user:id,name')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
