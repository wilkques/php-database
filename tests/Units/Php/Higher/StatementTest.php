<?php

namespace Wilkques\Database\Tests\Units\Php\Higher;

use Mockery;
use Wilkques\Database\Connections\PDO\Statement;
use Wilkques\Database\Tests\Units\Connections\PDO\StatementTest as DriversStatementTest;

class StatementTest extends DriversStatementTest
{
    protected function setUp(): void
    {
        $this->pdoStatement = Mockery::mock('PDOStatement');

        $this->connections = Mockery::mock('Wilkques\Database\Connections\PDO\Drivers\MySql');

        $this->statement = new Statement($this->pdoStatement, $this->connections);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}