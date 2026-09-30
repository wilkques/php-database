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

    public function testContactBacktickWithAsAlias()
    {
        $result = $this->grammar->contactBacktick('loginlog as lg1');

        $this->assertEquals('"loginlog" AS "lg1"', $result);
    }

    public function testContactBacktickDoesNotSplitOnPlainWhitespace()
    {
        // "table alias"（沒有 as 關鍵字）不該被誤判成 schema.table
        $result = $this->grammar->contactBacktick('loginlog lg1');

        $this->assertEquals('"loginlog lg1"', $result);
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
