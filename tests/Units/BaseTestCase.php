<?php

namespace Wilkques\Database\Tests\Units;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function envLoad($dir)
    {
        if (PHP_MAJOR_VERSION == 5) {
            $dotenv = \Dotenv\Dotenv::create($dir);
        } else {
            $dotenv = \Dotenv\Dotenv::createImmutable($dir);
        }

        $dotenv->load();
    }
}