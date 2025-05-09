<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $modelToUpdate): bool
    {
        if ($user->hasRole('SuperAmministratore')) {
            return true;
        }

        if ($user->hasRole('Amministratore')) {
            // Admins cannot update SuperAdmins
            if ($modelToUpdate->hasRole('SuperAmministratore')) {
                return false;
            }
            // Admins can update other Admins or Operators
            return $modelToUpdate->hasAnyRole(['Amministratore', 'Operatore']);
        }

        return false; // Operators and others cannot update users here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $modelToDelete): bool
    {
        // Prevent users from deleting themselves through this interface
        if ($user->id === $modelToDelete->id) {
            return false;
        }

        if ($user->hasRole('SuperAmministratore')) {
            // SuperAdmins cannot be deleted by anyone but perhaps another SuperAdmin (or via direct DB access)
            // For safety, let's prevent deletion of SuperAdmins via UI for now unless it's explicitly allowed.
            return !$modelToDelete->hasRole('SuperAmministratore');
        }

        if ($user->hasRole('Amministratore')) {
            // Admins cannot delete SuperAdmins
            if ($modelToDelete->hasRole('SuperAmministratore')) {
                return false;
            }
            // Admins can delete other Admins or Operators
            return $modelToDelete->hasAnyRole(['Amministratore', 'Operatore']);
        }
        return false; // Operators and others cannot delete users
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $modelToRestore): bool
    {
        if ($user->hasRole('SuperAmministratore')) {
            return true;
        }
        if ($user->hasRole('Amministratore')) {
            if ($modelToRestore->hasRole('SuperAmministratore')) {
                return false;
            }
            return $modelToRestore->hasAnyRole(['Amministratore', 'Operatore']);
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $modelToDelete): bool
    {
        // Prevent users from deleting themselves through this interface
        if ($user->id === $modelToDelete->id) {
            return false;
        }

        if ($user->hasRole('SuperAmministratore')) {
            return !$modelToDelete->hasRole('SuperAmministratore');
        }

        if ($user->hasRole('Amministratore')) {
            if ($modelToDelete->hasRole('SuperAmministratore')) {
                return false;
            }
            return $modelToDelete->hasAnyRole(['Amministratore', 'Operatore']);
        }
        return false;
    }
}
