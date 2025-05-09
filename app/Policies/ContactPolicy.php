<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all contacts.
        // Operators will have their query scoped in the resource.
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can view the model.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function view(User $user, Contact $contact): bool
    {
        if ($user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            return true;
        }
        // Operators can only view their own contacts.
        return $user->id === $contact->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user (SuperAdmin, Admin, Operator) can create contacts.
        return $user->isNotNull(); // Or simply true if all authenticated users are intended
    }

    /**
     * Determine whether the user can update the model.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function update(User $user, Contact $contact): bool
    {
        if ($user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            return true;
        }
        // Operators can only update their own contacts.
        return $user->id === $contact->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function delete(User $user, Contact $contact): bool
    {
        // User can delete if they own it OR if they are SuperAdmin/Admin.
        return $user->id === $contact->user_id || $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can restore the model.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $user->id === $contact->user_id || $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * The Gate::before in AuthServiceProvider handles SuperAdmin.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->id === $contact->user_id || $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }
}
