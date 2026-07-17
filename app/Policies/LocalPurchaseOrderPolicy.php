<?php

namespace App\Policies;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LocalPurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('local purchase order.view') || $user->can('local purchase order.view own');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.view') || $user->can('local purchase order.view own');
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
    public function update(User $user, LocalPurchaseOrder $order): Response
    {
         if (! $user->can('local purchase order.edit')) {
            return Response::deny('You do not have permission to edit local purchase orders.');
        }

        if ($order->status !== LocalPurchaseOrderStatus::CONFIRMED) {
            return Response::deny("This local purchase order is {$order->status->label()} and cannot be edited.");
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.delete') && $order->status === LocalPurchaseOrderStatus::PENDING;
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

    public function editTerms(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.edit');
    }

    /**
     * Cancel LPO
     */
    public function decide(User $user, LocalPurchaseOrder $order): Response
    {
        if (! $user->can('local purchase order.decide')) {
            return Response::deny('You do not have permission to approve or reject local purchase orders.');
        }

        if ($order->status == LocalPurchaseOrderStatus::APPROVED) {
            return Response::allow();
        }

        if ($order->status !== LocalPurchaseOrderStatus::CONFIRMED) {
            return Response::deny("This local purchase order is {$order->status->label()} and is not awaiting a decision. Only confirmed orders can be approved or rejected.");
        }

        return Response::allow();
    }

    /**
     * Confirm a pending LPO (confirmation comes before decision)
     */
    public function confirm(User $user, LocalPurchaseOrder $order): Response
    {
        if (! $user->can('local purchase order.confirm')) {
            return Response::deny('You do not have permission to confirm local purchase orders.');
        }

        if (in_array($order->status, [LocalPurchaseOrderStatus::CONFIRMED, LocalPurchaseOrderStatus::APPROVED])) {
            return Response::allow();
        }
        
        if ($order->status !== LocalPurchaseOrderStatus::PENDING) {
            return Response::deny("This local purchase order is already {$order->status->label()} and can no longer be confirmed. Only pending orders can be confirmed.");
        }

        return Response::allow();
    }

    /**
     * Print / export LPO as PDF
     */
    public function print(User $user, LocalPurchaseOrder $order): bool
    {
        return $user->can('local purchase order.print')
            || $user->can('local purchase order.view')
            || $user->can('local purchase order.view own');
    }
}
