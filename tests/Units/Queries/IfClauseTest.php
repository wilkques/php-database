<?php

namespace Wilkques\Database\Tests\Units\Queries;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Wilkques\Database\Queries\IfClause;

class IfClauseTest extends MockeryTestCase
{
    /** @var \Wilkques\Database\Queries\Builder|\Mockery\MockInterface */
    protected $parentBuilder;

    /** @var \Wilkques\Database\Queries\Grammar\Grammar|\Mockery\MockInterface */
    protected $grammar;

    protected function makeClause($condition)
    {
        return new IfClause($this->parentBuilder, $condition);
    }

    public function testThenReturnsSelf()
    {
        $clause = $this->makeClause('age >= 18');
        $this->assertSame($clause, $clause->then('Adult'));
    }

    public function testOtherwiseReturnsSelf()
    {
        $clause = $this->makeClause('age >= 18');
        $this->assertSame($clause, $clause->otherwise('Minor'));
    }

    public function testThenStoresTrueValueInQueries()
    {
        $clause = $this->makeClause('age >= 18');
        $clause->then('Adult');
        $this->assertEquals('Adult', $clause->getQuery('true_value'));
    }

    public function testOtherwiseStoresFalseValueInQueries()
    {
        $clause = $this->makeClause('age >= 18');
        $clause->otherwise('Minor');
        $this->assertEquals('Minor', $clause->getQuery('false_value'));
    }

    public function testEndCallsGrammarAndReturnsCompiledClause()
    {
        $this->grammar->shouldReceive('compileIf')
            ->once()
            ->with(Mockery::type('Wilkques\Database\Queries\IfClause'))
            ->andReturn(array('IF(age >= 18, ?, ?)', array('Adult', 'Minor')));

        $this->grammar->shouldReceive('contactBacktick')
            ->once()
            ->with('age_group')
            ->andReturn('`age_group`');

        $this->parentBuilder->shouldReceive('selectRaw')
            ->once()
            ->with('IF(age >= 18, ?, ?) AS `age_group`', array('Adult', 'Minor'))
            ->andReturnSelf();

        $result = $this->makeClause('age >= 18')->then('Adult')->otherwise('Minor')->end('age_group');

        $this->assertInstanceOf('Wilkques\Database\Queries\CompiledClause', $result);
        $this->assertEquals('age_group', $result->alias);
        $this->assertEquals('IF(age >= 18, ?, ?)', $result->rawSql);
    }

    public function testEndWithoutAlias()
    {
        $this->grammar->shouldReceive('compileIf')
            ->once()
            ->andReturn(array('IF(age >= 18, ?, ?)', array('Adult', 'Minor')));

        $this->parentBuilder->shouldReceive('selectRaw')
            ->once()
            ->with('IF(age >= 18, ?, ?)', array('Adult', 'Minor'))
            ->andReturnSelf();

        $this->makeClause('age >= 18')->then('Adult')->otherwise('Minor')->end();
    }

    public function testOtherwiseAcceptsIfClauseInstance()
    {
        $inner = $this->makeClause('age > 18');
        $inner->then('Adult')->otherwise('Minor');

        $outer = $this->makeClause('age > 30');
        $outer->then('Senior')->otherwise($inner);

        $this->assertSame($inner, $outer->getQuery('false_value'));
    }
}
