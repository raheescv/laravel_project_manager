<?php

namespace App\Models\Models\Views;

use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
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
}
