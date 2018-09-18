<?php

namespace Beryl\Foundation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;

class Application extends \Illuminate\Foundation\Application
{
    public function bootstrapPath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Set the base path for the application.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Register all of the per request configured providers.
     *
     * @return void
     */
    public function registerPerRequestConfiguredProviders()
    {
        $providers = Collection::make($this->config['app.per_request_providers']);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this->bootstrapPath() . ($this->getBerylConfig()['cached_config_path'] ?? '/cache/config.php');
    }

    /**
     * Get Beryl config.
     *
     * @return array
     */
    public function getBerylConfig()
    {
        return $this->get('beryl_config');
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);

        $this->instance(PackageManifest::class, new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return (!defined('EXEC_MODE') || (EXEC_MODE ?? 'cli') != 'web');
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));

        if ($this->runningInConsole()) {
            $this->register(new RoutingServiceProvider($this));
        }
    }
}