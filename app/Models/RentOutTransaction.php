<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutTransaction extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $table = 'rent_out_transactions';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'date',
        'due_date',
        'paid_date',
        'cheque_date',
        'cheque_no',
        'bank_name',
        'credit',
        'debit',
        'account_id',
        'source',
        'source_id',
        'model',
        'model_id',
        'journal_id',
        'journal_entry_id',
        'group',
        'category',
        'payment_type',
        'remark',
        'reason',
        'voucher_no',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'cheque_date' => 'date',
        'credit' => 'decimal:2',
        'debit' => 'decimal:2',
    ];

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the source model (RentOutPaymentTerm, RentOutUtilityTerm, RentOutService, etc.)
     */
    public function sourceModel()
    {
        if (! $this->model || ! $this->model_id) {
            return;
        }

        $modelClass = 'App\\Models\\'.$this->model;

        if (! class_exists($modelClass)) {
            return;
        }

        return $modelClass::find($this->model_id);
    }

    public function scopeReceipts($query)
    {
        return $query->where('credit', '>', 0);
    }

    public function scopePayments($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByModel($query, string $model, ?int $modelId = null)
    {
        $query->where('model', $model);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeByJournal($query, int $journalId)
    {
        return $query->where('journal_id', $journalId);
    }
}
