<?php

namespace App\Actions\V1\Employee;

use App\Http\Requests\V1\Employee\IndexRequest;
use App\Http\Resources\V1\Employee\EmployeeResource;
use App\Models\User;

class ListAction
{
    /**
     * List active employees (stylists) with optional search/branch filters and
     * pagination. Scoped to users with type = 'employee' so the mobile POS can
     * assign a stylist to a sale / line.
     */
    public function execute(IndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = User::query()->employee()->active()->with('designation');

        $query->when($filters['search'] ?? null, function ($q, $value) {
            $value = trim($value);

            return $q->where(function ($sub) use ($value) {
                $sub->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('mobile', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            });
        });

        $query->when($filters['branch_id'] ?? null, function ($q, $value) {
            return $q->whereHas('branches', function ($sub) use ($value) {
                $sub->where('user_has_branches.branch_id', $value);
            });
        });

        $query->orderBy($filters['sort_by'], $filters['sort_direction']);

        if ($filters['sort_by'] !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $employees = $query->paginate($filters['per_page']);

        return [
            'data' => EmployeeResource::collection($employees->items()),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'has_more_pages' => $employees->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null && $value !== '';
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }
}
