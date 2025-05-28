<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Connections;

use Mockery;
use Wilkques\Database\Connections\PDO\Statement;
use Wilkques\Database\Tests\Units\Connections\PDO\StatementTest as DriversStatementTest;

class StatementTest extends DriversStatementTest
{
    protected function setUp()
    {
        $this->pdoStatement = Mockery::mock('PDOStatement');

        $this->connections = Mockery::mock('Wilkques\Database\Connections\PDO\Drivers\MySql');

        $this->statement = new Statement($this->pdoStatement, $this->connections);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}