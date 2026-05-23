<?php

namespace App\Actions\V1\Customer;

use App\Http\Requests\V1\Customer\IndexRequest;
use App\Http\Resources\V1\Customer\CustomerResource;
use App\Models\Account;

class ListAction
{
    /**
     * List customers with optional mobile/search filters and pagination.
     * Filters are scoped to accounts with model = 'Customer'.
     */
    public function execute(IndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = Account::query()->customer();

        $query->when($filters['mobile'] ?? null, function ($q, $value) {
            $value = trim($value);

            return $q->where(function ($sub) use ($value) {
                $sub->where('mobile', 'like', "%{$value}%")
                    ->orWhere('whatsapp_mobile', 'like', "%{$value}%");
            });
        });

        $query->when($filters['search'] ?? null, function ($q, $value) {
            $value = trim($value);

            return $q->where(function ($sub) use ($value) {
                $sub->where('name', 'like', "%{$value}%")
                    ->orWhere('alias_name', 'like', "%{$value}%")
                    ->orWhere('mobile', 'like', "%{$value}%")
                    ->orWhere('whatsapp_mobile', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            });
        });

        $query->orderBy($filters['sort_by'], $filters['sort_direction']);

        if ($filters['sort_by'] !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $customers = $query->paginate($filters['per_page']);

        return [
            'data' => CustomerResource::collection($customers->items()),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'has_more_pages' => $customers->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null && $value !== '';
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }
}
