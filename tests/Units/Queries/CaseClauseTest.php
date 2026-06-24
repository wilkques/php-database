<?php

namespace Wilkques\Database\Tests\Units\Queries;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Wilkques\Database\Queries\CaseClause;

class CaseClauseTest extends MockeryTestCase
{
    /** @var \Wilkques\Database\Queries\Builder|\Mockery\MockInterface */
    protected $parentBuilder;

    /** @var \Wilkques\Database\Queries\Grammar\Grammar|\Mockery\MockInterface */
    protected $grammar;

    protected function makeClause($column = null)
    {
        return new CaseClause($this->parentBuilder, $column);
    }

    public function testWhenReturnsSelf()
    {
        $clause = $this->makeClause('status');
        $this->assertSame($clause, $clause->when('active', 'Active'));
    }

    public function testOtherwiseReturnsSelf()
    {
        $clause = $this->makeClause('status');
        $this->assertSame($clause, $clause->otherwise('Unknown'));
    }

    public function testWhenStoresConditionInQueries()
    {
        $clause = $this->makeClause('status');
        $clause->when('active', 'Active');

        $conditions = $clause->getQuery('conditions', array());
        $this->assertCount(1, $conditions);
        $this->assertEquals('active', $conditions[0]['when']);
        $this->assertEquals('Active', $conditions[0]['then']);
    }

    public function testOtherwiseStoresElseInQueries()
    {
        $clause = $this->makeClause('status');
        $clause->otherwise('Unknown');

        $this->assertTrue($clause->getQuery('has_else', false));
        $this->assertEquals('Unknown', $clause->getQuery('else_value'));
    }

    public function testEndCallsGrammarAndReturnsParentBuilder()
    {
        $this->grammar->shouldReceive('compileCase')
            ->once()
            ->with(Mockery::type('Wilkques\Database\Queries\CaseClause'))
            ->andReturn(array('CASE `status` WHEN ? THEN ? END', array('active', 'Active')));

        $this->grammar->shouldReceive('contactBacktick')
            ->once()
            ->with('label')
            ->andReturn('`label`');

        $this->parentBuilder->shouldReceive('selectRaw')
            ->once()
            ->with('CASE `status` WHEN ? THEN ? END AS `label`', array('active', 'Active'))
            ->andReturnSelf();

        $result = $this->makeClause('status')->when('active', 'Active')->end('label');

        $this->assertSame($this->parentBuilder, $result);
    }

    public function testEndWithoutAlias()
    {
        $this->grammar->shouldReceive('compileCase')
            ->once()
            ->andReturn(array('CASE `status` WHEN ? THEN ? END', array('active', 'Active')));

        $this->parentBuilder->shouldReceive('selectRaw')
            ->once()
            ->with('CASE `status` WHEN ? THEN ? END', array('active', 'Active'))
            ->andReturnSelf();

        $this->makeClause('status')->when('active', 'Active')->end();
    }
}
