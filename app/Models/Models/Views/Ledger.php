<?php

namespace App\Models\Models\Views;

use App\Models\Scopes\AssignedBranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public static function expenseList($filter)
    {
        return self::where('source', 'expense')
            ->where('debit', '>', 0)
            ->when($filter['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%");
                });
            })
            ->when($filter['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($filter['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($filter['branch_id'] ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($filter['account_id'] ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            });
    }

    public static function incomeList($filter)
    {
        return self::where('source', 'income')
            ->where('credit', '>', 0)
            ->when($filter['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%");
                });
            })
            ->when($filter['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($filter['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($filter['branch_id'] ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($filter['account_id'] ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            });
    }

    public static function monthly_summary($start_date, $end_date, $account_id)
    {
        $start_date = $start_date ?: now()->subMonth()->startOfMonth();
        $end_date = $end_date ?: now()->endOfMonth();
        $start = Carbon::parse($start_date)->startOfMonth();
        $end = Carbon::parse($end_date)->endOfMonth();

        // Create base array with all months initialized to zero
        $allMonths = collect();
        $current = $start->copy();

        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $allMonths[$monthKey] = [
                'month' => $monthKey,
                'month_name' => $current->format('M Y'),
                'credit' => 0,
                'debit' => 0,
            ];
            $current->addMonth();
        }

        // Get actual data from database
        $query = self::selectRaw('
                DATE_FORMAT(date, "%Y-%m") as month,
                SUM(debit) as debit,
                SUM(credit) as credit
            ')
            ->where('account_id', $account_id)
            ->whereBetween('date', [$start, $end])
            ->groupBy('month')
            ->orderBy('month', 'asc');

        // Merge actual data with base array
        $data = $query->get()->mapWithKeys(function ($item) {
            return [$item->month => [
                'month' => $item->month,
                'month_name' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                'debit' => (float) $item->debit,
                'credit' => (float) $item->credit,
            ]];
        });

        // Merge and ensure all months are present
        return $allMonths->merge($data)->sortKeys()->values();
    }
}
