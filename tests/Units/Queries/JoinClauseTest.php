<?php

namespace Wilkques\Database\Tests\Units\Queries;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\JoinClause;

class JoinClauseTest extends TestCase
{
    private function connection()
    {
        return $this->getMockForAbstractClass(
            'Wilkques\Database\Connections\Connections',
            array(),
            '',
            false
        );
    }

    private function builder()
    {
        return new Builder($this->connection());
    }

    private function join()
    {
        $abstract = $this->getMockBuilder('Wilkques\Database\Queries\JoinClause');

        $abstract->disableOriginalConstructor();

        /** @var \Wilkques\Database\Queries\Builder */
        $abstract = $abstract->getMockForAbstractClass();

        return $abstract;
    }

    public function testConstruct()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertTrue(
            $join instanceof JoinClause
        );
    }

    public function testGetType()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertEquals(
            'inner',
            $join->getType()
        );
    }

    public function testSetType()
    {
        $join = $this->join();

        $join->setType('left');

        $this->assertEquals(
            'left',
            $join->getType()
        );
    }

    public function testGetParentClass()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertEquals(
            'Wilkques\Database\Queries\Builder',
            $join->getParentClass()
        );
    }

    public function testSetParentClass()
    {
        $join = $this->join();

        $join->setParentClass(
            'Wilkques\Database\Queries\Builder'
        );

        $this->assertEquals(
            'Wilkques\Database\Queries\Builder',
            $join->getParentClass()
        );
    }

    public function testOn()
    {
        $join = $this->join();

        $join->on('abc.id', 'efg.id');

        $this->assertEquals(
            array(
                'AND `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = $this->join();

        $join->on('abc.id', '=', 'efg.id');

        $this->assertEquals(
            array(
                'AND `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $join->on(function ($join) {
            $join->on('abc.id', 'efg.id');
        });

        $this->assertEquals(
            array(
                'AND (`abc`.`id` = `efg`.`id`)',
            ),
            $join->getQuery('joins.queries')
        );
    }

    public function testOrOn()
    {
        $join = $this->join();

        $join->orOn('abc.id', 'efg.id');

        $this->assertEquals(
            array(
                'OR `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = $this->join();

        $join->orOn('abc.id', '=', 'efg.id');

        $this->assertEquals(
            array(
                'OR `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $join->orOn(function ($join) {
            $join->orOn('abc.id', 'efg.id');
        });

        $this->assertEquals(
            array(
                'OR (`abc`.`id` = `efg`.`id`)',
            ),
            $join->getQuery('joins.queries')
        );
    }

    public function testNewQuery()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertTrue(
            $join->newQuery() instanceof JoinClause
        );
    }

    public function testNewParentQuery()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertTrue(
            $join->newParentQuery() instanceof Builder
        );
    }

    public function testForSubQuery()
    {
        $join = new JoinClause(
            $this->builder(),
            'inner',
            'abc'
        );

        $this->assertTrue(
            $join->forSubQuery() instanceof Builder
        );
    }
}
