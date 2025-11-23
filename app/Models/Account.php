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
        'mobile',
        'whatsapp_mobile',
        'model',
        'email',

        'dob',
        'id_no',
        'nationality',
        'company',

        'description',
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
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('accounts.name', 'like', "%{$value}%")
                    ->orWhere('accounts.mobile', 'like', "%{$value}%")
                    ->orWhere('accounts.email', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['account_type'] ?? '', function ($query, $value) {
            return $query->where('account_type', $value);
        });
        $self = $self->when($request['customer_type_id'] ?? '', function ($query, $value) {
            return $query->where('customer_type_id', $value);
        });
        $self = $self->when($request['account_category_id'] ?? '', function ($query, $value) {
            return $query->where('account_category_id', $value);
        });
        $self = $self->when($request['is_payment_method'] ?? '', function ($query, $value) {
            return $query->whereIn('id', cache('payment_methods', []));
        });
        $self = $self->when($request['model'] ?? '', function ($query, $value) {
            return $query->where('model', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'mobile', 'email', 'id'])->toArray();
        $return['items'] = $self;

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
}
