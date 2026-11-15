<?php

namespace App\Console\Commands\Student;

use App\Actions\Student\PreOrder\ChargeDueAction;
use App\Actions\Student\PreOrder\SendSummaryAction;
use App\Models\Configuration;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Support\ModuleAccess;
use App\Support\Student\StudentSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The moment a school day's pre-order cut-off passes (e.g. 18:00 the evening
 * before), close that day:
 *
 * 1. pay what is still unpaid from the student cards (ChargeDueAction) — the
 *    weekly orders, and day orders whose bank card payment never came; a card
 *    that cannot cover its order leaves it unpaid;
 * 2. email the day's list to the addresses in Settings → Student Cards → Canteen,
 *    when there are any, so the kitchen knows what to prepare.
 *
 * Scheduled every minute and safe to run that often: each tenant remembers the
 * last day it closed (`SENT_KEY`), so a day closes once, and a day already paid is
 * never paid twice (ChargeAction). A cut-off missed by more than [LATE_BY_MINUTES]
 * (the scheduler was down, or pre-orders were only just switched on) is skipped
 * rather than handled stale; `--date=` closes a given day by hand.
 */
class PreOrderCloseCommand extends Command
{
    public const SENT_KEY = 'pre_order_closed_for';

    /** How late a summary may still go out after its cut-off. */
    public const LATE_BY_MINUTES = 60;

    protected $signature = 'student:pre-order-close {--date= : Close this day (YYYY-MM-DD) now, even if it was closed already}';

    protected $description = 'Pay and email each school day\'s canteen pre-orders once its cut-off has passed';

    public function handle(TenantService $tenantService): int
    {
        $tenantIds = Configuration::withoutTenant()
            ->where('key', StudentSettings::KEY)
            ->pluck('tenant_id')
            ->filter()
            ->unique();

        $now = now();

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant || ! $tenant->is_active) {
                continue;
            }

            $tenantService->setCurrentTenant($tenant);
            try {
                $settings = StudentSettings::current();
                if (! ModuleAccess::school() || ! $settings->preOrdersOpen()) {
                    continue;
                }

                foreach ($this->datesDue($settings, $now) as $date) {
                    $charged = (new ChargeDueAction())->forDate($date);
                    $this->line("{$tenant->subdomain} {$date->toDateString()}: {$charged['paid']} paid, ".count($charged['failed']).' not paid');

                    if ($settings->preOrderSummaryEmails !== []) {
                        $response = (new SendSummaryAction())->execute($date);
                        $this->line("{$tenant->subdomain} {$date->toDateString()}: {$response['message']}");
                    }
                    if (! $this->option('date')) {
                        Configuration::updateOrCreate(['key' => self::SENT_KEY], ['value' => $date->toDateString()]);
                    }
                }
            } catch (\Throwable $th) {
                report($th);
                $this->error("{$tenant->subdomain}: {$th->getMessage()}");
            } finally {
                $tenantService->clearCurrentTenant();
            }
        }

        return self::SUCCESS;
    }

    /**
     * School days whose cut-off passed within the last [LATE_BY_MINUTES] and that were not
     * closed yet. Only today and tomorrow can qualify: a cut-off lies on the day or
     * the day before.
     *
     * @return list<Carbon>
     */
    private function datesDue(StudentSettings $settings, Carbon $now): array
    {
        if ($this->option('date')) {
            return [Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()];
        }

        $lastSent = Configuration::where('key', self::SENT_KEY)->value('value');
        $due = [];
        foreach ([$now->copy()->startOfDay(), $now->copy()->startOfDay()->addDay()] as $date) {
            $deadline = $settings->preOrderDeadline($date);
            if (! $settings->isSchoolDay($date) || $deadline->gt($now) || $deadline->lt($now->copy()->subMinutes(self::LATE_BY_MINUTES))) {
                continue;
            }
            if ($lastSent !== null && $date->toDateString() <= $lastSent) {
                continue;
            }
            $due[] = $date;
        }

        return $due;
    }
}
