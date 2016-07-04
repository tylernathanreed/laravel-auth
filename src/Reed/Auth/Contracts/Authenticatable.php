<?php

namespace Reed\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

interface Authenticatable extends AuthenticatableContract
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName();
}
