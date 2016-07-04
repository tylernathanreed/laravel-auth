<?php

namespace Reed\Auth;

use Illuminate\Auth\Authenticatable as BaseAuthenticatable;

trait Authenticatable
{
    use BaseAuthenticatable;

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }
}
