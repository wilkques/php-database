<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Connections;

use Mockery;
use Wilkques\Database\Connections\PDO\Result;
use Wilkques\Database\Tests\Units\Connections\PDO\ResultTest as BaseResultTest;

class ResultTest extends BaseResultTest
{
    protected function setUp()
    {
        $this->statement = Mockery::mock('PDOStatement');

        $this->connections = Mockery::mock('Wilkques\Database\Connections\PDO\PDO');

        $this->result = new Result($this->statement, $this->connections);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
