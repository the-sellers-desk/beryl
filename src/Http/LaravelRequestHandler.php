<?php

namespace Beryl\Http;

use Beryl\Foundation\HttpKernelInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class LaravelRequestHandler implements RequestHandlerInterface
{
    protected $app;
    protected $router;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->router = $app->make('router');
    }

    public function handle(Request $request, HttpKernelInterface $kernel, array $middleware,
                           array $middleware_groups, array $route_middleware, array $middleware_priority)
    {
        // Register necessary providers that are to be in this
        $this->app['events']->fire('register_per_request_providers', [$this, $this->app]);

        foreach ($this->app['config']['app.per_request_providers'] as $provider) {
            $this->app->register($provider);
        }

        // Set up router and middleware
        $this->router = $this->app->make('router');

        $this->router->middlewarePriority = $middleware_priority;

        foreach ($middleware_groups as $key => $middleware) {
            $this->router->middlewareGroup($key, $middleware);
        }

        foreach ($route_middleware as $key => $middleware) {
            $this->router->aliasMiddleware($key, $middleware);
        }

        $kernel->setRouter($this->router);
    }
}