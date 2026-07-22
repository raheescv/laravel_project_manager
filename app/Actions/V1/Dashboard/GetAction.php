<?php

namespace App\Actions\V1\Dashboard;

use App\Http\Requests\V1\Dashboard\IndexRequest;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\SalePayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class GetAction
{
    /**
     * When set, every figure on the dashboard is hard-scoped to sales this user
     * created. Populated for non-admin employees so their overview mirrors the
     * self-scoped Sales list (see App\Actions\V1\Sale\ListAction); null for
     * admins and 'user'-type accounts, who see the whole (branch-scoped) view.
     */
    private ?int $restrictToUserId = null;

    /**
     * Build the admin overview dashboard:
     *  - today's sales snapshot
     *  - open day-session payment split (how much was collected per method)
     *  - business overview (weekly / monthly sales with growth %)
     */
    public function execute(IndexRequest $request): array
    {
        $branchId = $request->validatedWithDefaults()['branch_id'];

        $user = $request->user();
        $this->restrictToUserId = ($user && $user->type === 'employee' && ! $user->is_admin)
            ? $user->id
            : null;

        // Anchor the dashboard on the branch's current (open) day-session date
        // rather than the calendar date, so a session opened for another business
        // day — or one running past midnight — still reports against its own day.
        // Falls back to the calendar date when no session is open.
        $businessDate = $this->currentSessionDate($branchId);
        $today = $businessDate->toDateString();

        return [
            'date' => $today,
            'todaySummary' => $this->todaySummary($today, $branchId),
            'paymentSplit' => $this->paymentSplit($branchId),
            'bussinessOverview' => $this->bussinessOverview($businessDate, $branchId),
        ];
    }

    /**
     * The branch's currently-open day session — the request branch, falling back
     * to the user's default branch — or null when none is open. Shared by the
     * date anchor and the payment split so both resolve the session identically.
     */
    private function openSession(?int $branchId): ?SaleDaySession
    {
        $branchId ??= Auth::user()?->default_branch_id;

        return $branchId ? SaleDaySession::getOpenSessionForBranch($branchId) : null;
    }

    /**
     * The date the dashboard treats as "today": the opening date of the branch's
     * currently-open day session, or the calendar date when none is open.
     */
    private function currentSessionDate(?int $branchId): Carbon
    {
        return $this->openSession($branchId)?->opened_at?->copy()->startOfDay() ?? today();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todaySummary(string $today, ?int $branchId): array
    {
        $base = Sale::query()
            ->completed()
            ->whereDate('date', $today)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($this->restrictToUserId, fn ($q, $id) => $q->where('created_by', $id));

        return [
            ['title' => "Today's Sales", 'value' => round((float) (clone $base)->sum('paid'), 2), 'type' => 'currency'],
            ['title' => "Today's Bills", 'value' => (clone $base)->count(), 'type' => 'count'],
        ];
    }

    /**
     * How much was collected during the branch's currently-open day session,
     * grouped by payment method (Cash / Card / Bank / …), highest first — the
     * figure an owner reconciles against at day-close. Scoped to the session
     * (not the calendar date) so a session that spans midnight still adds up.
     * Empty when no day session is open for the branch.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentSplit(?int $branchId): array
    {
        $session = $this->openSession($branchId);

        if (! $session) {
            return [];
        }

        return SalePayment::query()
            ->whereHas('sale', function ($query) use ($session) {
                $query->where('status', 'completed')
                    ->where('sale_day_session_id', $session->id)
                    ->when($this->restrictToUserId, fn ($q, $id) => $q->where('created_by', $id));
            })
            ->selectRaw('payment_method_id, SUM(amount) as total')
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'title' => $row->paymentMethod?->name ?? 'Unknown',
                'value' => round((float) $row->total, 2),
                'type' => 'currency',
            ])
            ->all();
    }

    /**
     * Last 7 days vs prior 7 days, last 30 days vs prior 30 days.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bussinessOverview(Carbon $today, ?int $branchId): array
    {
        $weekFrom = $today->copy()->subDays(6)->toDateString();
        $weekTo = $today->toDateString();
        $prevWeekFrom = $today->copy()->subDays(13)->toDateString();
        $prevWeekTo = $today->copy()->subDays(7)->toDateString();

        $monthFrom = $today->copy()->subDays(29)->toDateString();
        $monthTo = $today->toDateString();
        $prevMonthFrom = $today->copy()->subDays(59)->toDateString();
        $prevMonthTo = $today->copy()->subDays(30)->toDateString();

        $weekly = $this->salesTotal($weekFrom, $weekTo, $branchId);
        $prevWeekly = $this->salesTotal($prevWeekFrom, $prevWeekTo, $branchId);
        $monthly = $this->salesTotal($monthFrom, $monthTo, $branchId);
        $prevMonthly = $this->salesTotal($prevMonthFrom, $prevMonthTo, $branchId);

        return [
            [
                'title' => 'weekly sales',
                'value' => round($weekly, 2),
                'percentage' => $this->growthPercentage($weekly, $prevWeekly),
            ],
            [
                'title' => 'Monthly sales',
                'value' => round($monthly, 2),
                'percentage' => $this->growthPercentage($monthly, $prevMonthly),
            ],
        ];
    }

    private function salesTotal(string $from, string $to, ?int $branchId): float
    {
        return (float) Sale::query()
            ->completed()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($this->restrictToUserId, fn ($q, $id) => $q->where('created_by', $id))
            ->sum('paid');
    }

    private function growthPercentage(float $current, float $previous): string
    {
        if ($previous == 0.0) {
            return $current > 0 ? '100%' : '0%';
        }

        return round((($current - $previous) / $previous) * 100).'%';
    }
}
