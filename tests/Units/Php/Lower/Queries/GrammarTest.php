<?php

namespace Wilkques\Database\Tests\Units\Php\Lower\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\Grammar\GrammarTest as BaseGrammarTest;

class GrammarTest extends BaseGrammarTest
{
    protected function setUp()
    {
        $this->grammar = Mockery::spy('Wilkques\Database\Queries\Grammar\Grammar')->makePartial();

        $this->query = Mockery::spy('Wilkques\Database\Queries\Builder')->makePartial();
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
