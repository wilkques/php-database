<?php

namespace Wilkques\Tests\Units\Connections;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function envLoad()
    {
        $dir = dirname(dirname(dirname(dirname(__DIR__))));

        if (PHP_MAJOR_VERSION == 5) {
            $dotenv = \Dotenv\Dotenv::create($dir);
        } else {
            $dotenv = \Dotenv\Dotenv::createImmutable($dir);
        }

        $dotenv->load();
    }
}