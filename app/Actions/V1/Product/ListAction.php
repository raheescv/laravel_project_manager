<?php

namespace App\Actions\V1\Product;

use App\Http\Requests\V1\Product\SearchRequest;
use App\Http\Resources\V1\InventoryResource;
use App\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAction
{
    /**
     * List sellable products, sorted alphabetically, with optional keyword search.
     */
    public function execute(SearchRequest $request): array
    {
        $branchId = $request->user()?->default_branch_id;
        $searchKey = $request->validated('searchKey');
        $barcode = $request->validated('barcode');
        $type = $request->validated('type');

        $list = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('inventories.branch_id', $branchId)
            ->where('products.is_selling', true)
            ->when($searchKey, function ($query, $value) {
                $value = trim($value);
                $query->where('products.name', 'like', "%{$value}%")->orWhere('products.code', 'like', "%{$value}%");
            })
            ->when($barcode, function ($query, $value) {
                $query->where('inventories.barcode', $value);
            })
            ->when($type, function ($query, $value) {
                $query->where('products.type', $value);
            })
            ->select('inventories.*', 'products.name', 'products.code', 'products.type', 'products.thumbnail', 'products.mrp')
            ->orderBy('products.name')
            ->paginate($request->perPage());


        return [
            'data' => InventoryResource::collection($list),
            'pagination' => $this->pagination($list),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }
}
