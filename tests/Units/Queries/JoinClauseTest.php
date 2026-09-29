<?php

namespace Wilkques\Database\Tests\Units\Queries;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\JoinClause;
use Wilkques\Database\Tests\Units\Queries\Support\ConnectionsStub;

class JoinClauseTest extends TestCase
{
    private function connection()
    {
        // Not $this->getMockForAbstractClass(): removed in PHPUnit 12, which
        // "phpunit/phpunit": "*" silently resolved to on PHP 8.3 CI runs.
        return new ConnectionsStub;
    }

    private function builder()
    {
        // A real Grammar/Processor, not null: JoinClause's constructor
        // calls setTable() (which needs contactBacktick(), resolved via
        // Builder::__call()'s resolvers array) as soon as it's built —
        // this whole test file was never actually wired into either
        // phpunit-higher.xml or phpunit-lower.xml until now (no
        // tests/Units/Php/{Higher,Lower}/Queries/JoinClauseTest.php
        // existed to pull it in via inheritance), so this gap was never
        // exercised.
        return new Builder(
            $this->connection(),
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor
        );
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
        // Not $this->join() (removed, see testOn()'s note above): a real
        // JoinClause built the same way as every other test in this file.
        $join = new JoinClause($this->builder(), 'inner', 'abc');

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
        // Not $this->join() (removed, see testOn()'s note above): a real
        // JoinClause built the same way as every other test in this file.
        $join = new JoinClause($this->builder(), 'inner', 'abc');

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
        // Needs a real Grammar to resolve contactBacktick(), which is only
        // populated by JoinClause's real constructor.
        $join = new JoinClause($this->builder(), 'inner', 'abc');

        $join->on('abc.id', 'efg.id');

        $this->assertEquals(
            array(
                'AND `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = new JoinClause($this->builder(), 'inner', 'abc');

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
        // See testOn(): needs a real Grammar to resolve contactBacktick().
        $join = new JoinClause($this->builder(), 'inner', 'abc');

        $join->orOn('abc.id', 'efg.id');

        $this->assertEquals(
            array(
                'OR `abc`.`id` = `efg`.`id`',
            ),
            $join->getQuery('joins.queries')
        );

        $join = new JoinClause($this->builder(), 'inner', 'abc');

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

    public function testClosureBasedJoinConditionCompilesCorrectSql()
    {
        // Regression test: JoinClause::__construct() used to call
        // setTable() before setParentClass() was set, but the old
        // setTable() (routing through newQuery()->from()) needed
        // $parentClass already set to build its throwaway instance —
        // fataled ("Class name must be a valid object or a string") the
        // moment any closure-based join condition was used, i.e. the
        // entire multi-condition join() API shown in this README's "join"
        // section. Separately, JoinClause::on() called a queryPush()
        // method that has never existed anywhere in this codebase, so
        // even after the constructor crash was fixed, on()/orOn() still
        // fataled ("Method: `queryPush` Not Exists") the instant they ran.
        $builder = new Builder(
            $this->connection(),
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor
        );

        $sql = $builder->from('orders')->join('users', function ($join) {
            $join->on('orders.user_id', 'users.id')
                ->orOn('orders.backup_user_id', 'users.id');
        })->toSql();

        $this->assertEquals(
            'SELECT * FROM `orders` INNER JOIN `users` ON `orders`.`user_id` = `users`.`id` OR `orders`.`backup_user_id` = `users`.`id`',
            $sql
        );
    }
}
