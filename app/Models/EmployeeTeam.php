<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

/**
 * One member of an employee's team. A team lead without 'property lead.view all'
 * sees their own leads plus those assigned to their team members.
 */
class EmployeeTeam extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'team_member_id',
    ];

    public static function rules(array $data = []): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'team_member_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:employee_id',
                Rule::unique(self::class)
                    ->where('tenant_id', self::getCurrentTenantId())
                    ->where('employee_id', $data['employee_id'] ?? null),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'team_member_id.required' => 'Please select an employee to add.',
            'team_member_id.different' => 'An employee cannot be added to their own team.',
            'team_member_id.unique' => 'This employee is already in the team.',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_member_id');
    }
}
