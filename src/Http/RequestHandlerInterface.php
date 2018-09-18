<?php

namespace Beryl\Http;

use Beryl\Foundation\HttpKernelInterface;
use Illuminate\Http\Request;

interface RequestHandlerInterface
{
    public function handle(Request $request, HttpKernelInterface $kernel, array $middleware,
                           array $middleware_groups, array $route_middleware, array $middleware_priority);
}