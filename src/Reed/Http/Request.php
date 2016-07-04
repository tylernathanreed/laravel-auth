<?php

namespace Reed\Http;

use Illuminate\Support\Str;
use Illuminate\Http\Request as HttpRequest;

class Request extends HttpRequest
{
    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization', '');

        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }
}