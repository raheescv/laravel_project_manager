<?php

namespace App\Actions\RentOut\Comparison;

use App\Models\RentOutComparison;
use Carbon\CarbonImmutable;

class StoreRentOutComparisonAction
{
    /**
     * @param  array<string, mixed>  $comparison
     * @return array{stored: int, deleted: int}
     */
    public function execute(array $comparison): array
    {
        $comparedAt = CarbonImmutable::parse($comparison['meta']['generated_at']);
        $now = now();
        $rows = collect($comparison['records'])->map(function (array $record) use ($comparedAt, $now): array {
            return [
                'rent_out_id' => $record['id'],
                'agreement_type' => $record['agreement_type'],
                'category' => $record['category'],
                'status' => $record['status'],
                'is_booking' => $record['is_booking'],
                'exists_old' => $record['exists_old'],
                'exists_new' => $record['exists_new'],
                'matches' => $record['matches'],
                'difference_count' => $record['difference_count'],
                'old_url' => $record['old_url'],
                'new_url' => $record['new_url'],
                'payload' => json_encode([
                    'header' => $record['header'],
                    'tabs' => $record['tabs'],
                    'ledger' => $record['ledger'],
                ], JSON_THROW_ON_ERROR),
                'compared_at' => $comparedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values();

        $model = new RentOutComparison();

        return $model->getConnection()->transaction(function () use ($comparison, $rows): array {
            foreach ($rows->chunk(250) as $chunk) {
                RentOutComparison::query()->upsert(
                    $chunk->all(),
                    ['rent_out_id'],
                    [
                        'agreement_type',
                        'category',
                        'status',
                        'is_booking',
                        'exists_old',
                        'exists_new',
                        'matches',
                        'difference_count',
                        'old_url',
                        'new_url',
                        'payload',
                        'compared_at',
                        'updated_at',
                    ],
                );
            }

            $deleted = $this->deleteStaleRows($comparison, $rows->pluck('rent_out_id')->all());

            return ['stored' => $rows->count(), 'deleted' => $deleted];
        });
    }

    /**
     * @param  array<string, mixed>  $comparison
     * @param  array<int>  $comparedIds
     */
    private function deleteStaleRows(array $comparison, array $comparedIds): int
    {
        if ($comparison['meta']['selected_ids'] !== []) {
            return 0;
        }

        $query = RentOutComparison::query()
            ->when(
                $comparison['meta']['type'],
                fn ($query, string $type) => $query->where('agreement_type', $type),
            );

        if ($comparedIds !== []) {
            $query->whereNotIn('rent_out_id', $comparedIds);
        }

        return $query->delete();
    }
}
