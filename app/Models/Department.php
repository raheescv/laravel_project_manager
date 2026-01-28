<?php

namespace App\Models;

use App\Actions\Settings\Department\CreateAction;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Department extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', Rule::unique(self::class, 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($id)],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public static function selfCreate($name)
    {
        $existing = Department::firstWhere('name', $name);
        if (! $existing) {
            $response = (new CreateAction())->execute(['name' => $name]);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return $response['data']['id'];
        } else {
            return $existing['id'];
        }
    }
}
