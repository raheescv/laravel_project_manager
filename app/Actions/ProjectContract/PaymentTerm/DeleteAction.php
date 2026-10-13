<?php

namespace App\Actions\ProjectContract\PaymentTerm;

use App\Models\ProjectContractPaymentTerm;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * @return array{success: bool, message: string, data?: ProjectContractPaymentTerm}
     */
    public function execute(int $id, int $userId): array
    {
        try {
            $term = DB::transaction(function () use ($id, $userId): ProjectContractPaymentTerm {
                $term = ProjectContractPaymentTerm::lockForUpdate()->findOrFail($id);
                if ($term->allocations()->exists()) {
                    throw new Exception('This term has payments recorded against it and cannot be deleted.');
                }

                $term->update(['updated_by' => $userId]);
                $term->delete();

                return $term;
            });

            return ['success' => true, 'message' => 'Payment term deleted.', 'data' => $term];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
