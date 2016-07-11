<?php

namespace Reed\Auth\Passwords;

use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPasswordBroker();
    }

    /**
     * Register the password broker instance.
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->app->singleton('reed.auth.password', function ($app) {
            return new PasswordBrokerManager($app);
        });

        $this->app->bind('reed.auth.password.broker', function ($app) {
            return $app->make('reed.auth.password')->broker();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['reed.auth.password', 'reed.auth.password.broker'];
    }
}