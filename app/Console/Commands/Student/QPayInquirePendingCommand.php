<?php

namespace App\Console\Commands\Student;

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\StartPaymentAction;
use App\Models\QpayTransaction;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * Broken transactions (QPay certification): a top-up whose result never reached
 * us — the parent closed the browser on QPay's page, the connection dropped — is
 * inquired once it is 20 minutes old, and again every 20 minutes until QPay gives
 * a final answer. A paid one is credited to the card; the parent is unblocked
 * either way.
 *
 * Payments the office released without an answer are chased too: the release only
 * freed the card, so QPay's answer still decides, and a released payment that
 * turns out to have been paid is credited when the answer arrives.
 *
 * The chase stops at [CHASE_FOR_DAYS]. Nothing about asking a fortnight later makes
 * an answer more likely, and without a floor every payment QPay never resolved
 * would be re-asked every 20 minutes for the life of the school.
 */
class QPayInquirePendingCommand extends Command
{
    protected $signature = 'qpay:inquire-pending';

    protected $description = 'Ask QPay for the result of student card top-ups that are still pending after 20 minutes';

    /** A payment with no answer is chased for this long, then left alone. */
    public const CHASE_FOR_DAYS = 7;

    public function handle(TenantService $tenantService): int
    {
        $cutoff = now()->subMinutes(StartPaymentAction::BROKEN_AFTER_MINUTES);
        $floor = now()->subDays(self::CHASE_FOR_DAYS);

        $tenantIds = QpayTransaction::withoutGlobalScopes()
            ->where('type', QpayTransaction::TYPE_PAYMENT)
            ->whereIn('status', QpayTransaction::STATUSES_AWAITING_RESULT)
            ->whereBetween('created_at', [$floor, $cutoff])
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                continue;
            }

            $tenantService->setCurrentTenant($tenant);
            try {
                $pending = QpayTransaction::where('type', QpayTransaction::TYPE_PAYMENT)
                    ->whereIn('status', QpayTransaction::STATUSES_AWAITING_RESULT)
                    ->whereBetween('created_at', [$floor, $cutoff])
                    ->where(fn ($q) => $q->whereNull('last_inquired_at')->orWhere('last_inquired_at', '<=', $cutoff))
                    ->oldest('id')
                    ->limit(200)
                    ->get();

                foreach ($pending as $transaction) {
                    $response = (new InquireAction())->execute($transaction);
                    $this->line("{$tenant->subdomain} {$transaction->pun}: {$response['message']}");
                }
            } finally {
                $tenantService->clearCurrentTenant();
            }
        }

        return self::SUCCESS;
    }
}
