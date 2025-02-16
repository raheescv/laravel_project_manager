<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class JournalEntry extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'remarks',
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
            'debit' => ['required'],
            'created_by' => ['required'],
        ], $merge);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
