<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Journal extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'date',
        'description',
        'remarks',
        'reference_number',
        'model',
        'model_id',
        'created_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'date' => ['required'],
            'description' => ['required'],
            'created_by' => ['required'],
        ], $merge);
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }
}
