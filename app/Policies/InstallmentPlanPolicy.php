<?php

namespace App\Policies;

use App\Models\InstallmentPlan;
use App\Models\User;

class InstallmentPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InstallmentPlan $installmentPlan): bool
    {
        return $user->id === $installmentPlan->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InstallmentPlan $installmentPlan): bool
    {
        return $user->id === $installmentPlan->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InstallmentPlan $installmentPlan): bool
    {
        return $user->id === $installmentPlan->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InstallmentPlan $installmentPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InstallmentPlan $installmentPlan): bool
    {
        return false;
    }
}
