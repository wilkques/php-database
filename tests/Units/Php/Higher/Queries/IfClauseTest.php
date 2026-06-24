<?php

namespace Wilkques\Database\Tests\Units\Php\Higher\Queries;

use Mockery;
use Wilkques\Database\Tests\Units\Queries\IfClauseTest as BaseIfClauseTest;

class IfClauseTest extends BaseIfClauseTest
{
    protected function setUp(): void
    {
        $this->grammar = Mockery::mock('Wilkques\Database\Queries\Grammar\Grammar');

        $this->parentBuilder = Mockery::mock('Wilkques\Database\Queries\Builder');
        $this->parentBuilder->shouldReceive('getConnection')->andReturn(Mockery::mock('Wilkques\Database\Connections\Connections'));
        $this->parentBuilder->shouldReceive('getGrammar')->andReturn($this->grammar);
        $this->parentBuilder->shouldReceive('getProcessor')->andReturn(Mockery::mock('Wilkques\Database\Queries\Processors\ProcessorInterface'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
