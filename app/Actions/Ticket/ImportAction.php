<?php

namespace App\Actions\Ticket;

use App\Support\Ticket\TicketImportSheet;
use Exception;
use Illuminate\Support\Facades\DB;

class ImportAction
{
    /**
     * Save every row the review marks ready; skipped and invalid rows are left out.
     *
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, ?int>  $mappings
     * @param  array{default_status?: string, duplicates?: string}  $options
     * @return array{success: bool, message: string, data?: array{created: int, skipped: int, failed: int}}
     */
    public function execute(array $rows, array $mappings, array $options, int $userId): array
    {
        try {
            if (! isset($mappings['title'])) {
                throw new Exception('Match a column to Title before importing.');
            }

            $reviewed = TicketImportSheet::review($rows, $mappings, $options);
            $summary = TicketImportSheet::summary($reviewed);

            DB::transaction(function () use ($reviewed, $userId): void {
                foreach ($reviewed as $row) {
                    if ($row['state'] !== 'ready') {
                        continue;
                    }
                    $response = (new CreateAction())->execute($row['data'], $userId);
                    if (! $response['success']) {
                        throw new Exception("Row {$row['line']}: {$response['message']}", 1);
                    }
                }
            });

            $return['success'] = true;
            $return['message'] = "Imported {$summary['ready']} ticket".($summary['ready'] === 1 ? '' : 's').'.';
            $return['data'] = ['created' => $summary['ready'], 'skipped' => $summary['skip'], 'failed' => $summary['error']];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
