<?php

namespace App\Models;

use App\Actions\Settings\AccountCategory\CreateAction;
use App\Models\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class AccountCategory extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function parent()
    {
        return $this->belongsTo(AccountCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountCategory::class, 'parent_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_category_id');
    }

    public static function parentCreate($parent)
    {
        $model = self::firstOrCreate(['name' => $parent]);

        return $model['id'];
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', '%'.trim($value).'%');
        });
        $self = $self->when($request['is_parent'] ?? false, function ($query, $value) {
            return $query->whereNull('parent_id');
        });
        $self = $self->when($request['parent_id'] ?? '', function ($query, $value) {
            return $query->where('parent_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public static function selfCreate($name)
    {
        $data['name'] = $name;
        $existing = AccountCategory::firstWhere('name', $name);
        if (! $existing) {
            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return $response['data']['id'];
        } else {
            return $existing['id'];
        }
    }
}
