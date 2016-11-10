<?php

namespace Reed\Auth\Contracts;

use Closure;

interface PasswordBroker51 extends PasswordBroker
{
    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @param  \Closure|null  $callback
     *
     * @return string
     */
    public function sendResetLink(array $credentials, Closure $callback = null);
}