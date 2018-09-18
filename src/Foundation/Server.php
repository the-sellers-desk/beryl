<?php

namespace Beryl\Foundation;

class Server extends \Swoole\Http\Server
{
    protected $app;
    protected $kernel;
    protected $bind_to;
    protected $bind_port;
    protected $request_handler;

    public function __construct(Application $app, HttpKernel $kernel)
    {
        $this->app = $app;
        $this->kernel = $kernel;

        $beryl_config = $app->get('beryl_config');
        $this->bind_to = $beryl_config['bind_to'] ?? '0.0.0.0';
        $this->bind_port = $beryl_config['port'] ?? 80;

        parent::__construct($this->bind_to, $this->bind_port);
    }

    public function handleRequest($req, $res) {
        $this->kernel->handleRequest($req, $res);
    }

    public function handleStart($http) {
        echo "Swoole HTTP server start. Binding to: {$this->bind_to} at port {$this->bind_port}\n";
    }

    public function start() {
        $this->on('request', [$this, 'handleRequest']);
        $this->on('start', [$this, 'handleStart']);

        parent::start();
    }
}