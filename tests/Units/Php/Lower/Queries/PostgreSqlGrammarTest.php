<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\Grammar\PostgreSqlGrammarTest as BasePostgreSqlGrammarTest;

class PostgreSqlGrammarTest extends BasePostgreSqlGrammarTest
{
    protected function setUp()
    {
        $this->grammar = Mockery::spy('Wilkques\Database\Queries\Grammar\Drivers\PostgreSql')->makePartial();

        $this->query = Mockery::spy('Wilkques\Database\Queries\Builder')->makePartial();
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
