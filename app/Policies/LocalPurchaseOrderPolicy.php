<?php

namespace App\Policies;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use App\Models\User;

class LocalPurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('local purchase order.view') ||
            $user->can('local purchase order.view own');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.view') ||
            ($user->can('local purchase order.view own') && $order->created_by === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('local purchase order.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.create') &&
            $order->created_by === $user->id &&
            $order->status === LocalPurchaseOrderStatus::PENDING;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.delete-own') &&
            $order->status === LocalPurchaseOrderStatus::PENDING;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LocalPurchaseOrder $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LocalPurchaseOrder $order): bool
    {
        return false;
    }

    /**
     * Cancel LPO
     */
    public function decide(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.decide') &&
            $order->status === LocalPurchaseOrderStatus::PENDING;
    }
}
