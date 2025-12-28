<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
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
        'customer_type_id',
        'account_category_id',
        'name',
        'alias_name',
        'mobile',
        'whatsapp_mobile',
        'model',
        'email',
        'place',

        'dob',
        'id_no',
        'nationality',
        'company',
        'credit_period_days',

        'description',
        'opening_debit',
        'opening_credit',
        'second_reference_no',
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

   public function getDropDownList($request)
{
    $self = self::orderBy('name');

    // 🔍 Search
    $self = $self->when($request['query'] ?? '', function ($query, $value) {
        $value = trim($value);
        $query->where(function ($q) use ($value) {
            $q->where('accounts.name', 'like', "%{$value}%")
              ->orWhere('accounts.mobile', 'like', "%{$value}%")
              ->orWhere('accounts.email', 'like', "%{$value}%");
        });
    });

    // 🔹 Filters
    $self = $self->when($request['account_type'] ?? '', fn ($q, $v) => $q->where('account_type', $v));
    $self = $self->when($request['customer_type_id'] ?? '', fn ($q, $v) => $q->where('customer_type_id', $v));
    $self = $self->when($request['account_category_id'] ?? '', fn ($q, $v) => $q->where('account_category_id', $v));
    $self = $self->when($request['is_payment_method'] ?? '', fn ($q) => $q->whereIn('id', cache('payment_methods', [])));
    $self = $self->when($request['model'] ?? '', fn ($q, $v) => $q->where('model', $v));

    // ✅ LIMIT ONLY WHEN SEARCHING
    if (!empty($request['query'])) {
        $self->limit(10);
    }

    $return['items'] = $self->get(['id', 'name', 'mobile', 'email']);

    return $return;
}

    public function scopeVendor($query)
    {
        return $query->where('model', 'Vendor');
    }

    public function scopeCustomer($query)
    {
        return $query->where('model', 'Customer');
    }

    public function notes()
    {
        return $this->hasMany(AccountNote::class);
    }

    public function ledger()
    {
        return $this->hasMany(Ledger::class, 'account_id');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'account_id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getNameAttribute($value)
    {
        return ucwords($value);
    }
}
