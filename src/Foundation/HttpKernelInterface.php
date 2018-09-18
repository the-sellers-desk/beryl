<?php

namespace Beryl\Foundation;

use Illuminate\Contracts\Http\Kernel;

interface HttpKernelInterface extends Kernel
{
    public function setRouter($router);
}