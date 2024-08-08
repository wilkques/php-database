<?php

namespace Wilkques\Tests\Grammar;

use PHPUnit\Framework\TestCase;
use Wilkques\Helpers\Arrays;

class GrammarTest extends TestCase
{
    private function builder($queries = array())
    {
        $abstract = $this->getMockBuilder('Wilkques\Database\Queries\Builder');

        $abstract->disableOriginalConstructor();

        /** @var \Wilkques\Database\Queries\Builder */
        $abstract = $abstract->getMockForAbstractClass();

        return $abstract->setQueries($queries);
    }

    private function grammar()
    {
        return $this->getMockForAbstractClass('Wilkques\Database\Queries\Grammar\Grammar');
    }

    public function testArrayNested()
    {
        $grammar = $this->grammar();

        $result = $grammar->arrayNested(array(
            'abc',
            new \Wilkques\Database\Queries\Expression('efg'),
        ));

        $this->assertEquals(
            array(
                'abc',
                new \Wilkques\Database\Queries\Expression('efg'),
            ),
            $result
        );

        $grammar = $this->grammar();

        $result = $grammar->arrayNested(array(
            'abc',
            new \Wilkques\Database\Queries\Expression('efg'),
        ), function ($column) {
            return "456.{$column}";
        });

        $this->assertEquals(
            array(
                "456.abc",
                new \Wilkques\Database\Queries\Expression('efg'),
            ),
            $result
        );

        $grammar = $this->grammar();

        $result = $grammar->arrayNested(array(
            'abc',
            new \Wilkques\Database\Queries\Expression('efg'),
        ), '?');

        $this->assertEquals(
            array(
                "?",
                new \Wilkques\Database\Queries\Expression('efg'),
            ),
            $result
        );
    }

    public function testCompilerColumns()
    {
        $columns = array();

        Arrays::set($columns, 'columns.queries', array('*'));

        $mock = $this->builder($columns);

        $this->assertEquals("*", $this->grammar()->compilerColumns($mock));

        $columns = array();

        Arrays::set($columns, 'columns.queries', array('abc', 'efg'));

        $mock = $this->builder($columns);

        $this->assertEquals("abc, efg", $this->grammar()->compilerColumns($mock));
    }

    public function testCompilerSelect()
    {
        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'columns' => array(
                    'queries' => array(
                        'dns_record.*',
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        'AND (`dns_record`.`id` = ?)',
                    ),
                    'bindings' => array(
                        448,
                    ),
                ),
                'orders' => array(
                    'queries' => array(
                        '`dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC',
                    ),
                    'bindings' => array(
                        127,
                        127,
                    ),
                ),
                'groups' => array(
                    'queries' => array(
                        0 => 'dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC',
                    ),
                    'bindings' => array(
                        0 => 127,
                        1 => 127,
                    ),
                ),
                'havings' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`provider_id` = ?'),
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`cdn_provider_id` = ?'),
                    ),
                    'bindings' => array(
                        1,
                        1,
                    ),
                ),
                'offset' => array(
                    'queries' => '?',
                    'bindings' => 1,
                ),
                'limits' => array(
                    'queries' => array(
                        '?',
                    ),
                    'bindings' => array(
                        10,
                    ),
                ),
            )
        );

        $this->assertEquals(
            "SELECT dns_record.* FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) WHERE (`dns_record`.`id` = ?) GROUP BY dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC HAVING `dns_record`.`provider_id` = ? AND `dns_record`.`cdn_provider_id` = ? ORDER BY `dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC LIMIT ? OFFSET ?",
            $this->grammar()->compilerSelect($mock)
        );
    }

    public function testCompilerFroms()
    {
        $froms = array();

        Arrays::set($froms, 'froms.queries', array('zones', 'dns_record'));

        $mock = $this->builder($froms);

        $this->assertEquals(
            "FROM zones, dns_record",
            $this->grammar()->compilerFroms($mock)
        );
    }

    public function testCompilerWheres()
    {
        $wheres = array();

        Arrays::set($wheres, 'wheres.queries', array('AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($wheres);

        $this->assertEquals(
            "WHERE (`dns_record`.`id` = ?)",
            $this->grammar()->compilerWheres($mock)
        );

        $wheres = array();

        Arrays::set($wheres, 'wheres.queries', array('AND (`dns_record`.`id` = ?)', 'AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($wheres);

        $this->assertEquals(
            "WHERE (`dns_record`.`id` = ?) AND (`dns_record`.`id` = ?)",
            $this->grammar()->compilerWheres($mock)
        );

        $wheres = array();

        Arrays::set($wheres, 'wheres.queries', array('OR (`dns_record`.`id` = ?)', 'AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($wheres);

        $this->assertEquals(
            "WHERE (`dns_record`.`id` = ?) AND (`dns_record`.`id` = ?)",
            $this->grammar()->compilerWheres($mock)
        );

        $wheres = array();

        Arrays::set($wheres, 'wheres.queries', array('OR (`dns_record`.`id` = ?)', 'OR (`dns_record`.`id` = ?)'));

        $mock = $this->builder($wheres);

        $this->assertEquals(
            "WHERE (`dns_record`.`id` = ?) OR (`dns_record`.`id` = ?)",
            $this->grammar()->compilerWheres($mock)
        );
    }

    public function testCompilerHavings()
    {
        $havings = array();

        Arrays::set($havings, 'havings.queries', array('AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($havings);

        $this->assertEquals(
            "HAVING (`dns_record`.`id` = ?)",
            $this->grammar()->compilerHavings($mock)
        );

        $havings = array();

        Arrays::set($havings, 'havings.queries', array('AND (`dns_record`.`id` = ?)', 'AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($havings);

        $this->assertEquals(
            "HAVING (`dns_record`.`id` = ?) AND (`dns_record`.`id` = ?)",
            $this->grammar()->compilerHavings($mock)
        );

        $havings = array();

        Arrays::set($havings, 'havings.queries', array('OR (`dns_record`.`id` = ?)', 'AND (`dns_record`.`id` = ?)'));

        $mock = $this->builder($havings);

        $this->assertEquals(
            "HAVING (`dns_record`.`id` = ?) AND (`dns_record`.`id` = ?)",
            $this->grammar()->compilerHavings($mock)
        );

        $havings = array();

        Arrays::set($havings, 'havings.queries', array('OR (`dns_record`.`id` = ?)', 'OR (`dns_record`.`id` = ?)'));

        $mock = $this->builder($havings);

        $this->assertEquals(
            "HAVING (`dns_record`.`id` = ?) OR (`dns_record`.`id` = ?)",
            $this->grammar()->compilerHavings($mock)
        );
    }

    public function testCompilerLimits()
    {
        $limits = array();

        Arrays::set($limits, 'limits.queries', array('?'));

        $mock = $this->builder($limits);

        $this->assertEquals(
            "LIMIT ?",
            $this->grammar()->compilerLimits($mock)
        );

        $limits = array();

        Arrays::set($limits, 'limits.queries', array('?', '?'));

        $mock = $this->builder($limits);

        $this->assertEquals(
            "LIMIT ?, ?",
            $this->grammar()->compilerLimits($mock)
        );
    }

    public function testCompilerGroups()
    {
        $groups = array();

        Arrays::set($groups, 'groups.queries', array('zones.id DESC'));

        $mock = $this->builder($groups);

        $this->assertEquals(
            "GROUP BY zones.id DESC",
            $this->grammar()->compilerGroups($mock)
        );

        $groups = array();

        Arrays::set($groups, 'groups.queries', array('zones.id DESC', 'zones.id ASC'));

        $mock = $this->builder($groups);

        $this->assertEquals(
            "GROUP BY zones.id DESC, zones.id ASC",
            $this->grammar()->compilerGroups($mock)
        );
    }

    public function testCompilerOrders()
    {
        $orders = array();

        Arrays::set($orders, 'orders.queries', array('zones.id DESC'));

        $mock = $this->builder($orders);

        $this->assertEquals(
            "ORDER BY zones.id DESC",
            $this->grammar()->compilerOrders($mock)
        );

        $orders = array();

        Arrays::set($orders, 'orders.queries', array('zones.id DESC', 'zones.id ASC'));

        $mock = $this->builder($orders);

        $this->assertEquals(
            "ORDER BY zones.id DESC, zones.id ASC",
            $this->grammar()->compilerOrders($mock)
        );
    }

    public function testCompilerOffset()
    {
        $offset = array();

        Arrays::set($offset, 'offset.queries', '?');

        $mock = $this->builder($offset);

        $this->assertEquals(
            "OFFSET ?",
            $this->grammar()->compilerOffset($mock)
        );
    }

    public function testCompilerLock()
    {
        $lock = array();

        Arrays::set($lock, 'lock', 'FOR UPDATE');

        $mock = $this->builder($lock);

        $this->assertEquals(
            "FOR UPDATE",
            $this->grammar()->compilerLock($mock)
        );

        $lock = array();

        Arrays::set($lock, 'lock', 'LOCK IN SHARE MODE');

        $mock = $this->builder($lock);

        $this->assertEquals(
            "LOCK IN SHARE MODE",
            $this->grammar()->compilerLock($mock)
        );
    }

    public function testCompilerJoins()
    {
        $joins = array();

        Arrays::set($joins, 'joins.queries', array(
            'INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'
        ));

        $mock = $this->builder($joins);

        $this->assertEquals(
            "INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)",
            $this->grammar()->compilerJoins($mock)
        );
    }

    public function testCompilerComponent()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerComponent');

        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($abstract, $this->builder());

        $this->assertEquals(
            array(),
            $result
        );

        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'columns' => array(
                    'queries' => array(
                        'dns_record.*',
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        'AND (`dns_record`.`id` = ?)',
                    ),
                    'bindings' => array(
                        448,
                    ),
                ),
                'orders' => array(
                    'queries' => array(
                        '`dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC',
                    ),
                    'bindings' => array(
                        127,
                        127,
                    ),
                ),
                'groups' => array(
                    'queries' => array(
                        0 => 'dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC',
                    ),
                    'bindings' => array(
                        0 => 127,
                        1 => 127,
                    ),
                ),
                'havings' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`provider_id` = ?'),
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`cdn_provider_id` = ?'),
                    ),
                    'bindings' => array(
                        1,
                        1,
                    ),
                ),
                'offset' => array(
                    'queries' => '?',
                    'bindings' => 1,
                ),
                'limits' => array(
                    'queries' => array(
                        '?',
                    ),
                    'bindings' => array(
                        10,
                    ),
                ),
            )
        );

        $result = $reflectionMethod->invoke($abstract, $mock);

        $this->assertEquals(
            array(
                "columns" => "dns_record.*",
                "froms" => "FROM `dns_record`",
                "joins" => "INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)",
                "wheres" => "WHERE (`dns_record`.`id` = ?)",
                "groups" => "GROUP BY dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC",
                "havings" => "HAVING `dns_record`.`provider_id` = ? AND `dns_record`.`cdn_provider_id` = ?",
                "orders" => "ORDER BY `dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC",
                "limits" => "LIMIT ?",
                "offset" => "OFFSET ?",
            ),
            $result
        );
    }

    public function testConcatenate()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'concatenate');

        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($abstract, array());

        $this->assertEquals(
            '',
            $result
        );

        $result = $reflectionMethod->invoke($abstract, array(
            "columns" => "dns_record.*",
            "froms" => "FROM `dns_record`",
            "joins" => "INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)",
            "wheres" => "WHERE (`dns_record`.`id` = ?)",
            "groups" => "GROUP BY dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC",
            "havings" => "HAVING `dns_record`.`provider_id` = ? AND `dns_record`.`cdn_provider_id` = ?",
            "orders" => "ORDER BY `dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC",
            "limits" => "LIMIT ?",
            "offset" => "OFFSET ?",
        ));

        $this->assertEquals(
            "dns_record.* FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) WHERE (`dns_record`.`id` = ?) GROUP BY dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC HAVING `dns_record`.`provider_id` = ? AND `dns_record`.`cdn_provider_id` = ? ORDER BY `dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC LIMIT ? OFFSET ?",
            $result
        );
    }

    public function testCompilerUpdate()
    {
        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                )
            )
        );

        $this->assertEquals(
            "UPDATE `dns_record` SET abc = ? WHERE `id` IN (?,?)",
            $this->grammar()->compilerUpdate($mock, array('abc'))
        );

        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression(
                            'INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'
                        ),
                    ),
                )
            )
        );

        $this->assertEquals(
            "UPDATE `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) SET abc = ? WHERE `id` IN (?,?)",
            $this->grammar()->compilerUpdate($mock, array('abc'))
        );
    }

    public function testCompilerUpdateWithoutJoins()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerUpdateWithoutJoins');

        $reflectionMethod->setAccessible(true);

        $mock = $this->builder(
            array(
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                )
            )
        );

        $result = $reflectionMethod->invoke($abstract, $mock, '`dns_record`', 'abc = ?');

        $this->assertEquals(
            "UPDATE `dns_record` SET abc = ? WHERE `id` IN (?,?)",
            $result
        );
    }

    public function testCompilerUpdateWithJoins()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerUpdateWithJoins');

        $reflectionMethod->setAccessible(true);

        $mock = $this->builder(
            array(
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression(
                            'INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'
                        ),
                    ),
                )
            )
        );

        $result = $reflectionMethod->invoke($abstract, $mock, '`dns_record`', 'abc = ?');

        $this->assertEquals(
            "UPDATE `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) SET abc = ? WHERE `id` IN (?,?)",
            $result
        );
    }

    public function testCompilerUnions()
    {
        $unions = array();

        Arrays::set($unions, 'unions.queries', array('UNION SELECT * FROM `dns_record` WHERE `id` IN (?,?)'));

        $mock = $this->builder($unions);

        $this->assertEquals(
            "UNION SELECT * FROM `dns_record` WHERE `id` IN (?,?)",
            $this->grammar()->compilerUnions($mock)
        );
    }

    public function testCompilerInsert()
    {
        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                )
            )
        );

        $this->assertEquals(
            "INSERT INTO `dns_record` (`abc`) VALUES (?)",
            $this->grammar()->compilerInsert($mock, array('abc' => 1))
        );

        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression(
                            'INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'
                        ),
                    ),
                )
            )
        );

        $this->assertEquals(
            "UPDATE `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) SET abc = ? WHERE `id` IN (?,?)",
            $this->grammar()->compilerUpdate($mock, array('abc'))
        );
    }

    public function testCompilerInsertWithoutSubQuery()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerInsertWithoutSubQuery');

        $reflectionMethod->setAccessible(true);

        $this->assertEquals(
            "INSERT INTO dns_record (abc) VALUES (1)",
            $reflectionMethod->invoke($abstract, "dns_record", "abc", "1")
        );
    }

    public function testCompilerInsertWithSubQuery()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerInsertWithSubQuery');

        $reflectionMethod->setAccessible(true);

        $this->assertEquals(
            "INSERT INTO dns_record (abc) SELECT abc FROM dns_record",
            $reflectionMethod->invoke($abstract, "dns_record", "abc", "SELECT abc FROM dns_record")
        );
    }

    public function testCompilerDelete()
    {
        $mock = $this->builder(array(
            'froms' => array(
                'queries' => array(
                    new \Wilkques\Database\Queries\Expression('`dns_record`'),
                ),
            ),
            'wheres' => array(
                'queries' => array(
                    new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                ),
                'bindings' => array(
                    443,
                    444,
                ),
            ),
        ));

        $this->assertEquals(
            "DELETE FROM `dns_record` WHERE `id` IN (?,?)",
            $this->grammar()->compilerDelete($mock)
        );

        $mock = $this->builder(array(
            'froms' => array(
                'queries' => array(
                    new \Wilkques\Database\Queries\Expression('`dns_record`'),
                ),
            ),
            'wheres' => array(
                'queries' => array(
                    new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)')
                ),
                'bindings' => array(
                    443,
                    444,
                ),
            ),
            'joins' => array(
                'bindings' => array(
                    127,
                    127,
                ),
                'queries' => array(
                    new \Wilkques\Database\Queries\Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
                ),
            ),
        ));

        $this->assertEquals(
            "DELETE FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) WHERE `id` IN (?,?)",
            $this->grammar()->compilerDelete($mock)
        );
    }

    public function testCompilerDeleteWithoutJoins()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerDeleteWithoutJoins');

        $reflectionMethod->setAccessible(true);

        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)'),
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                ),
            )
        );

        $result = $reflectionMethod->invoke($abstract, $mock);

        $this->assertEquals(
            "DELETE FROM `dns_record` WHERE `id` IN (?,?)",
            $result
        );
    }

    public function testCompilerDeleteWithJoins()
    {
        $abstract = $this->grammar();

        $reflectionMethod = new \ReflectionMethod($abstract, 'compilerDeleteWithJoins');

        $reflectionMethod->setAccessible(true);

        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `id` IN (?,?)')
                    ),
                    'bindings' => array(
                        443,
                        444,
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
                    ),
                ),
            )
        );

        $result = $reflectionMethod->invoke($abstract, $mock, '`dns_record`', 'abc = ?');

        $this->assertEquals(
            "DELETE FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) WHERE `id` IN (?,?)",
            $result
        );
    }

    public function testCompilerCount()
    {
        $mock = $this->builder(
            array(
                'froms' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('`dns_record`'),
                    ),
                ),
                'columns' => array(
                    'queries' => array(
                        'dns_record.*',
                    ),
                ),
                'joins' => array(
                    'bindings' => array(
                        127,
                        127,
                    ),
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
                    ),
                ),
                'wheres' => array(
                    'queries' => array(
                        'AND (`dns_record`.`id` = ?)',
                    ),
                    'bindings' => array(
                        448,
                    ),
                ),
                'orders' => array(
                    'queries' => array(
                        '`dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC',
                    ),
                    'bindings' => array(
                        127,
                        127,
                    ),
                ),
                'groups' => array(
                    'queries' => array(
                        0 => 'dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC',
                    ),
                    'bindings' => array(
                        0 => 127,
                        1 => 127,
                    ),
                ),
                'havings' => array(
                    'queries' => array(
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`provider_id` = ?'),
                        new \Wilkques\Database\Queries\Expression('AND `dns_record`.`cdn_provider_id` = ?'),
                    ),
                    'bindings' => array(
                        1,
                        1,
                    ),
                ),
                'offset' => array(
                    'queries' => '?',
                    'bindings' => 1,
                ),
                'limits' => array(
                    'queries' => array(
                        '?',
                    ),
                    'bindings' => array(
                        10,
                    ),
                ),
            )
        );

        $this->assertEquals(
            "SELECT COUNT(*) AS `aggregate` FROM (SELECT dns_record.* FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`) WHERE (`dns_record`.`id` = ?) GROUP BY dns_record.id DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, dns_record.provider_id DESC HAVING `dns_record`.`provider_id` = ? AND `dns_record`.`cdn_provider_id` = ? ORDER BY `dns_record`.`id` DESC, (SELECT MAX(`dns_record`.`id`) FROM `dns_record` INNER JOIN (SELECT * FROM `default`.`zones` WHERE `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` WHERE `zones`.`id` = ?) DESC, `dns_record`.`provider_id` DESC LIMIT ? OFFSET ?) AS `aggregate_table`",
            $this->grammar()->compilerCount($mock)
        );
    }
}
