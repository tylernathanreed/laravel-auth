<?php

namespace Reed\Auth\Foundation;

use Reed\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Reed\Auth\Contracts\Authenticatable as AuthenticatableContract;
use Reed\Auth\Contracts\Access\Authorizable as AuthorizableContract;
use Reed\Auth\Contracts\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;
}