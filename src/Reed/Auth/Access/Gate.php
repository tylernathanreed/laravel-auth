<?php

namespace Reed\Auth\Access;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Reed\Auth\Contracts\Access\Gate as GateContract;

class Gate implements GateContract
{
    use HandlesAuthorization;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [], array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [])
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->afterCallbacks = $afterCallbacks;
        $this->beforeCallbacks = $beforeCallbacks;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback) && Str::contains($callback, '@')) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            list($class, $method) = explode('@', $callback);

            return call_user_func_array([$this->resolvePolicy($class), $method], func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($ability, $arguments = [])
    {
        try {
            $result = $this->raw($ability, $arguments);
        } catch (AuthorizationException $e) {
            return false;
        }

        return (bool) $result;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        $result = $this->raw($ability, $arguments);

        if ($result instanceof Response) {
            return $result;
        }

        return $result ? $this->allow() : $this->deny();
    }

    /**
     * Get the raw result for the given ability for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return mixed
     */
    protected function raw($ability, $arguments = [])
    {
        if (! $user = $this->resolveUser()) {
            return false;
        }

        $arguments = is_array($arguments) ? $arguments : [$arguments];

        if (is_null($result = $this->callBeforeCallbacks($user, $ability, $arguments))) {
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );

        return $result;
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAuthCallback(
            $user, $ability, $arguments
        );

        return call_user_func_array(
            $callback, array_merge([$user], $arguments)
        );
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        $arguments = array_merge([$user, $ability], [$arguments]);

        foreach ($this->beforeCallbacks as $before) {
            if (! is_null($result = call_user_func_array($before, $arguments))) {
                return $result;
            }
        }
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  bool  $result
     * @return void
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        $arguments = array_merge([$user, $ability, $result], [$arguments]);

        foreach ($this->afterCallbacks as $after) {
            call_user_func_array($after, $arguments);
        }
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if ($this->usesPolicyCallback($ability, $arguments)) {
            return $this->resolvePolicyCallback($user, $ability, $arguments);
        } elseif (isset($this->abilities[$ability])) {
            return $this->abilities[$ability];
        } else {
            return function () {
                return false;
            };
        }
    }

    /**
     * Returns whether or not the given Ability and Arguments uses a Policy Callback.
     *
     * @param  string  $ability
     * @param  array   $arguments
     *
     * @return boolean
     */
    protected function usesPolicyCallback($ability, array $arguments)
    {
        // Make sure the Arguments Correspond to a Policy
        if(!$this->firstArgumentCorrespondsToPolicy($arguments))
            return false;

        // Make sure the Policy has the Ability
        if(!$this->policyHasAbility($ability, $arguments))
            return false;

        // Uses Policy Callback
        return true;
    }

    /**
     * Determine if the first argument in the array corresponds to a policy.
     *
     * @param  array  $arguments
     * @return bool
     */
    protected function firstArgumentCorrespondsToPolicy(array $arguments)
    {
        if (! isset($arguments[0])) {
            return false;
        }

        if (is_object($arguments[0])) {
            return isset($this->policies[get_class($arguments[0])]);
        }

        return is_string($arguments[0]) && isset($this->policies[$arguments[0]]);
    }

    /**
     * Returns whether or not the Policy derived from the given Arguments has the given Ability.
     *
     * @param  string  $ability
     * @param  array   $arguments
     *
     * @return boolean
     */
    protected function policyHasAbility($ability, array $arguments)
    {
        // Make sure the First Argument is set
        if(!isset($arguments[0]))
            return false;

        // Determine the Policy from the First Argument
        $policy = $this->getPolicyFor($arguments[0]);

        // Make sure a Policy was Found
        if(is_null($policy))
            return false;

        // Return whether or not the Ability is Callable
        return is_callable([$policy, $ability]);
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments)
    {
        return function () use ($user, $ability, $arguments) {
            $instance = $this->getPolicyFor($arguments[0]);

            // If we receive a non-null result from the before method, we will return it
            // as the final result. This will allow developers to override the checks
            // in the policy to return a result for all rules defined in the class.
            if (method_exists($instance, 'before')) {
                if (! is_null($result = $instance->before($user, $ability, ...$arguments))) {
                    return $result;
                }
            }

            if (strpos($ability, '-') !== false) {
                $ability = Str::camel($ability);
            }

            // If the first argument is a string, that means they are passing a class name
            // to the policy. We will remove the first argument from this argument list
            // because the policy already knows what type of models it can authorize.
            if (isset($arguments[0]) && is_string($arguments[0])) {
                array_shift($arguments);
            }

            if (! is_callable([$instance, $ability])) {
                return false;
            }

            return $instance->{$ability}($user, ...$arguments);
        };
    }

    /**
     * Returns whether or not a Policy Instance exists for the specified Class.
     *
     * @param  object|string  $class  The specified Class, or an Object.
     *
     * @return boolean
     */
    public function hasPolicyFor($class)
    {
        // Convert Objects to Class Names
        if(is_object($class))
            $class = get_class($class);

        // Return whether or not the Policy Instance exists
        return isset($this->policies[$class]);
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class)
    {
        // Make sure the Policy Instance Exists
        if(!$this->hasPolicyFor($class))
            throw new InvalidArgumentException("Policy not defined for [{$class}].");

        // Convert Objects to Class Names
        if(is_object($class))
            $class = get_class($class);

        return $this->resolvePolicy($this->policies[$class]);
    }

    /**
     * Build a policy class instance of the given type.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Get a guard instance for the given user.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }
}
