<?php

namespace App\Actions\Sale;

use App\Helpers\Facades\MoqSolutionsHelper;
use App\Models\SaleDaySession;
use Exception;

/**
 * Post a day session's revenue to the MOQ Solutions API.
 *
 * Run from the "Sync to MOQ" button on /sale/day-session/{id}, either while the
 * day is being closed or after it is closed. The amount is whatever the user
 * types: it is not derived from the drawer count. Every attempt leaves an
 * api_logs row (written by the helper); only a successful post stamps the
 * session with the amount, time and user.
 */
class SyncDaySessionToMoqAction
{
    /**
     * @return array{success: bool, message: string, data?: SaleDaySession}
     */
    public function execute(SaleDaySession $session, float $amount, int $userId): array
    {
        try {
            if (! $session->branch?->moq_sync) {
                throw new Exception('MOQ sync is not enabled for this branch.');
            }
            if ($amount < 0) {
                throw new Exception('The sync amount cannot be negative.');
            }

            $result = MoqSolutionsHelper::syncDayCloseAmount([
                'Date' => $session->opened_at->format('Y-m-d'),
                'Revenue' => $amount,
                'Outlet' => config('app.name').' '.$session->branch->name,
            ]);

            if (! $result['success']) {
                throw new Exception('MOQ sync failed: '.($result['error'] ?? $result['message']));
            }

            $session->update([
                'sync_amount' => $amount,
                'synced_at' => now(),
                'synced_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Day session synced to MOQ successfully.';
            $return['data'] = $session;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
