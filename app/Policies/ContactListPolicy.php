<?php

namespace App\Policies;

use App\Models\ContactList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactListPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * SuperAdmin bypass is handled by Gate::before.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can view the model.
     * SuperAdmin bypass is handled by Gate::before.
     */
    public function view(User $user, ContactList $contactList): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can create models.
     * SuperAdmin bypass is handled by Gate::before.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can update the model.
     * SuperAdmin bypass is handled by Gate::before.
     */
    public function update(User $user, ContactList $contactList): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can delete the model.
     * SuperAdmin bypass is handled by Gate::before.
     */
    public function delete(User $user, ContactList $contactList): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContactList $contactList): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContactList $contactList): bool
    {
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }
}
