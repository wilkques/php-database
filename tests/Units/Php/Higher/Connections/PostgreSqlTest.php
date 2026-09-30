<?php

namespace Wilkques\Database\Tests\Units\Php\Higher\Connections;

use Wilkques\Database\Tests\Units\Connections\PDO\Drivers\PostgreSqlTest as DriversPostgreSqlTest;

class PostgreSqlTest extends DriversPostgreSqlTest
{
    protected function setUp(): void
    {
        $this->init();

        $this->connection();

        $this->cleanupDatabase();

        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupDatabase();
    }
}
