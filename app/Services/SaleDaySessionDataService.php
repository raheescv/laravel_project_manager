<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\SalePayment;
use App\Models\TailoringOrder;
use App\Models\TailoringPayment;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class SaleDaySessionDataService
{
    public function salesQueryForSession(int $sessionId, bool $withoutGlobalScopes = false): EloquentBuilder
    {
        $query = $withoutGlobalScopes ? Sale::withoutGlobalScopes() : Sale::query();

        return $query
            ->completed()
            ->where('sale_day_session_id', $sessionId);
    }

    public function tailoringOrdersQueryForSession(int $sessionId, bool $withoutGlobalScopes = false): EloquentBuilder
    {
        $query = $withoutGlobalScopes ? TailoringOrder::withoutGlobalScopes() : TailoringOrder::query();

        return $query->where('sale_day_session_id', $sessionId);
    }

    public function salePaymentsQueryForSessionOpenDateBranch(SaleDaySession $session): EloquentBuilder
    {
        return SalePayment::query()
            ->whereDate('date', date('Y-m-d', strtotime($session->opened_at)))
            ->whereHas('sale', function ($query) use ($session): void {
                $query->where('branch_id', $session->branch_id)->where('status', 'completed');
            });
    }

    public function tailoringPaymentsQueryForSessionOpenDateBranch(SaleDaySession $session): EloquentBuilder
    {
        return TailoringPayment::query()
            ->whereDate('date', date('Y-m-d', strtotime($session->opened_at)))
            ->whereHas('order', function ($query) use ($session): void {
                $query->where('branch_id', $session->branch_id);
            });
    }
}
