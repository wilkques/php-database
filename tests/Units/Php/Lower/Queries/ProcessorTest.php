<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\Processors\ProcessorTest as BaseProcessorTest;

class ProcessorTest extends BaseProcessorTest
{
    protected function setUp()
    {
        $this->connection = Mockery::spy('Wilkques\Database\Connections\PDO\PDO')->makePartial();

        $this->query = Mockery::spy('Wilkques\Database\Queries\Builder')->makePartial();

        $this->processor = Mockery::spy('Wilkques\Database\Queries\Processors\Processor')->makePartial();
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
