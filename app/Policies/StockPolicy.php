<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StockPolicy
{
    /**
     * pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            //admin can do anything with stock
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All roles can at least see some stock.
        // The actual filtering will happen in the controller/query.
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Stock $stock): bool
    {
        if ($user->isBranchManager()) {
            return $user->branch_id === $stock->store->branch_id;
        }

        if ($user->isStoreManager()) {
            return $user->store_id === $stock->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // For general "can I initiate a movement"
        return $user->isAdministrator() || $user->isBranchManager() || $user->isStoreManager();
    }

    /**
     * Determine whether the user can update (adjust/move) the stock.
     */
    public function update(User $user, Stock $stock): bool
    {
        if ($user->isBranchManager()) {
            return $user->branch_id === $stock->store->branch_id;
        }

        if ($user->isStoreManager()) {
            return $user->store_id === $stock->store_id;
        }

        return false;
    }

    /**
     * Custom check for inter-store transfers.
     * Can the user move stock FROM this store?
     */
    public function moveFrom(User $user, \App\Models\Store $store): bool
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
    public function moveTo(User $user, \App\Models\Store $store): bool
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
