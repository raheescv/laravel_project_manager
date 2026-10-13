<?php

namespace App\Actions\ProjectContract;

use App\Enums\ProjectContract\ContractStatus;
use App\Models\Account;
use App\Models\ProjectContract;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /** Fields that drive money; once a contract is approved they change only through an extension. */
    public const LOCKED_WHEN_APPROVED = ['account_id', 'start_date', 'end_date', 'no_of_terms', 'payment_frequency', 'contract_amount'];

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: ProjectContract}
     */
    public function execute(array $data, int $id, int $userId): array
    {
        try {
            $contract = DB::transaction(function () use ($data, $id, $userId): ProjectContract {
                $contract = ProjectContract::lockForUpdate()->findOrFail($id);

                if (in_array($contract->status, [ContractStatus::Cancelled, ContractStatus::Completed], true)) {
                    throw new Exception('A '.strtolower($contract->status->label()).' contract cannot be edited.');
                }

                if (! $contract->status->isEditable()) {
                    $this->guardLockedFields($contract, $data);
                    $contract->fill(array_intersect_key($data, array_flip(['project_name', 'remark'])));
                } else {
                    validationHelper(ProjectContract::rules(), $data);
                    if (! Account::vendor()->whereKey($data['account_id'])->exists()) {
                        throw new Exception('Choose a vendor account for this contract.');
                    }
                    $contract->fill(array_intersect_key($data, array_flip(['project_name', 'account_id', 'start_date', 'end_date', 'no_of_terms', 'payment_frequency', 'contract_amount', 'remark'])));
                }

                $contract->updated_by = $userId;
                $contract->save();

                if (! empty($data['submit']) && $contract->status->isEditable()) {
                    $response = (new SubmitAction())->execute($contract->id, $userId);
                    if (! $response['success']) {
                        throw new Exception($response['message']);
                    }
                }

                return $contract->refresh();
            });

            return ['success' => true, 'message' => 'Contract updated.', 'data' => $contract];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardLockedFields(ProjectContract $contract, array $data): void
    {
        foreach (self::LOCKED_WHEN_APPROVED as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $current = $contract->getAttribute($field);
            $current = match (true) {
                $current instanceof \BackedEnum => $current->value,
                $current instanceof \DateTimeInterface => $current->format('Y-m-d'),
                default => $current,
            };

            $changed = is_numeric($current) && is_numeric($data[$field])
                ? round((float) $current, 2) !== round((float) $data[$field], 2)
                : (string) $current !== (string) $data[$field];

            if ($changed) {
                throw new Exception('The vendor, dates, amount and terms of an approved contract change only through an extension.');
            }
        }
    }
}
