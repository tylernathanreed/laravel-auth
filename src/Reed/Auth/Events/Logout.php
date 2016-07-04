<?php

namespace Reed\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Reed\Auth\Contracts\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \Reed\Auth\Contracts\Authenticatable  $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
