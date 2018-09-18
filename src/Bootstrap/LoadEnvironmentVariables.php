<?php
/**
 * Created by PhpStorm.
 * User: creeves
 * Date: 9/15/18
 * Time: 1:15 PM
 */

namespace Beryl\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Contracts\Foundation\Application;

class LoadEnvironmentVariables extends \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($app);

        try {
            $beryl_config = $app->get('beryl_config');
            $env_file = $beryl_config['env_file'] ?? $app->environmentFile();
            $env_path = getcwd();

            if (!empty($beryl_config['env_file_path'])) {
                $env_path .= rtrim($beryl_config['env_file_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            } else {
                $env_path .= $app->environmentFilePath();
            }

            (new Dotenv($env_path, $env_file))->load();
        } catch (InvalidPathException $e) {
            //
        } catch (InvalidFileException $e) {
            echo 'The environment file is invalid: '.$e->getMessage();
            die(1);
        }
    }
}