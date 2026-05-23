<?php

namespace App\Actions\V1\Product;

use App\Http\Requests\V1\Product\SearchRequest;
use App\Http\Resources\V1\Product\ProductResource;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAction
{
    /**
     * List sellable products, sorted alphabetically, with optional keyword search.
     */
    public function execute(SearchRequest $request): array
    {
        $branchId = $request->user()?->default_branch_id;
        $search = $request->validated('searchKey');

        $products = Product::query()
            ->product()
            ->where('is_selling', true)
            ->with(['unit:id,name', 'mainCategory:id,name', 'brand:id,name'])
            ->withSum(['inventories' => fn ($query) => $query->where('branch_id', $branchId)], 'quantity')
            ->when($search, function ($query, $value) {
                $value = trim($value);
                $query->where(function ($sub) use ($value) {
                    $sub->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('barcode_number', 'like', "%{$value}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->perPage());

        return [
            'data' => ProductResource::collection($products->items()),
            'pagination' => $this->pagination($products),
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
