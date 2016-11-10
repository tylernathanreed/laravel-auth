<?php

namespace Reed\Auth\Contracts;

use Closure;

interface PasswordBroker53 extends PasswordBroker
{
    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     *
     * @return string
     */
    public function sendResetLink(array $credentials);
}