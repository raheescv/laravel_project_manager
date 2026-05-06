<?php

namespace App\Services;

use App\Models\AccountingPeriodLock;
use Carbon\Carbon;

class AccountingPeriodLockService
{
    public function isLocked(string $date, ?int $branchId = null, ?int $tenantId = null): bool
    {
        $date = Carbon::parse($date)->toDateString();

        return AccountingPeriodLock::query()
            ->active()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($lockQuery) use ($branchId): void {
                    $lockQuery->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                });
            })
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();
    }

    public function ensureOpen(string $date, ?int $branchId = null, ?int $tenantId = null): void
    {
        if ($this->isLocked($date, $branchId, $tenantId)) {
            throw new \Exception('The accounting period for '.Carbon::parse($date)->format('d M Y').' is locked.');
        }
    }
}
