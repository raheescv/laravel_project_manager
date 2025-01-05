<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Account extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'account_type',
        'name',
        'mobile',
        'model',
        'email',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'account_type' => ['required'],
            'name' => ['required', 'max:100'],
            'mobile' => ['max:15'],
            'model' => ['max:30'],
            'email' => ['max:50'],
            'unique_composite' => [
                Rule::unique(self::class)
                    ->where(function ($query) {
                        return $query
                            ->where('account_type', request()->input('account_type'))
                            ->where('name', request()->input('name'))
                            ->where('mobile', request()->input('mobile'));
                    })
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
        ], $merge);
    }
}
