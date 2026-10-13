<?php

namespace App\Http\Controllers\ProjectContract\Concerns;

use App\Http\Resources\ProjectContract\ContractDetailResource;
use App\Models\ProjectContract;
use Illuminate\Http\JsonResponse;

/**
 * Every write on a contract answers with the whole, fresh contract, so the
 * view redraws from one payload instead of patching its tabs.
 */
trait RespondsWithContract
{
    protected function loadDetail(int $id): ProjectContract
    {
        return ProjectContract::with([
            'vendor', 'branch:id,name', 'creator:id,name',
            'terms' => fn ($query) => $query->withCount('allocations'),
            'payments.paymentMethod:id,name', 'payments.creator:id,name', 'payments.allocations.term:id,label',
            'extensions.creator:id,name',
            'securities.paymentMethod:id,name', 'securities.settlements', 'securities.contract:id,end_date',
            'documents.creator:id,name',
            'notes.creator:id,name',
        ])->findOrFail($id);
    }

    /**
     * @param  array{success: bool, message: string, warnings?: array<int, string>}  $response
     */
    protected function result(array $response, ?int $contractId): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        $contract = $contractId ? ProjectContract::find($contractId) : null;
        $payload = $contract ? ContractDetailResource::make($this->loadDetail($contract->id))->resolve() : null;

        if ($payload !== null && ! empty($response['warnings'])) {
            $payload['warnings'] = $response['warnings'];
        }

        return $this->sendSuccess($payload, $response['message']);
    }
}
