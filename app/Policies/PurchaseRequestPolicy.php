<?php

namespace App\Policies;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('purchase request.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase request.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('purchase request.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase request.edit') && $purchaseRequest->status === PurchaseRequestStatus::PENDING;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase request.delete') && $purchaseRequest->status === PurchaseRequestStatus::PENDING;
    }

    /**
     * Determine whether the user can make purchase request decision.
     */
    public function decide(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase request.decide') && $purchaseRequest->status === PurchaseRequestStatus::PENDING;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return false;
    }
}
