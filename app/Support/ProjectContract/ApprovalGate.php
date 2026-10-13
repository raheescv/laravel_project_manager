<?php

namespace App\Support\ProjectContract;

use App\Models\ProjectContractApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Who may act on the current level of an approval round: anyone holding the
 * `project contract.approve` permission, including the person who submitted
 * it. A level's role is a label for the approval route; access comes from the
 * permission alone.
 */
final class ApprovalGate
{
    /** The latest round for a submission, in level order. */
    public static function round(Model $approvable): Collection
    {
        $query = ProjectContractApproval::query()
            ->where('approvable_type', $approvable::class)
            ->where('approvable_id', $approvable->getKey());

        $latest = (clone $query)->max('round');

        return $latest ? $query->where('round', $latest)->with('actor:id,name')->orderBy('level')->get() : collect();
    }

    /** The lowest pending level, or null once every level has acted. */
    public static function current(Model $approvable): ?ProjectContractApproval
    {
        return self::round($approvable)->firstWhere('status', ProjectContractApproval::PENDING);
    }

    /** Why this user may not act now, or null when they may. */
    public static function refusal(User $user, ProjectContractApproval $approval): ?string
    {
        if (! $user->can('project contract.approve')) {
            return 'You need the “approve” permission for project contracts.';
        }

        return null;
    }

    public static function canAct(User $user, Model $approvable): bool
    {
        $current = self::current($approvable);

        return $current !== null && self::refusal($user, $current) === null;
    }
}
