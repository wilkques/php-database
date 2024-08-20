<?php

namespace Wilkques\Database\Tests\Units;

use Wilkques\Database\Connections\PDO\Drivers\MySql as MySqlConnection;
use Wilkques\Database\Database;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Grammar\Drivers\MySql as MySqlGrammar;
use Wilkques\Database\Tests\BaseTestCase;

class DatabaseTest extends BaseTestCase
{
    public function testConnection()
    {
        $dir = dirname(__DIR__);

        $this->configLoad($dir);

        $driver = $this->getConfigItem('DB_DRIVER');

        $host = $this->getConfigItem('DB_HOST');

        $username = $this->getConfigItem('DB_USER');

        $password = $this->getConfigItem('DB_PASSWORD');

        $database = $this->getConfigItem('DB_NAME_1');

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