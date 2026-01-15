<?php

namespace App\Actions\Journal;

use Exception;

class GeneralVoucherJournalEntryAction
{
    public function execute($userId, $data, $id = null)
    {
        try {
            $data['created_by'] = $userId;
            $data['source'] = $data['source'] ?? 'General Voucher';
            $data['description'] = $data['description'] ?? 'General Voucher';

            // If entries are already provided, use them; otherwise, create from debit/credit (backward compatibility)
            if (! isset($data['entries']) || empty($data['entries'])) {
                // Backward compatibility: create entries from debit_id, credit_id, amount
                if (isset($data['debit_id']) && isset($data['credit_id']) && isset($data['amount'])) {
                    $entries = [];
                    $entries[] = [
                        'account_id' => $data['debit_id'],
                        // 'counter_account_id' => $data['credit_id'],
                        'debit' => $data['amount'],
                        'credit' => 0,
                        'created_by' => $userId,
                        'remarks' => $data['remarks'] ?? null,
                    ];
                    $entries[] = [
                        'account_id' => $data['credit_id'],
                        // 'counter_account_id' => $data['debit_id'],
                        'debit' => 0,
                        'credit' => $data['amount'],
                        'created_by' => $userId,
                        'remarks' => $data['remarks'] ?? null,
                    ];
                    $data['entries'] = $entries;
                }
            } else {
                // Set counter_account_id for each entry (first other account with opposite transaction)
                $entries = $data['entries'];
                foreach ($entries as $index => &$entry) {
                    $entry['created_by'] = $userId;
                    // Find a counter account (another entry with opposite debit/credit)
                    if (! isset($entry['counter_account_id'])) {
                        foreach ($entries as $otherIndex => $otherEntry) {
                            if ($index !== $otherIndex && (($entry['debit'] > 0 && $otherEntry['credit'] > 0) || ($entry['credit'] > 0 && $otherEntry['debit'] > 0))) {
                                // $entry['counter_account_id'] = $otherEntry['account_id'];
                                break;
                            }
                        }
                    }
                }
                $data['entries'] = $entries;
            }
            if ($id) {
                $response = (new UpdateAction())->execute($data, $id);
            } else {
                $response = (new CreateAction())->execute($data);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = $id ? 'Successfully Updated General Voucher' : 'Successfully Created General Voucher';
            $return['data'] = $response['data'];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
