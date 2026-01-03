<?php

namespace App\Models;

use App\Models\Scopes\CurrentBranchScope;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class JournalEntry extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'journal_id',
        'branch_id',
        'account_id',
        'counter_account_id',

        'date',
        'delivered_date',

        'debit',
        'credit',
        'remarks',

        'source',
        'person_name',
        'description',
        'journal_remarks',
        'reference_number',
        'journal_model',
        'journal_model_id',

        'model',
        'model_id',
        'created_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'journal_id' => ['required'],
            'account_id' => ['required'],
            'debit' => ['required'],
            'created_by' => ['required'],
        ], $merge);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function scopeExpense($query)
    {
        return $query->where('source', 'expense');
    }

    public function scopeIncome($query)
    {
        return $query->where('source', 'income');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
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
        return self::expense()
            ->where('debit', '>', 0)
            ->when($filter['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_remarks', 'like', "%{$value}%")
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
        return self::income()
            ->where('credit', '>', 0)
            ->when($filter['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_remarks', 'like', "%{$value}%")
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
