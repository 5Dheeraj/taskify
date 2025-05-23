<?php

namespace App\Providers;

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
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        // Todo::class => TodoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // ✅ Added LMS Permissions for training slides
        Gate::define('edit training', function ($user) {
            return $user->hasPermissionTo('edit training');
        });

        Gate::define('delete training', function ($user) {
            return $user->hasPermissionTo('delete training');
        });
    }
}
