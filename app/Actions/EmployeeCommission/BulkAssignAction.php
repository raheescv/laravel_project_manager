<?php

namespace App\Actions\EmployeeCommission;

use App\Models\EmployeeCommission;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BulkAssignAction
{
    /**
     * Build the product query matching the given bulk filters.
     * Only non-empty filters are applied.
     */
    public function productQuery(array $filters): Builder
    {
        return Product::query()
            ->when($filters['department_ids'] ?? [], fn (Builder $q, $ids) => $q->whereIn('department_id', (array) $ids))
            ->when($filters['main_category_ids'] ?? [], fn (Builder $q, $ids) => $q->whereIn('main_category_id', (array) $ids))
            ->when($filters['sub_category_ids'] ?? [], fn (Builder $q, $ids) => $q->whereIn('sub_category_id', (array) $ids))
            ->when($filters['brand_ids'] ?? [], fn (Builder $q, $ids) => $q->whereIn('brand_id', (array) $ids));
    }

    public function execute(array $data): array
    {
        try {
            $employeeId = $data['employee_id'] ?? null;
            $percentage = $data['commission_percentage'] ?? null;
            $overwrite = (bool) ($data['overwrite'] ?? false);

            $productIds = $this->productQuery($data)->pluck('id');

            if ($productIds->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No products or services matched the selected filters',
                ];
            }

            $existing = EmployeeCommission::where('employee_id', $employeeId)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();
            $existingSet = array_flip($existing);

            $tenantId = session('tenant_id');
            $now = now();

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $insertRows = [];

            DB::transaction(function () use ($productIds, $existingSet, $overwrite, $employeeId, $percentage, $tenantId, $now, &$created, &$updated, &$skipped, &$insertRows): void {
                foreach ($productIds as $productId) {
                    if (isset($existingSet[$productId])) {
                        if ($overwrite) {
                            EmployeeCommission::where('employee_id', $employeeId)
                                ->where('product_id', $productId)
                                ->update(['commission_percentage' => $percentage]);
                            $updated++;
                        } else {
                            $skipped++;
                        }

                        continue;
                    }

                    $insertRows[] = [
                        'tenant_id' => $tenantId,
                        'employee_id' => $employeeId,
                        'product_id' => $productId,
                        'commission_percentage' => $percentage,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $created++;
                }

                foreach (array_chunk($insertRows, 500) as $chunk) {
                    EmployeeCommission::insert($chunk);
                }
            });

            $parts = [];
            if ($created) {
                $parts[] = "{$created} created";
            }
            if ($updated) {
                $parts[] = "{$updated} updated";
            }
            if ($skipped) {
                $parts[] = "{$skipped} skipped (already existed)";
            }

            return [
                'success' => true,
                'message' => 'Bulk commission applied: '.implode(', ', $parts ?: ['nothing to do']),
                'data' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'matched' => $productIds->count(),
                ],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
