<?php

namespace Wilkques\Database\Tests\Units\Queries\Grammar;

use Mockery\Adapter\Phpunit\MockeryTestCase;

class PostgreSqlGrammarTest extends MockeryTestCase
{
    protected $grammar;

    protected $query;

    public function testLockForUpdate()
    {
        $result = $this->grammar->lockForUpdate();

        $this->assertEquals('FOR UPDATE', $result);
    }

    public function testSharedLock()
    {
        $result = $this->grammar->sharedLock();

        $this->assertEquals('FOR SHARE', $result);
    }

    public function testContactBacktick()
    {
        $result = $this->grammar->contactBacktick('users.name');

        $this->assertEquals('"users"."name"', $result);
    }

    public function testCompilerCount()
    {
        // Mock the compilerSelect method
        $this->grammar->shouldReceive('compilerSelect')
            ->with($this->query)
            ->andReturn('SELECT * FROM posts WHERE status = \'active\'');

        // Call the method under test
        $result = $this->grammar->compilerCount($this->query);

        // Define the expected SQL
        $expected = 'SELECT COUNT(*) AS "aggregate" FROM (SELECT * FROM posts WHERE status = \'active\') AS "aggregate_table"';

        // Assert that the generated SQL matches the expected SQL
        $this->assertEquals($expected, $result);
    }

    public function testCompilerLimitsSingleArgument()
    {
        $this->query->shouldReceive('getQuery')
            ->with('limits.queries', array())
            ->andReturn(array('?'));

        $result = $this->grammar->compilerLimits($this->query);

        $this->assertEquals('LIMIT ?', $result);
    }

    public function testCompilerLimitsWithOffset()
    {
        $this->query->shouldReceive('getQuery')
            ->with('limits.queries', array())
            ->andReturn(array('?', '?'));

        $result = $this->grammar->compilerLimits($this->query);

        $this->assertEquals('LIMIT ? OFFSET ?', $result);
    }

    public function testCompilerLimitsWithEmptyValues()
    {
        $this->query->shouldReceive('getQuery')
            ->with('limits.queries', array())
            ->andReturn(array());

        $result = $this->grammar->compilerLimits($this->query);

        $this->assertFalse($result);
    }
}
