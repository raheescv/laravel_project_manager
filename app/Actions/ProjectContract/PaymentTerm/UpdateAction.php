<?php

namespace App\Actions\ProjectContract\PaymentTerm;

use App\Models\ProjectContractPaymentTerm;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * A term that has payments keeps its amount at or above what was paid.
     *
     * @param  array{label: string, due_date: string, amount: float, remarks?: string|null}  $data
     * @return array{success: bool, message: string, data?: ProjectContractPaymentTerm, warnings?: array<int, string>}
     */
    public function execute(array $data, int $id, int $userId): array
    {
        try {
            $term = DB::transaction(function () use ($data, $id, $userId): ProjectContractPaymentTerm {
                $term = ProjectContractPaymentTerm::with('contract')->lockForUpdate()->findOrFail($id);
                if (! $term->contract?->isActive()) {
                    throw new Exception('Terms can only be changed on an active contract.');
                }
                if ((float) $data['amount'] < (float) $term->paid) {
                    throw new Exception('The amount cannot be less than the '.number_format((float) $term->paid, 2).' already paid on this term.');
                }

                $term->update([
                    'label' => $data['label'],
                    'due_date' => $data['due_date'],
                    'amount' => $data['amount'],
                    'remarks' => $data['remarks'] ?? null,
                    'updated_by' => $userId,
                ]);

                return $term;
            });

            return ['success' => true, 'message' => 'Payment term updated.', 'data' => $term, 'warnings' => TermWarnings::for($term->contract)];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
