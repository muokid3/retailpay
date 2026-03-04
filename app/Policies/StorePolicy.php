<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    /**
     * pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            //admin can do anything with stores
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        if ($user->isBranchManager()) {
            return $user->branch_id === $store->branch_id;
        }

        if ($user->isStoreManager()) {
            return $user->store_id === $store->id;
        }

        return false;
    }

    /**
     * Custom check for inter-store transfers.
     * Can the user move stock FROM this store?
     */
    public function moveFrom(User $user, Store $store): bool
    {
        if ($user->isBranchManager()) {
            return $user->branch_id === $store->branch_id;
        }

        if ($user->isStoreManager()) {
            return $user->store_id === $store->id;
        }

        return false;
    }

    /**
     * Can the user move stock TO this store?
     */
    public function moveTo(User $user, Store $store): bool
    {
        if ($user->isBranchManager()) {
            return $user->branch_id === $store->branch_id;
        }

        if ($user->isStoreManager()) {
            return $user->store_id === $store->id;
        }

        return false;
    }
}
