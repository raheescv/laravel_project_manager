<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * A salesman sees only the leads assigned to them or to their team members
 * (employee_teams) — the old system's "Sales Team" rule. Admins, back-office
 * users and anyone granted 'property lead.view all' see every lead. Requests
 * without a signed-in user (console, unattended jobs) are not narrowed.
 */
class LeadVisibilityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->seesOnlyTeamLeads()) {
            return;
        }

        $builder->whereIn($model->qualifyColumn('assigned_to'), $user->leadVisibleUserIds());
    }
}
