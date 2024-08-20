<?php

namespace Wilkques\Database\Tests;

use PHPUnit\Framework\TestCase;
use Wilkques\Helpers\Arrays;

class BaseTestCase extends TestCase
{
    protected $config;

    protected function configLoad($dir)
    {
        $path = "{$dir}/config.php";

        $config = require($path);

        return $this->setConfig($config);
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
