<?php

namespace Reed\Auth\Passwords;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Reed\Auth\Contracts\PasswordBrokerFactory as FactoryContract;

class PasswordBrokerManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new PasswordBroker manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the broker from the local cache.
     *
     * @param  string  $name
     *
     * @return \Reed\Auth\Contracts\PasswordBroker
     */
    public function broker($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return isset($this->brokers[$name])
                    ? $this->brokers[$name]
                    : $this->brokers[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given broker.
     *
     * @param  string  $name
     *
     * @return \Reed\Auth\Contracts\PasswordBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        // Determine the Driver Configuration
        $config = $this->getConfig($name);

        // Make sure the Driver is Configured
        if(is_null($config))
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");

        // Determine the Driver
        $driver = Arr::get($config, 'driver', 'laravel');

        // Check for a Custom Creator
        if(isset($this->customCreators[$driver]))
            return $this->callCustomCreator($name, $config);

        // Determine the Driver Method
        $driverMethod = 'create' . ucfirst($driver) . 'Driver';

        // Call the Driver Creator
        if(method_exists($this, $driverMethod))
            return $this->{$driverMethod}($name, $config);

        // Driver Not Defined
        throw new InvalidArgumentException("Password resetter driver [{$driver}] is not defined.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string  $name
     * @param  array   $config
     *
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Creates the Laravel 5.2 Password Broker.
     *
     * @param  string  $name
     * @param  array  $config
     *
     * @return \Reed\Auth\Passwords\PasswordBroker
     */
    protected function createLaravelDriver($name, array $config)
    {
        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new PasswordBroker(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider']),
            $this->app['mailer'],
            $config['email']
        );
    }

    /**
     * Creates the Laravel 5.3 Password Broker.
     *
     * @param  string  $name
     * @param  array  $config
     *
     * @return \Reed\Auth\Passwords\PasswordBroker
     */
    protected function createLaravel53Driver($name, array $config)
    {
        return new PasswordBroker53(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider'])
        );
    }

    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param  array  $config
     *
     * @return \Reed\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = isset($config['connection']) ? $config['connection'] : null;

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $config['table'],
            $key,
            $config['expire']
        );
    }

    /**
     * Get the password broker configuration.
     *
     * @param  string  $name
     *
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.passwords.{$name}"];
    }

    /**
     * Get the default password broker name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.passwords'];
    }

    /**
     * Set the default password broker name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.passwords'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->broker()->{$method}(...$parameters);
    }
}
