<?php

namespace Wilkques\Database\Tests\Units;

use Wilkques\Database\Connections\PDO\Drivers\MySql as MySqlConnection;
use Wilkques\Database\Database;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Grammar\Drivers\MySql as MySqlGrammar;
use Wilkques\Database\Tests\BaseTestCase;
use Wilkques\Helpers\Arrays;

class DatabaseTest extends BaseTestCase
{
    public function testConnection()
    {
        $dir = dirname(__DIR__);

        $this->envLoad($dir);

        $driver = Arrays::get($_ENV, 'DB_DRIVER');

        $host = Arrays::get($_ENV, 'DB_HOST');

        $username = Arrays::get($_ENV, 'DB_USER');

        $password = Arrays::get($_ENV, 'DB_PASSWORD');

        $database = Arrays::get($_ENV, 'DB_NAME_1');

        $builder = Database::connect(
            $driver, $host, $username, $password, $database
        );

        $this->assertTrue(
            $builder instanceof Builder
        );

        $this->assertTrue(
            $builder->getConnection() instanceof MySqlConnection
        );

        $this->assertTrue(
            $builder->getGrammar() instanceof MySqlGrammar
        );

        $builder = Database::connect(compact('driver', 'host', 'username', 'password', 'database'));

        $this->assertTrue(
            $builder instanceof Builder
        );

        $this->assertTrue(
            $builder->getConnection() instanceof MySqlConnection
        );

        $this->assertTrue(
            $builder->getGrammar() instanceof MySqlGrammar
        );
    }
}