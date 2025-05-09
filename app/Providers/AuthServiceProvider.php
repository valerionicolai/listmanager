<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Source;
use App\Models\User;
use App\Policies\ContactListPolicy;
use App\Policies\ContactPolicy;
use App\Policies\SourcePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Contact::class => ContactPolicy::class,
        ContactList::class => ContactListPolicy::class,
        Source::class => SourcePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate per SuperAmministratore
        Gate::define('isSuperAdmin', function (User $user) {
            return $user->hasRole('SuperAmministratore');
        });

        // Gate per Amministratore
        Gate::define('isAdmin', function (User $user) {
            return $user->hasRole('Amministratore');
        });

        // Gate per Operatore
        Gate::define('isOperator', function (User $user) {
            return $user->hasRole('Operatore');
        });

        // Il SuperAmministratore bypassa tutte le altre policy
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('SuperAmministratore')) {
                return true;
            }
            return null; // Continua con la normale logica dei permessi
        });
    }
}