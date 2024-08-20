<?php

namespace Wilkques\Database\Tests;

use PHPUnit\Framework\TestCase;
use Wilkques\Helpers\Arrays;

class BaseTestCase extends TestCase
{
    protected $config;

    protected function envLoad($dir)
    {
        $path = "{$dir}/config.php";

        if (file_exists($path)) {
            $config = require($path);

            return $this->setConfig($config);
        }

        if (method_exists('\Dotenv\Dotenv', 'createImmutable')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($dir);
        } else {
            $dotenv = \Dotenv\Dotenv::create($dir);
        }

        $dotenv->load();

        $this->setConfig($_ENV);

        $config = var_export($_ENV, true);

        $phpString = "<?php \n\n return {$config};";

        file_put_contents($path, $phpString);

        return $this;
    }

    protected function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    protected function getConfigItems()
    {
        return $this->config;
    }

    protected function getConfigItem($key, $default = null)
    {
        return Arrays::get($this->config, $key, $default);
    }
}
