<?php

namespace App\Actions\V1\Branch;

use App\Http\Requests\V1\GetBranchesRequest;
use App\Models\Branch;

class GetBranchesAction
{
    /**
     * Execute the action to get all branches with optional filtering.
     */
    public function execute(GetBranchesRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = Branch::select(['id', 'name', 'code', 'location', 'mobile'])
            // Back-office branches (warehouse / head office / online ledger) are
            // flagged in Settings → Branch and never reach the public catalog.
            ->unless($filters['include_hidden'] ?? false, function ($q) {
                return $q->where('exclude_from_showcase', false);
            })
            ->when($filters['query'] ?? null, function ($q, $value) {
                return $q->where(function ($query) use ($value) {
                    $query->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('location', 'like', "%{$value}%");
                });
            })
            ->orderBy('name');

        $branches = $query->get();

        return $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->location,
                'code' => $branch->code,
                'location' => $branch->location,
                'mobile' => $branch->mobile,
            ];
        })->toArray();
    }
}
