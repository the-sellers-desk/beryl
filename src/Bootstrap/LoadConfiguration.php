<?php

namespace Beryl\Bootstrap;

use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Exception;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param Application $app
     * @return void
     * @throws \Exception
     */
    public function bootstrap(Application $app)
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = $this->getItemsFromConfig($cached);
            $loadedFromCache = true;
        }

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
        $app->instance('config', $config = new Repository($items));

        if (! isset($loadedFromCache)) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');
        });

        date_default_timezone_set($config->get('beryl.timezone') ?? $config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->name('/\.(php|yaml|yml)$/')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);
            $file_ext = pathinfo($file->getRelativePathname(), PATHINFO_EXTENSION);
            $files[$directory . basename($file->getRealPath(), '.' . $file_ext)] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @return void
     * @throws \Exception
     */
    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
        $files = $this->getConfigurationFiles($app);

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach ($files as $key => $path) {
            $repository->set($key, $this->getItemsFromConfig($path));
        }
    }

    /**
     * Get config array from file.
     *
     * @param string $file
     * @return array
     */
    protected function getItemsFromConfig(string $file) {
        switch(pathinfo($file, PATHINFO_EXTENSION)) {
            case 'yaml':
                $items = Yaml::parseFile($file) ?? [];
                break;
            case 'yml':
                $items = Yaml::parseFile($file) ?? [];
                break;
            default:
                $items = require $file;
        }

        return $items;
    }
}