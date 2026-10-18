<?php

namespace App\Actions\V1\Sale;

use App\Http\Requests\V1\Sale\ItemIndexRequest;
use App\Http\Resources\V1\Sale\SaleItemListResource;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;

class ItemListAction
{
    /**
     * Every sold line, one row each — not grouped by product the way the item
     * report is. The sale-level filters run against the parent sale, so a line
     * is listed exactly when its invoice would be on the invoice list.
     *
     * @return array<string, mixed>
     */
    public function execute(ItemIndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $user = $request->user();

        // Same rule as ListAction: a non-admin employee sees only the lines of
        // sales they rang up, whatever mine_only says.
        $scopeToSelf = $user && (! empty($filters['mine_only']) || $user->seesOnlyOwnRecords());

        // Searched per line below (product, code, barcode, invoice, customer), so
        // it is kept out of the sale filter, which only knows invoice fields.
        $search = trim((string) ($filters['search'] ?? ''));
        $saleIds = Sale::query()
            ->filter(array_merge($filters, ['search' => '']))
            ->when($scopeToSelf, fn ($q) => $q->where('sales.created_by', $user->id))
            ->select('sales.id');

        $query = $this->baseQuery($saleIds, $search);

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as line_count, COALESCE(SUM(sale_items.quantity), 0) as quantity, COALESCE(SUM(sale_items.discount), 0) as discount, COALESCE(SUM(sale_items.total), 0) as total')
            ->first();

        $sortColumn = match ($filters['sort_by']) {
            'total' => 'sale_items.total',
            'quantity' => 'sale_items.quantity',
            'id' => 'sale_items.id',
            default => 'sales.date',
        };

        $items = $query
            ->select('sale_items.*')
            ->with([
                'sale:id,invoice_no,date,status,account_id,customer_name,created_by',
                'sale.account:id,name',
                'sale.createdUser:id,name',
                'product:id,name,code,barcode',
                'unit:id,name',
                'employee:id,name',
            ])
            ->orderBy($sortColumn, $filters['sort_direction'])
            ->orderBy('sale_items.id', 'desc')
            ->paginate($filters['per_page']);

        return [
            'data' => SaleItemListResource::collection($items->items()),
            'summary' => [
                'lines' => (int) $summary->getAttribute('line_count'),
                'quantity' => round((float) $summary->getAttribute('quantity'), 3),
                'discount' => round((float) $summary->getAttribute('discount'), 2),
                'total' => round((float) $summary->getAttribute('total'), 2),
            ],
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'has_more_pages' => $items->hasMorePages(),
            ],
        ];
    }

    /**
     * @param  Builder<Sale>  $saleIds
     * @return Builder<SaleItem>
     */
    protected function baseQuery(Builder $saleIds, string $search): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIn('sale_items.sale_id', $saleIds)
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($q) use ($search): void {
                    $q->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.code', 'like', "%{$search}%")
                        ->orWhere('products.barcode', 'like', "%{$search}%")
                        ->orWhere('products.barcode_number', 'like', "%{$search}%")
                        ->orWhere('sales.invoice_no', 'like', "%{$search}%")
                        ->orWhere('sales.reference_no', 'like', "%{$search}%")
                        ->orWhere('sales.customer_name', 'like', "%{$search}%")
                        ->orWhere('sales.customer_mobile', 'like', "%{$search}%");
                });
            });
    }
}
