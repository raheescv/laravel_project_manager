<?php

namespace App\Livewire\Issue;

use App\Actions\Issue\CreateAction;
use App\Actions\Issue\UpdateAction;
use App\Models\Issue;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public ?int $table_id = null;

    public string $type = 'issue';

    public array $issues = [];

    public array $items = [];

    public array $accounts = [];

    public string $account_id = '';

    public string $product_id = '';

    public string $add_quantity_in = '0';

    public string $add_quantity_out = '0';

    public string $barcode_input = '';

    public function mount(?int $id = null, string $type = 'issue'): void
    {
        $this->table_id = $id;
        $this->items = [];
        $this->type = in_array($type, ['issue', 'return'], true) ? $type : 'issue';

        if ($this->table_id) {
            $issue = Issue::with('account', 'items.product')->find($this->table_id);
            if (! $issue) {
                $this->redirect(route('issue::index'));

                return;
            }
            $this->type = $issue->type;
            $this->accounts = [$issue->account_id => $issue->account?->name ?? 'Customer'];
            $this->issues = [
                'account_id' => $issue->account_id,
                'type' => $issue->type,
                'date' => $issue->date?->format('Y-m-d') ?? date('Y-m-d'),
                'remarks' => $issue->remarks ?? '',
            ];
            foreach ($issue->items as $item) {
                $key = 'item_'.($item->id ?? uniqid());
                $this->items[$key] = [
                    'id' => $item->id,
                    'key' => $key,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity_in' => (string) $item->quantity_in,
                    'quantity_out' => (string) $item->quantity_out,
                ];
            }
        } else {
            $this->issues = [
                'account_id' => '',
                'date' => date('Y-m-d'),
                'remarks' => '',
            ];
        }
    }

    public function updatedProductId(): void{
        if ($this->isReturnMode()) {
            $this->add_quantity_in = '1';
            $this->add_quantity_out = '0';
        } else {
            $this->add_quantity_out = '1';
            $this->add_quantity_in = '0';
        }
    }
    public function addToCart(): void
    {
        $productId = $this->product_id;
        if (! $productId) {
            $this->dispatch('error', ['message' => 'Please select a product.']);

            return;
        }

        $qtyIn = (float) $this->add_quantity_in;
        $qtyOut = (float) $this->add_quantity_out;
        $qty = $this->isReturnMode() ? $qtyIn : $qtyOut;
        if ($qty <= 0) {
            $this->dispatch('error', ['message' => 'Please enter quantity.']);

            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            $this->dispatch('error', ['message' => 'Product not found.']);

            return;
        }

        $key = 'item_'.uniqid();
        $this->items[$key] = [
            'id' => null,
            'key' => $key,
            'product_id' => $productId,
            'name' => $product->name,
            'quantity_in' => (string) ($this->isReturnMode() ? $qty : 0),
            'quantity_out' => (string) ($this->isReturnMode() ? 0 : $qty),
        ];

        $this->product_id = '';
        $this->add_quantity_in = '0';
        $this->add_quantity_out = '0';
        $this->dispatch('OpenProductBox');
        $this->dispatch('success', ['message' => 'Added to cart.']);
    }

    public function addToCartByBarcode(): void
    {
        $barcode = trim($this->barcode_input);
        if ($barcode === '') {
            return;
        }

        $product = Product::firstWhere('barcode', $barcode);
        if (! $product) {
            $this->dispatch('error', ['message' => 'No product found for barcode: '.$barcode]);
            $this->barcode_input = '';

            return;
        }

        $this->product_id = (string) $product->id;
        if ($this->isReturnMode()) {
            $this->add_quantity_in = '1';
            $this->add_quantity_out = '0';
        } else {
            $this->add_quantity_out = '1';
            $this->add_quantity_in = '0';
        }
        $this->addToCart();
        $this->barcode_input = '';
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
        $this->dispatch('success', ['message' => 'Item removed']);
    }

    public function save(): void
    {
        $this->validate([
            'issues.account_id' => ['required', 'exists:accounts,id'],
            'issues.date' => ['required', 'date'],
        ], [
            'issues.account_id.required' => 'Please select a customer.',
            'issues.date.required' => 'Please select a date.',
        ]);

        try {
            if (empty($this->items)) {
                throw new Exception('Please add at least one product with quantity in or quantity out.');
            }

            $payload = [
                'type' => $this->type,
                'account_id' => (int) $this->issues['account_id'],
                'date' => $this->issues['date'],
                'remarks' => $this->issues['remarks'] ?? '',
                'items' => [],
            ];

            foreach ($this->items as $item) {
                $qtyIn = (float) ($item['quantity_in'] ?? 0);
                $qtyOut = (float) ($item['quantity_out'] ?? 0);
                $qty = $this->isReturnMode() ? $qtyIn : $qtyOut;

                if ($qty <= 0) {
                    continue;
                }

                $payload['items'][] = [
                    'id' => $item['id'] ?? null,
                    'product_id' => (int) $item['product_id'],
                    'quantity_in' => $this->isReturnMode() ? $qty : 0,
                    'quantity_out' => $this->isReturnMode() ? 0 : $qty,
                ];
            }

            if (empty($payload['items'])) {
                throw new Exception('Please enter quantity for at least one product.');
            }

            DB::beginTransaction();

            if ($this->table_id) {
                $response = (new UpdateAction())->execute($payload, $this->table_id);
            } else {
                $response = (new CreateAction())->execute($payload);
            }

            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);

            if ($this->table_id) {
                $this->redirect(route('issue::view', $this->table_id));
            } else {
                $this->redirect(route('issue::view', $response['data']->id));
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.issue.page');
    }

    public function isReturnMode(): bool
    {
        return $this->type === 'return';
    }
}
