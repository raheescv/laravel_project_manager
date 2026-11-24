<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

/**
 * Moves a commission one approval step forward. Owner approval is optional:
 * Finance can approve straight after the CEO. Finance approval posts the
 * expense journal and assigns the commission invoice number.
 */
class ApproveAction
{
    public const STAGE_CEO = 'ceo';

    public const STAGE_OWNER = 'owner';

    public const STAGE_FINANCE = 'finance';

    /** @var array<string, array<int, CommissionStatus>> */
    private const ALLOWED_FROM = [
        self::STAGE_CEO => [CommissionStatus::Submitted],
        self::STAGE_OWNER => [CommissionStatus::CeoApproved],
        self::STAGE_FINANCE => [CommissionStatus::CeoApproved, CommissionStatus::OwnerApproved],
    ];

    public function execute(int $id, string $stage, int $userId): array
    {
        try {
            if (! isset(self::ALLOWED_FROM[$stage])) {
                throw new Exception('Unknown approval step.', 1);
            }

            $model = RentOutCommission::findOrFail($id);
            if (! in_array($model->status, self::ALLOWED_FROM[$stage], true)) {
                throw new Exception('This commission is '.$model->status->label().' and cannot be approved at this step.', 1);
            }

            $model->update(match ($stage) {
                self::STAGE_CEO => ['status' => CommissionStatus::CeoApproved, 'ceo_approved_by' => $userId, 'ceo_approved_at' => now()],
                self::STAGE_OWNER => ['status' => CommissionStatus::OwnerApproved, 'owner_approved_by' => $userId, 'owner_approved_at' => now()],
                self::STAGE_FINANCE => [
                    'status' => CommissionStatus::FinanceApproved,
                    'finance_approved_by' => $userId,
                    'finance_approved_at' => now(),
                    'invoice_no' => $model->invoice_no ?: 'COM-'.str_pad((string) $model->id, 5, '0', STR_PAD_LEFT),
                ],
            } + ['updated_by' => $userId]);

            if ($stage === self::STAGE_FINANCE) {
                $response = (new JournalEntryAction())->execute($model->refresh(), $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Commission '.$model->status->label().'.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
