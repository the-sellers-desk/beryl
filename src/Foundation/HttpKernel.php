<?php

namespace Beryl\Foundation;

use Beryl\Http\RequestHandlerInterface;
use Beryl\Http\Server;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Indragunawan\SwooleHttpMessageBridge\Symfony\Request;
use Illuminate\Http\Request as LaravelRequest;
use Indragunawan\SwooleHttpMessageBridge\Symfony\Response;

class HttpKernel extends \Illuminate\Foundation\Http\Kernel implements HttpKernelInterface
{
    protected $app;
    protected $router;
    protected $request_handler;

    public function __construct(Application $app, RequestHandlerInterface $request_handler)
    {
        $this->app = $app;
        $this->request_handler = $request_handler;

        // Save middleware to kernel
        $middleware_config = $app->config['middleware'] ?? [];
        $this->middleware = $middleware_config['middleware'] ?? [];
        $this->middlewareGroups = $middleware_config['middleware_groups'] ?? [];
        $this->routeMiddleware = $middleware_config['route_middleware'] ?? [];
    }

    public function handleRequest(\Swoole\Http\Request $swoole_request, \Swoole\Http\Response $swoole_response) {

        /** @var LaravelRequest $request */
        $request = LaravelRequest::createFromBase(Request::createFromSwooleRequest($swoole_request));
        $request::enableHttpMethodParameterOverride();

        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');

        $response = $this->request_handler
            ->handle($request, $this, $this->middleware, $this->middlewareGroups, $this->routeMiddleware, $this->middlewarePriority);

        // Short circuit this if a response is returned from the handler
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return Response::writeSwooleResponse($swoole_response, $response);
        }

        return Response::writeSwooleResponse($swoole_response, $this->handleRoutingRequest($request));
    }

    protected function handleRoutingRequest(\Symfony\Component\HttpFoundation\Request $request) {
        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }

    public function setRouter($router)
    {
        $this->router = $router;
    }
}