<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Connections;

use Wilkques\Database\Tests\Units\Connections\PDO\Drivers\PostgreSqlTest as DriversPostgreSqlTest;

class PostgreSqlTest extends DriversPostgreSqlTest
{
    protected function setUp()
    {
        $this->init();

        $this->connection();

        $this->cleanupDatabase();

        $this->setupDatabase();
    }

    protected function tearDown()
    {
        $this->cleanupDatabase();
    }
}
