<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\BuilderTest as QueriesBuilderTest;

class BuilderTest extends QueriesBuilderTest
{
    protected function setUp()
    {
        $this->connection = Mockery::spy('Wilkques\Database\Connections\PDO\PDO')->makePartial();

        $this->grammar = Mockery::spy('Wilkques\Database\Queries\Grammar\Grammar')->makePartial();

        $this->processor = Mockery::spy('Wilkques\Database\Queries\Processors\Processor')->makePartial();

        $this->query = Mockery::spy('Wilkques\Database\Queries\Builder')->makePartial()->shouldAllowMockingProtectedMethods();

        $this->arrays = Mockery::spy('Wilkques\Helpers\Arrays')->makePartial();
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
