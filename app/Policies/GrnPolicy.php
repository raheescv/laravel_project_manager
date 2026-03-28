<?php

namespace App\Policies;

use App\Enums\Grn\GrnStatus;
use App\Models\Grn;
use App\Models\User;

class GrnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('grn.view');
    }

    public function view(User $user, Grn $grn): bool
    {
        return $user->can('grn.view');
    }

    public function create(User $user): bool
    {
        return $user->can('grn.create');
    }

    public function update(User $user, Grn $grn): bool
    {
        return $user->can('grn.create') && $grn->status === GrnStatus::PENDING;
    }

    public function delete(User $user, Grn $grn): bool
    {
        return $user->can('grn.delete') && $grn->status === GrnStatus::PENDING;
    }

    public function restore(User $user, Grn $grn): bool
    {
        return false;
    }

    public function forceDelete(User $user, Grn $grn): bool
    {
        return false;
    }

    public function decide(User $user, Grn $grn): bool
    {
        return $user->can('grn.decide') && $grn->status === GrnStatus::PENDING;
    }
}
