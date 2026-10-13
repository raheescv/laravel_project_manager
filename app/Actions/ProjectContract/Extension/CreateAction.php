<?php

namespace App\Actions\ProjectContract\Extension;

use App\Actions\ProjectContract\Approval\RequestAction;
use App\Enums\ProjectContract\ApprovalStatus;
use App\Models\ProjectContract;
use App\Models\ProjectContractExtension;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Ask to extend an active contract. Nothing on the contract changes until
     * every approval level agrees; with no levels it applies at once.
     *
     * @param  array{project_contract_id: int, new_end_date: string, additional_amount?: float|null, additional_terms?: int|null, reason: string}  $data
     * @return array{success: bool, message: string, data?: ProjectContractExtension}
     */
    public function execute(array $data, int $userId, ?UploadedFile $document = null): array
    {
        try {
            $extension = DB::transaction(function () use ($data, $userId, $document): ProjectContractExtension {
                $contract = ProjectContract::lockForUpdate()->findOrFail($data['project_contract_id']);
                if (! $contract->isActive()) {
                    throw new Exception('Only an active contract can be extended.');
                }
                if ($contract->extensions()->where('status', ApprovalStatus::PendingApproval)->exists()) {
                    throw new Exception('An extension is already waiting for approval on this contract.');
                }

                $additionalAmount = round((float) ($data['additional_amount'] ?? 0), 2);
                $additionalTerms = (int) ($data['additional_terms'] ?? 0);
                $newEnd = $data['new_end_date'];
                if ($newEnd < $contract->end_date->toDateString()) {
                    throw new Exception('The new end date cannot be before the current end date.');
                }
                if ($newEnd === $contract->end_date->toDateString() && $additionalAmount <= 0 && $additionalTerms <= 0) {
                    throw new Exception('Change the end date, the amount or the planned terms to extend the contract.');
                }

                $extension = ProjectContractExtension::create([
                    'project_contract_id' => $contract->id,
                    'old_end_date' => $contract->end_date,
                    'new_end_date' => $newEnd,
                    'old_amount' => $contract->contract_amount,
                    'additional_amount' => $additionalAmount,
                    'old_no_of_terms' => $contract->no_of_terms,
                    'additional_terms' => $additionalTerms,
                    'reason' => $data['reason'],
                    'document_path' => $document?->store('project-contracts/'.$contract->id, 'public'),
                    'status' => ApprovalStatus::PendingApproval,
                    'created_by' => $userId,
                ]);

                $response = (new RequestAction())->execute($extension, $contract, $extension->newAmount(), $userId);
                if (! $response['success']) {
                    throw new Exception($response['message']);
                }

                if ($response['data']['levels'] === 0) {
                    $applied = (new ApplyAction())->execute($extension, $userId);
                    if (! $applied['success']) {
                        throw new Exception($applied['message']);
                    }
                }

                return $extension->refresh();
            });

            $message = $extension->status === ApprovalStatus::Approved ? 'Contract extended.' : 'Extension sent for approval.';

            return ['success' => true, 'message' => $message, 'data' => $extension];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
