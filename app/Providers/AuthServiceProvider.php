<?php

namespace App\Providers;

use Auth;
use Illuminate\Support\Facades\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Auth\EloquentAdminUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        // Binding eloquent.admin to our EloquentAdminUserProvider
        Auth::provider('eloquent.admin', function($app, array $config) {
            return new EloquentAdminUserProvider($app['hash'], $config['model']);
        });
    }
}
