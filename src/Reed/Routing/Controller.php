<?php

namespace Reed\Routing;

use BadMethodCallException;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Controller extends BaseController
{
    /**
     * Register middleware on the controller.
     *
     * @param  array|string  $middleware
     * @param  array   $options
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function middleware($middleware, array $options = [])
    {
        foreach ((array) $middleware as $middlewareName) {
            $this->middleware[$middlewareName] = &$options;
        }

        return new ControllerMiddlewareOptions($options);
    }
}