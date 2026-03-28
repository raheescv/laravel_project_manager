<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class LpoPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lpo-purchase.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('lpo-purchase.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lpo-purchase.create');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->can('lpo-purchase.create') && $purchase->status === 'pending';
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->can('lpo-purchase.delete') && $purchase->status === 'pending';
    }

    public function restore(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function forceDelete(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function decide(User $user, Purchase $purchase): bool
    {
        return $user->can('lpo-purchase.decide') && $purchase->status === 'pending';
    }
}
