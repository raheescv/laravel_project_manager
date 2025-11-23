<?php

namespace App\Models;

use App\Events\BranchUpdated;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuditableContracts
{
    use Auditable, HasFactory, HasRoles, Notifiable;
    use HasApiTokens;

    protected $fillable = [
        'type',
        'name',
        'code',
        'email',
        'mobile',
        'is_admin',
        'default_branch_id',
        'email_verified_at',
        'password',
        'pin',
        'dob',
        'doj',
        'place',
        'nationality',
        'allowance',
        'salary',
        'hra',
        'max_discount_per_sale',
        'is_locked',
        'is_active',
        'is_whatsapp_enabled',
        'second_reference_no',
        'telegram_chat_id',
        'is_telegram_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $auditExclude = [
        'remember_token',
    ];

    public static function createRules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->ignore($id)],
            'password' => ['required'],
        ], $merge);
    }

    public static function updateRules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->ignore($id)],
        ], $merge);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user): void {
            if ($user->isDirty('default_branch_id')) {
                // event(new BranchUpdated($user, $user->default_branch_id));
            }
        });
    }

    public function branches()
    {
        return $this->hasMany(UserHasBranch::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class, 'employee_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function scopeEmployee($query)
    {
        return $query->where('type', 'employee');
    }

    public function scopeUser($query)
    {
        return $query->where('type', 'user');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('mobile', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            });
        });
        $self = $self->whereHas('branches', function ($query) {
            return $query->where('user_has_branches.branch_id', session('branch_id'));
        });
        $self = $self->when($request['type'] ?? '', function ($query, $value) {
            return $query->where('type', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'email', 'mobile', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function validateMaxDiscount($grossAmount, $totalDiscount)
    {
        if (! $this->max_discount_per_sale) {
            return; // No limit set, allow any discount
        }

        if ($grossAmount <= 0) {
            return; // No gross amount, nothing to validate
        }

        $discountPercentage = round(($totalDiscount / $grossAmount) * 100, 2);
        if($this->max_discount_per_sale==0){
            throw new Exception("You don't have the permission to give discount");
        }
        if ($discountPercentage > $this->max_discount_per_sale) {
            throw new Exception("Total discount percentage ({$discountPercentage}%) exceeds your maximum allowed discount per sale ({$this->max_discount_per_sale}%).");
        }
    }
}
