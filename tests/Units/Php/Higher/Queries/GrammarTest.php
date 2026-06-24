<?php

namespace Wilkques\Database\Tests\Units\Php\Higher\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\Grammar\GrammarTest as BaseGrammarTest;

class GrammarTest extends BaseGrammarTest
{
    protected function setUp(): void
    {
        $this->grammar = Mockery::spy('Wilkques\Database\Queries\Grammar\Grammar')->makePartial();
        $this->query   = Mockery::spy('Wilkques\Database\Queries\Builder')->makePartial();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
