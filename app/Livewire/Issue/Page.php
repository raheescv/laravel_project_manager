<?php

namespace App\Livewire\Issue;

use App\Actions\Issue\CreateAction;
use App\Actions\Issue\UpdateAction;
use App\Models\Inventory;
use App\Models\Issue;
use App\Models\IssueItem;
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

    public string $inventory_id = '';

    public string $add_quantity_in = '0';

    public string $add_quantity_out = '0';

    public string $barcode_input = '';

    public ?int $source_issue_id = null;

    public function mount(?int $id = null, string $type = 'issue'): void
    {
        $this->table_id = $id;
        $this->items = [];
        $this->type = in_array($type, ['issue', 'return'], true) ? $type : 'issue';
        $this->source_issue_id = (int) request()->query('source_issue_id') ?: null;

        if ($this->table_id) {
            $issue = Issue::with('account', 'sourceIssue:id,date', 'items.product:id,name,thumbnail', 'items.inventory.product:id,name,thumbnail')->find($this->table_id);
            if (! $issue) {
                $this->redirect(route('issue::index'));

                return;
            }
            $this->type = $issue->type;
            $this->accounts = [$issue->account_id => $issue->account?->name ?? 'Customer'];
            $this->source_issue_id = $issue->source_issue_id;
            $this->issues = [
                'account_id' => $issue->account_id,
                'type' => $issue->type,
                'source_issue_id' => $issue->source_issue_id,
                'date' => $issue->date?->format('Y-m-d') ?? date('Y-m-d'),
                'remarks' => $issue->remarks ?? '',
            ];
            foreach ($issue->items->values() as $index => $item) {
                $key = 'item_'.($item->id ?? uniqid());
                $inventoryProduct = $item->inventory?->product;
                $resolvedInventoryId = $item->inventory_id ?: $this->resolveInventoryIdForProduct((int) $item->product_id);
                $this->items[$key] = [
                    'id' => $item->id,
                    'key' => $key,
                    'source_issue_item_id' => $item->source_issue_item_id,
                    'source_item_order' => $item->source_item_order ?: ($index + 1),
                    'inventory_id' => $resolvedInventoryId,
                    'product_id' => $inventoryProduct?->id ?? $item->product_id,
                    'name' => $inventoryProduct?->name ?? $item->product?->name,
                    'thumbnail' => $inventoryProduct?->thumbnail ?? $item->product?->thumbnail,
                    'quantity_in' => (string) $item->quantity_in,
                    'quantity_out' => (string) $item->quantity_out,
                ];
            }
        } else {
            $this->issues = [
                'account_id' => '',
                'source_issue_id' => $this->source_issue_id,
                'date' => date('Y-m-d'),
                'remarks' => '',
            ];

            if ($this->isReturnMode() && $this->source_issue_id) {
                $this->prefillReturnItemsFromSourceIssue($this->source_issue_id);
            }
        }
    }

    public function updatedInventoryId(): void
    {
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
        $inventoryId = $this->inventory_id;
        if (! $inventoryId) {
            $this->dispatch('error', ['message' => 'Please select inventory item.']);

            return;
        }

        $qtyIn = (float) $this->add_quantity_in;
        $qtyOut = (float) $this->add_quantity_out;
        $qty = $this->isReturnMode() ? $qtyIn : $qtyOut;
        if ($qty <= 0) {
            $this->dispatch('error', ['message' => 'Please enter quantity.']);

            return;
        }

        $inventory = Inventory::with('product:id,name,thumbnail')->find((int) $inventoryId);
        if (! $inventory || ! $inventory->product) {
            $this->dispatch('error', ['message' => 'Inventory item not found.']);

            return;
        }

        $key = 'item_'.uniqid();
        $this->items[$key] = [
            'id' => null,
            'key' => $key,
            'source_issue_item_id' => null,
            'source_item_order' => null,
            'inventory_id' => (int) $inventoryId,
            'product_id' => $inventory->product_id,
            'name' => $inventory->product->name,
            'thumbnail' => $inventory->product->thumbnail,
            'quantity_in' => (string) ($this->isReturnMode() ? $qty : 0),
            'quantity_out' => (string) ($this->isReturnMode() ? 0 : $qty),
        ];

        $this->inventory_id = '';
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

        $inventory = Inventory::with('product:id,name,thumbnail')
            ->whereNull('employee_id')
            ->where('branch_id', session('branch_id'))
            ->where('barcode', $barcode)
            ->first();
        if (! $inventory || ! $inventory->product) {
            $this->dispatch('error', ['message' => 'No inventory found for barcode: '.$barcode]);
            $this->barcode_input = '';

            return;
        }

        $this->inventory_id = (string) $inventory->id;
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
                'source_issue_id' => $this->isReturnMode() ? ($this->source_issue_id ?: null) : null,
                'date' => $this->issues['date'],
                'remarks' => $this->issues['remarks'] ?? '',
                'items' => [],
            ];

            foreach ($this->items as $item) {
                $qtyIn = (float) ($item['quantity_in'] ?? 0);
                $qtyOut = (float) ($item['quantity_out'] ?? 0);
                $qty = $this->isReturnMode() ? $qtyIn : $qtyOut;
                $inventoryId = (int) ($item['inventory_id'] ?? 0);
                $productId = (int) ($item['product_id'] ?? 0);

                if ($inventoryId <= 0 && $productId > 0) {
                    $inventoryId = (int) ($this->resolveInventoryIdForProduct($productId) ?? 0);
                }

                if ($qty <= 0) {
                    continue;
                }

                if ($inventoryId <= 0) {
                    throw new Exception('Inventory not found for one or more items. Please reselect the product.');
                }

                $payload['items'][] = [
                    'id' => $item['id'] ?? null,
                    'inventory_id' => $inventoryId,
                    'product_id' => $productId,
                    'source_issue_item_id' => $item['source_issue_item_id'] ?? null,
                    'source_item_order' => $item['source_item_order'] ?? null,
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

    private function resolveInventoryIdForProduct(int $productId): ?int
    {
        if ($productId <= 0) {
            return null;
        }

        return Inventory::query()
            ->whereNull('employee_id')
            ->where('branch_id', session('branch_id'))
            ->where('product_id', $productId)
            ->value('id');
    }

    private function prefillReturnItemsFromSourceIssue(int $sourceIssueId): void
    {
        $sourceIssue = Issue::with(['account:id,name', 'items' => fn ($q) => $q->orderBy('id'), 'items.product:id,name,thumbnail', 'items.inventory:id,product_id'])
            ->where('type', 'issue')
            ->find($sourceIssueId);

        if (! $sourceIssue) {
            return;
        }

        $this->accounts = [$sourceIssue->account_id => $sourceIssue->account?->name ?? 'Customer'];
        $this->issues['account_id'] = $sourceIssue->account_id;

        $rows = [];
        foreach ($sourceIssue->items->values() as $index => $sourceItem) {
            $issuedQty = max(0, (float) $sourceItem->quantity_out - (float) $sourceItem->quantity_in);
            if ($issuedQty <= 0) {
                continue;
            }

            $returnedQty = (float) IssueItem::query()
                ->join('issues', 'issues.id', '=', 'issue_items.issue_id')
                ->where('issues.type', 'return')
                ->where('issue_items.source_issue_item_id', $sourceItem->id)
                ->sum('issue_items.quantity_in');

            $availableQty = $issuedQty - $returnedQty;
            if ($availableQty <= 0) {
                continue;
            }

            $key = 'item_'.uniqid();
            $rows[$key] = [
                'id' => null,
                'key' => $key,
                'source_issue_item_id' => $sourceItem->id,
                'source_item_order' => $index + 1,
                'inventory_id' => $sourceItem->inventory_id ?: $this->resolveInventoryIdForProduct((int) $sourceItem->product_id),
                'product_id' => $sourceItem->product_id,
                'name' => $sourceItem->product?->name,
                'thumbnail' => $sourceItem->product?->thumbnail,
                'quantity_in' => (string) $availableQty,
                'quantity_out' => '0',
            ];
        }

        $this->items = $rows;
    }
}
