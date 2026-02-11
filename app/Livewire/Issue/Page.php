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

    public array $issues = [];

    public array $items = [];

    public array $accounts = [];

    public string $account_id = '';

    public string $product_id = '';

    public string $add_quantity_in = '0';

    public string $add_quantity_out = '0';

    public string $add_date = '';

    public string $barcode_input = '';

    public function mount(?int $id = null): void
    {
        $this->table_id = $id;
        $this->items = [];

        if ($this->table_id) {
            $issue = Issue::with('account', 'items.product')->find($this->table_id);
            if (! $issue) {
                $this->redirect(route('issue::index'));

                return;
            }
            $this->accounts = [$issue->account_id => $issue->account?->name ?? 'Customer'];
            $this->issues = [
                'account_id' => $issue->account_id,
                'remarks' => $issue->remarks ?? '',
            ];
            $this->add_date = date('Y-m-d');
            foreach ($issue->items as $item) {
                $key = 'item_'.($item->id ?? uniqid());
                $this->items[$key] = [
                    'id' => $item->id,
                    'key' => $key,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity_in' => (string) $item->quantity_in,
                    'quantity_out' => (string) $item->quantity_out,
                    'date' => $item->date?->format('Y-m-d') ?? '',
                ];
            }
        } else {
            $this->issues = [
                'account_id' => '',
                'remarks' => '',
            ];
            $this->add_date = date('Y-m-d');
        }
    }

    public function updatedIssuesDate($value): void
    {
        if ($value && ! $this->table_id) {
            $this->add_date = $value;
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
        $itemDate = $this->add_date;

        if ($qtyIn <= 0 && $qtyOut <= 0) {
            $this->dispatch('error', ['message' => 'Please enter quantity in (return) or quantity out (issue).']);

            return;
        }

        if ($qtyIn > 0 && $qtyOut > 0) {
            $this->dispatch('error', ['message' => 'Only one of quantity in or quantity out can be filled per item.']);

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
            'quantity_in' => (string) $qtyIn,
            'quantity_out' => (string) $qtyOut,
            'date' => $itemDate,
        ];

        $this->product_id = '';
        $this->add_quantity_in = '0';
        $this->add_quantity_out = '0';
        $this->add_date = $this->issues['date'] ?? date('Y-m-d');
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
        $this->add_quantity_out = '1';
        $this->add_quantity_in = '0';
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
        ], [
            'issues.account_id.required' => 'Please select a customer.',
        ]);

        try {
            if (empty($this->items)) {
                throw new Exception('Please add at least one product with quantity in or quantity out.');
            }

            $payload = [
                'account_id' => (int) $this->issues['account_id'],
                'remarks' => $this->issues['remarks'] ?? '',
                'items' => [],
            ];

            foreach ($this->items as $item) {
                $qtyIn = (float) ($item['quantity_in'] ?? 0);
                $qtyOut = (float) ($item['quantity_out'] ?? 0);
                if ($qtyIn <= 0 && $qtyOut <= 0) {
                    continue;
                }
                if ($qtyIn > 0 && $qtyOut > 0) {
                    throw new Exception('Only one of quantity in or quantity out can be filled per item (product: '.($item['name'] ?? '').').');
                }
                $payload['items'][] = [
                    'id' => $item['id'] ?? null,
                    'product_id' => (int) $item['product_id'],
                    'quantity_in' => $qtyIn,
                    'quantity_out' => $qtyOut,
                    'date' => $item['date'],
                ];
            }

            if (empty($payload['items'])) {
                throw new Exception('Please enter quantity in (return) or quantity out (issue) for at least one product.');
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
}
