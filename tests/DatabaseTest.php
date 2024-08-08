<?php

namespace Wilkques\Tests;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Connections\PDO\Drivers\MySql as MySqlConnection;
use Wilkques\Database\Database;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Grammar\Drivers\MySql as MySqlGrammar;

class DatabaseTest extends TestCase
{
    public function testConnection()
    {
        $driver = 'mysql';

        $host = getenv('DB_HOST') ?: '127.0.0.1';

        $username = getenv('DB_USER') ?: 'user';

        $password = getenv('DB_PASSWORD') ?: 'root';

        $database = getenv('DB_NAME') ?: 'test';

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