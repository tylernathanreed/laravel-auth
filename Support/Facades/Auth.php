<?php

namespace Reed\Auth\Support\Facades;

/**
 * @see \Reed\Auth\AuthManager
 * @see \Reed\Auth\Contracts\Factory
 * @see \Reed\Auth\Contracts\Guard
 * @see \Reed\Auth\Contracts\StatefulGuard
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'reed.auth';
    }
}
