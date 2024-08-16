<?php

namespace Wilkques\Database\Tests\Units\Queries;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wilkques\Database\Connections\Connections;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Expression;
use Wilkques\Database\Queries\Grammar\Grammar;
use Wilkques\Database\Queries\JoinClause;
use Wilkques\Database\Queries\Processors\ProcessorInterface;

class BuilderTest extends TestCase
{
    private function grammar()
    {
        return $this->getMockForAbstractClass(
            'Wilkques\Database\Queries\Grammar\Grammar',
            array(),
            '',
            false
        );
    }

    private function connection()
    {
        return $this->getMockForAbstractClass(
            'Wilkques\Database\Connections\Connections',
            array(),
            '',
            false
        );
    }

    private function process()
    {
        $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        return call_user_func(array($this, $createMock), 'Wilkques\Database\Queries\Processors\Processor');
    }

    private function builder()
    {
        return new Builder(
            $this->connection(),
            $this->grammar(),
            $this->process()
        );
    }

    public function testMake()
    {
        $builder = Builder::make(
            $this->connection(),
            $this->grammar(),
            $this->process()
        );

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testResolverRegister()
    {
        $builder = $this->builder();

        $resolverRegisterMethod = new \ReflectionMethod($builder, 'resolverRegister');

        $resolverRegisterMethod->setAccessible(true);

        $result = $resolverRegisterMethod->invoke($builder, $this->connection());

        $this->assertTrue(
            $result instanceof Builder
        );
    }

    public function testGetResolvers()
    {
        $builder = $this->builder();

        $getResolversMethod = new \ReflectionMethod($builder, 'getResolvers');

        $getResolversMethod->setAccessible(true);

        $this->assertTrue(
            is_array($getResolversMethod->invoke($builder))
        );
    }

    public function testGetResolver()
    {
        $builder = $this->builder();

        $getResolverMethod = new \ReflectionMethod($builder, 'getResolver');

        $getResolverMethod->setAccessible(true);

        $this->assertTrue(
            $getResolverMethod->invoke($builder, 'Wilkques\Database\Connections\Connections') instanceof Connections
        );

        $this->assertTrue(
            $getResolverMethod->invoke($builder, 'Wilkques\Database\Queries\Grammar\Grammar') instanceof Grammar
        );

        $this->assertTrue(
            $getResolverMethod->invoke($builder, 'Wilkques\Database\Queries\Processors\ProcessorInterface') instanceof ProcessorInterface
        );
    }

    public function testSetConnection()
    {
        $builder = $this->builder();

        $builder = $builder->setConnection($this->connection());

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testGetConnection()
    {
        $builder = $this->builder();

        $connection = $builder->getConnection();

        $this->assertTrue(
            $connection instanceof Connections
        );
    }

    public function testSetGrammar()
    {
        $builder = $this->builder();

        $builder = $builder->setGrammar($this->grammar());

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testGetGrammar()
    {
        $builder = $this->builder();

        $grammar = $builder->getGrammar();

        $this->assertTrue(
            $grammar instanceof Grammar
        );
    }

    public function testSetProcessor()
    {
        $builder = $this->builder();

        $builder = $builder->setProcessor($this->process());

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testGetProcessor()
    {
        $builder = $this->builder();

        $process = $builder->getProcessor();

        $this->assertTrue(
            $process instanceof ProcessorInterface
        );
    }

    public function testSetQueries()
    {
        $builder = $this->builder();

        $builder = $builder->setQueries(array());

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testGetQueries()
    {
        $builder = $this->builder();

        $queries = $builder->getQueries();

        $this->assertTrue(
            is_array($queries)
        );
    }

    public function testSetQuery()
    {
        $builder = $this->builder();

        $builder = $builder->setQuery('abc', 123);

        $this->assertTrue(
            $builder instanceof Builder
        );
    }

    public function testGetQuery()
    {
        $builder = $this->builder();

        $builder->setQuery('abc', 123);

        $query = $builder->getQuery('abc');

        $this->assertEquals(
            123,
            $query
        );
    }

    public function testNewQuery()
    {
        $builder = $this->builder();

        $this->assertTrue(
            $builder->newQuery() instanceof Builder
        );
    }

    public function testPrependDatabaseNameIfCrossDatabaseQuery()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'prependDatabaseNameIfCrossDatabaseQuery');

        $builderMethod->setAccessible(true);

        $this->assertTrue(
            $builderMethod->invoke($builder, $builder) instanceof Builder
        );

        $connection = $this->connection();

        $connection->setDatabase('test');

        $newBuilder = $this->builder();

        $newBuilder->setConnection($connection);

        $newBuilder->setFrom('default');

        $newBuilder = $builderMethod->invoke($builder, $newBuilder);

        $this->assertEquals(
            array(
                "`test`.`default`"
            ),
            $newBuilder->getFrom()
        );
    }

    public function testParseSub()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'parseSub');

        $builderMethod->setAccessible(true);

        $newBuilder = $this->builder();

        $this->assertEquals(
            array(
                'SELECT *', array()
            ),
            $builderMethod->invoke($builder, $newBuilder)
        );

        $this->assertEquals(
            array(
                'abc', array()
            ),
            $builderMethod->invoke($builder, 'abc')
        );

        $this->assertEquals(
            array(
                'abc', array()
            ),
            $builderMethod->invoke($builder, new Expression('abc'))
        );

        try {
            $builderMethod->invoke($builder, new \stdClass);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(
                $e instanceof InvalidArgumentException
            );

            $this->assertEquals(
                'A subquery must be a query builder instance, a Closure, or a string.',
                $e->getMessage()
            );
        }
    }

    public function testCreateSub()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'createSub');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, function ($query) {
            return $query;
        });

        $this->assertEquals(
            array(
                'SELECT *', array()
            ),
            $result
        );
    }

    public function testToSql()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'toSql');

        $builderMethod->setAccessible(true);

        $this->assertEquals(
            'SELECT *',
            $builderMethod->invoke($builder)
        );
    }

    public function testBindingsNested()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'bindingsNested');

        $builderMethod->setAccessible(true);

        $this->assertEquals(
            array(
                '123',
                new Expression('456')
            ),
            $builderMethod->invoke($builder, array(
                '123',
                new Expression('456')
            ))
        );
    }

    public function testGetBindings()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'getBindings');

        $builderMethod->setAccessible(true);

        $builder->setQueries(
            array(
                'froms' => array(
                    'queries' => array(
                        new Expression('`dns_record`'),
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
                        new Expression('INNER JOIN (SELECT * FROM `default`.`zones` AS `zones` WHERE `zones`.`id` = ? OR `zones`.`id` = ?) AS `zones` ON `zones`.`id` = `dns_record`.`zones_id` AND (`zones`.`id` = `dns_record`.`zones_id`)'),
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
                        new Expression('AND `dns_record`.`provider_id` = ?'),
                        new Expression('AND `dns_record`.`cdn_provider_id` = ?'),
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
            array(
                127, 127, 448, 127, 127, 1, 1, 127, 127, 10, 1
            ),
            $builderMethod->invoke($builder)
        );
    }

    public function testQueriesPush()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'queriesPush');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 'abc', 123);

        $this->assertEquals(
            array(
                'wheres' => array(
                    'queries' => array(
                        'abc'
                    ),
                    'bindings' => array(
                        123
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                'abc'
            ),
            $result->getQuery('wheres.queries')
        );

        $result = $builderMethod->invoke($builder, 'abc', 123, 'columns');

        $this->assertEquals(
            array(
                'wheres' => array(
                    'queries' => array(
                        'abc'
                    ),
                    'bindings' => array(
                        123
                    )
                ),
                'columns' => array(
                    'queries' => array(
                        'abc'
                    ),
                    'bindings' => array(
                        123
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                'abc'
            ),
            $result->getQuery('columns.queries')
        );

        $this->assertTrue(
            $result instanceof Builder
        );
    }

    public function testQueryPush()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'queryPush');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 'abc');

        $this->assertEquals(
            array(
                'wheres' => array(
                    'queries' => array(
                        'abc'
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                'abc'
            ),
            $result->getQuery('wheres.queries')
        );

        $result = $builderMethod->invoke($builder, 'abc', 'columns');

        $this->assertEquals(
            array(
                'wheres' => array(
                    'queries' => array(
                        'abc'
                    )
                ),
                'columns' => array(
                    'queries' => array(
                        'abc'
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                'abc'
            ),
            $result->getQuery('columns.queries')
        );

        $this->assertTrue(
            $result instanceof Builder
        );
    }

    public function testBindingPush()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'bindingPush');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 123);

        $this->assertEquals(
            array(
                'wheres' => array(
                    'bindings' => array(
                        123
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                123
            ),
            $result->getQuery('wheres.bindings')
        );

        $result = $builderMethod->invoke($builder, 123, 'columns');

        $this->assertEquals(
            array(
                'wheres' => array(
                    'bindings' => array(
                        123
                    )
                ),
                'columns' => array(
                    'bindings' => array(
                        123
                    )
                )
            ),
            $result->getQueries()
        );

        $this->assertEquals(
            array(
                123
            ),
            $result->getQuery('columns.bindings')
        );
    }

    public function testSubQueryAsContactBacktick()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'subQueryAsContactBacktick');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 'abc');

        $this->assertEquals(
            '(abc)',
            $result
        );

        $result = $builderMethod->invoke($builder, 'abc', 'a');

        $this->assertEquals(
            '(abc) AS `a`',
            $result
        );
    }

    public function testQueryAsContactBacktick()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'queryAsContactBacktick');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 'abc');

        $this->assertEquals(
            '`abc`',
            $result
        );

        $result = $builderMethod->invoke($builder, 'abc', 'a');

        $this->assertEquals(
            '`abc` AS `a`',
            $result
        );
    }

    public function testRaw()
    {
        $raw = $this->builder()->raw('abc');

        $this->assertTrue(
            $raw instanceof Expression
        );

        $this->assertTrue(
            (string) $raw === 'abc'
        );
    }

    public function testFromRaw()
    {
        $fromRaw = $this->builder()->fromRaw('abc');

        $this->assertEquals(
            array('abc'),
            $fromRaw->getFrom()
        );
    }

    public function testSetForm()
    {
        $from = $this->builder()->setFrom('abc');

        $this->assertEquals(
            array('abc'),
            $from->getFrom()
        );
    }

    public function testFrom()
    {
        $from = $this->builder()->from('abc');

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $from->getFrom()
        );

        $from = $this->builder()->from('abc', 'a');

        $this->assertEquals(
            array(
                new Expression('`abc` AS `a`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->from($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)'),
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg', 'e');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->from($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->from($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`'),
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->from($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            function ($query) {
                $query->from('efg', 'e');
            },
            function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => function ($query) {
                $query->from('efg', 'e');
            },
            'f' => function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->from(array(
            $newBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->from('efg', 'e');

        $from = $builder->from(array(
            $newBuilder, $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->from('efg', 'e');

        $from = $builder->from(array(
            'e' => $newBuilder,
            'e' => $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );
    }

    public function testFromSub()
    {
        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->fromSub($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)'),
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg', 'e');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->fromSub($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->fromSub($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`'),
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->fromSub($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(array(
            function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(array(
            'e' => function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(array(
            function ($query) {
                $query->from('efg', 'e');
            },
            function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(array(
            'e' => function ($query) {
                $query->from('efg', 'e');
            },
            'f' => function ($query) {
                $query->from('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->fromSub(array(
            $newBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->from('efg', 'e');

        $from = $builder->fromSub(array(
            $newBuilder, $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->from('efg', 'e');

        $from = $builder->fromSub(array(
            'e' => $newBuilder,
            'e' => $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );
    }

    public function testGetFrom()
    {
        $builder = $this->builder();

        $this->assertNull($builder->getFrom());
    }

    public function testSetTable()
    {
        $from = $this->builder()->setTable('abc');

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $from->getFrom()
        );

        $from = $this->builder()->table('abc');

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $from->getFrom()
        );

        $from = $this->builder()->setTable('abc', 'a');

        $this->assertEquals(
            array(
                new Expression('`abc` AS `a`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg');

        $from = $builder->setTable($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)'),
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg', 'e');
        });

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $from = $builder->setTable($newBuilder);

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg');

        $from = $builder->setTable($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`'),
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $from = $builder->setTable($newBuilder, 'e');

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            function ($query) {
                $query->setTable('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            'e' => function ($query) {
                $query->setTable('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            function ($query) {
                $query->setTable('efg', 'e');
            },
            function ($query) {
                $query->setTable('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            'e' => function ($query) {
                $query->setTable('efg', 'e');
            },
            'f' => function ($query) {
                $query->setTable('efg', 'e');
            }
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $from = $builder->setTable(array(
            $newBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->setTable('efg', 'e');

        $from = $builder->setTable(array(
            $newBuilder, $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`)'),
                new Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $newOtherBuilder = $this->builder();

        $newOtherBuilder->setTable('efg', 'e');

        $from = $builder->setTable(array(
            'e' => $newBuilder,
            'e' => $newOtherBuilder
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            'e' => new Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );
    }

    public function testGetTable()
    {
        $builder = $this->builder();

        $this->assertNull($builder->getTable());
    }

    public function testSelectRaw()
    {
        $builder = $this->builder()->selectRaw('*');

        $this->assertEquals(
            array(
                new Expression('*')
            ),
            $builder->getQuery('columns.queries')
        );
    }

    public function testSelect()
    {
        $builder = $this->builder()->select('*');

        $this->assertEquals(
            array(
                '*'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select('abc', 'efg');

        $this->assertEquals(
            array(
                '`abc`', '`efg`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select();

        $this->assertEquals(
            array(
                '*'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(array('abc', 'efg'));

        $this->assertEquals(
            array(
                '`abc`', '`efg`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(array('abc' => 'efg'));

        $this->assertEquals(
            array(
                '`efg` AS `abc`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(
            new Expression('*')
        );

        $this->assertEquals(
            array(
                '*'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(
            new Expression('abc'),
            new Expression('efg')
        );

        $this->assertEquals(
            array(
                new Expression('abc'),
                new Expression('efg')
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(array(
            'a' => new Expression('abc'),
            'e' => new Expression('efg')
        ));

        $this->assertEquals(
            array(
                'abc AS `a`',
                'efg AS `e`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(
            function ($query) {
                $query->from('abc');
            }
        );

        $this->assertEquals(
            array(
                '(SELECT * FROM `abc`)'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(
            function ($query) {
                $query->from('abc');
            },
            function ($query) {
                $query->from('efg');
            }
        );

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `abc`)'),
                new Expression('(SELECT * FROM `efg`)')
            ),
            $builder->getQuery('columns.queries')
        );
    }

    public function testSelectSub()
    {
        $builder = $this->builder()->selectSub('*');

        $this->assertEquals(
            array(
                '(*)'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub('abc', 'efg');

        $this->assertEquals(
            array(
                '(abc) AS `efg`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(array('*'));

        $this->assertEquals(
            array(
                '*'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(array('abc', 'efg'));

        $this->assertEquals(
            array(
                '`abc`', '`efg`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(array('abc' => 'efg'));

        $this->assertEquals(
            array(
                '`efg` AS `abc`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(
            new Expression('*')
        );

        $this->assertEquals(
            array(
                new Expression('(*)')
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(
            new Expression('abc'),
            new Expression('efg')
        );

        $this->assertEquals(
            array(
                new Expression('(abc) AS efg')
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(array(
            'a' => new Expression('abc'),
            'e' => new Expression('efg')
        ));

        $this->assertEquals(
            array(
                'abc AS `a`',
                'efg AS `e`'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(
            function ($query) {
                $query->from('abc');
            }
        );

        $this->assertEquals(
            array(
                '(SELECT * FROM `abc`)'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->selectSub(
            function ($query) {
                $query->from('abc');
            }
        );

        $this->assertEquals(
            array(
                new Expression('(SELECT * FROM `abc`)')
            ),
            $builder->getQuery('columns.queries')
        );
    }

    public function testInvalidOperatorAndValue()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'invalidOperatorAndValue');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, '=', 'abc');

        $this->assertFalse($result);

        $result = $builderMethod->invoke($builder, '=', null);

        $this->assertFalse($result);

        $result = $builderMethod->invoke($builder, '>', 'abc');

        $this->assertFalse($result);

        $result = $builderMethod->invoke($builder, '>', null);

        $this->assertTrue($result);
    }

    public function testPrepareValueAndOperator()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'prepareValueAndOperator');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 'abc', '=');

        $this->assertEquals(
            array(
                'abc', '='
            ),
            $result
        );

        $result = $builderMethod->invoke($builder, null, 'abc', true);

        $this->assertEquals(
            array(
                'abc', '='
            ),
            $result
        );

        try {
            $builderMethod->invoke($builder, '))', 'abc', true);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(
                $e instanceof InvalidArgumentException
            );

            $this->assertEquals(
                'Illegal operator and value combination.',
                $e->getMessage()
            );
        }
    }

    public function testInvalidOperator()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'invalidOperator');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, 123);

        $this->assertTrue($result);

        $result = $builderMethod->invoke($builder, new \stdClass);

        $this->assertTrue($result);

        $result = $builderMethod->invoke($builder, '=');

        $this->assertFalse($result);

        $result = $builderMethod->invoke($builder, '!');

        $this->assertTrue($result);
    }

    public function testArrayNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builderMethod = new \ReflectionMethod($builder, 'arrayNested');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, array(array('abc', 123)), 'and');

        $this->assertTrue($result instanceof Builder);

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $builder->getFrom()
        );

        $this->assertEquals(
            array('AND (`abc` = ?)'),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builderMethod = new \ReflectionMethod($builder, 'nested');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, function ($query) {
            $query->where('abc', 123);
        });

        $this->assertTrue($result instanceof Builder);

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $builder->getFrom()
        );

        $this->assertEquals(
            array('AND (`abc` = ?)'),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testForNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builderMethod = new \ReflectionMethod($builder, 'forNested');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder);

        $this->assertTrue($result instanceof Builder);

        $this->assertEquals(
            array(
                new Expression('`abc`')
            ),
            $builder->getFrom()
        );
    }

    public function testAddNestedQuery()
    {
        $builder = $this->builder();

        $nested = $this->builder();

        $nested->where('abc', 123);

        $builderMethod = new \ReflectionMethod($builder, 'addNestedQuery');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, $nested);

        $this->assertTrue($result instanceof Builder);

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $result->getQuery('wheres')
        );
    }

    public function testFirstJoinReplace()
    {
        $builder = $this->builder();

        $result = $builder->firstJoinReplace('');

        $this->assertEquals('', $result);

        $result = $builder->firstJoinReplace('`abc` = ?');

        $this->assertEquals('`abc` = ?', $result);

        $result = $builder->firstJoinReplace('AND `abc` = ?');

        $this->assertEquals('`abc` = ?', $result);

        $result = $builder->firstJoinReplace('OR `abc` = ?');

        $this->assertEquals('`abc` = ?', $result);

        $result = $builder->firstJoinReplace(', `abc` = ?');

        $this->assertEquals(', `abc` = ?', $result);
    }

    public function testNestedArrayArguments()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'nestedArrayArguments');

        $builderMethod->setAccessible(true);

        $this->assertEquals(
            array(
                'abc', null, null, 'AND'
            ),
            $builderMethod->invoke($builder, array(
                'abc'
            ), 'AND')
        );
    }

    public function testWhereNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builderMethod = new \ReflectionMethod($builder, 'whereNested');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder, function ($query) {
            $query->where('abc', 123);
        });

        $this->assertTrue($result instanceof Builder);

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $result->getQuery('wheres')
        );
    }

    public function testArrayWhereNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $nested = $this->builder();

        $builder->arrayWhereNested($nested, 1, array('abc', 123), 'and');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $nested->getQuery('wheres')
        );
    }

    public function testWhereRaw()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereRaw('abc = ?', array(123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('and abc = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereRaw()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereRaw('abc = ?', array(123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('or abc = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->where('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->where(function ($query) {
            $query->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->where('abc', 123);

        $builder->where($newBuilder, '=', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND (SELECT * WHERE `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123,
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->where('abc', 123);

        $builder->where($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND EXISTS (SELECT * WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->where('abc', '>', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` > ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->where('abc', '>', function ($query) {
            $query->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` > (SELECT * WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->where(array(
            array('abc', '>', 123),
            array('abc', 123),
            array(
                function ($query) {
                    $query->where('abc', '<', 123);
                }
            ),
            array(
                'abc', '<', function ($query) {
                    $query->where('abc', 123);
                }
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` > ? AND `abc` = ? AND (`abc` < ?) AND `abc` < (SELECT * WHERE `abc` = ?))'
                ),
                'bindings' => array(
                    123,
                    123,
                    123,
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhere('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhere(function ($query) {
            $query->orWhere('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhere('abc', 123);

        $builder->orWhere($newBuilder, '=', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR (SELECT * WHERE `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123,
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhere('abc', 123);

        $builder->orWhere($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR EXISTS (SELECT * WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhere('abc', '>', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` > ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhere('abc', '>', function ($query) {
            $query->orWhere('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` > (SELECT * WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhere(array(
            array('abc', '>', 123),
            array('abc', 123),
            array(
                function ($query) {
                    $query->orWhere('abc', '<', 123);
                }
            ),
            array(
                'abc', '<', function ($query) {
                    $query->orWhere('abc', 123);
                }
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` > ? OR `abc` = ? OR (`abc` < ?) OR `abc` < (SELECT * WHERE `abc` = ?))'
                ),
                'bindings' => array(
                    123,
                    123,
                    123,
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereSub()
    {
        $builder = $this->builder();

        $builder->whereSub('abc', function ($query) {
            $query->from('efg')->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = (SELECT * FROM `efg` WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->whereSub('abc', '>', function ($query) {
            $query->from('efg')->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` > (SELECT * FROM `efg` WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereSub()
    {
        $builder = $this->builder();

        $builder->orWhereSub('abc', function ($query) {
            $query->from('efg')->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = (SELECT * FROM `efg` WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->orWhereSub('abc', '>', function ($query) {
            $query->from('efg')->where('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` > (SELECT * FROM `efg` WHERE `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereNull()
    {
        $builder = $this->builder();

        $builder->whereNull('abc');

        $this->assertEquals(
            array(
                new Expression('AND `abc` IS NULL')
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNull(function ($query) {
            $query->whereNull('abc');
        });

        $this->assertEquals(
            array(
                'AND (`abc` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNull(
            array(
                array(
                    function ($query) {
                        $query->whereNull('abc');
                    }
                ),
                array(
                    function ($query) {
                        $query->whereNull('abc');
                    }
                )
            )
        );

        $this->assertEquals(
            array(
                'AND ((`abc` IS NULL) AND (`abc` IS NULL))'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNull(
            array(
                array('abc')
            )
        );

        $this->assertEquals(
            array(
                'AND (`abc` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNull(
            array(
                array('abc'),
                array('abce')
            )
        );

        $this->assertEquals(
            array(
                'AND (`abc` IS NULL AND `abce` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testOrWhereNull()
    {
        $builder = $this->builder();

        $builder->orWhereNull('abc');

        $this->assertEquals(
            array(
                new Expression('OR `abc` IS NULL')
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNull(function ($query) {
            $query->orWhereNull('abc');
        });

        $this->assertEquals(
            array(
                'OR (`abc` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNull(
            array(
                array(
                    function ($query) {
                        $query->orWhereNull('abc');
                    }
                ),
                array(
                    function ($query) {
                        $query->orWhereNull('abc');
                    }
                )
            )
        );

        $this->assertEquals(
            array(
                'OR ((`abc` IS NULL) OR (`abc` IS NULL))'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNull(
            array(
                array('abc')
            )
        );

        $this->assertEquals(
            array(
                'OR (`abc` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNull(
            array(
                array('abc'),
                array('abce')
            )
        );

        $this->assertEquals(
            array(
                'OR (`abc` IS NULL OR `abce` IS NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testWhereNotNull()
    {
        $builder = $this->builder();

        $builder->whereNotNull('abc');

        $this->assertEquals(
            array(
                new Expression('AND `abc` IS NOT NULL')
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNotNull(function ($query) {
            $query->whereNotNull('abc');
        });

        $this->assertEquals(
            array(
                'AND (`abc` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNotNull(
            array(
                array(
                    function ($query) {
                        $query->whereNotNull('abc');
                    }
                ),
                array(
                    function ($query) {
                        $query->whereNotNull('abc');
                    }
                )
            )
        );

        $this->assertEquals(
            array(
                'AND ((`abc` IS NOT NULL) AND (`abc` IS NOT NULL))'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNotNull(
            array(
                array('abc')
            )
        );

        $this->assertEquals(
            array(
                'AND (`abc` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->whereNotNull(
            array(
                array('abc'),
                array('abce')
            )
        );

        $this->assertEquals(
            array(
                'AND (`abc` IS NOT NULL AND `abce` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testOrWhereNotNull()
    {
        $builder = $this->builder();

        $builder->orWhereNotNull('abc');

        $this->assertEquals(
            array(
                new Expression('OR `abc` IS NOT NULL')
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNotNull(function ($query) {
            $query->orWhereNotNull('abc');
        });

        $this->assertEquals(
            array(
                'OR (`abc` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNotNull(
            array(
                array(
                    function ($query) {
                        $query->orWhereNotNull('abc');
                    }
                ),
                array(
                    function ($query) {
                        $query->orWhereNotNull('abc');
                    }
                )
            )
        );

        $this->assertEquals(
            array(
                'OR ((`abc` IS NOT NULL) OR (`abc` IS NOT NULL))'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNotNull(
            array(
                array('abc')
            )
        );

        $this->assertEquals(
            array(
                'OR (`abc` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orWhereNotNull(
            array(
                array('abc'),
                array('abce')
            )
        );

        $this->assertEquals(
            array(
                'OR (`abc` IS NOT NULL OR `abce` IS NOT NULL)'
            ),
            $builder->getQuery('wheres.queries')
        );
    }

    public function testWhereIn()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereIn('efg', array(123, 456));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `efg` IN (?, ?)')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereIn(
            array(
                array('efg', array(123, 456)),
                array('efg', array(123, 456))
            )
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`efg` IN (?, ?) AND `efg` IN (?, ?))'
                ),
                'bindings' => array(123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereIn('abc', function ($query) {
            $query->whereIn('abc', array(123, 456));
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` IN (SELECT * WHERE `abc` IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereIn('abc', array(123, 456));

        $builder->whereIn('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` IN (SELECT * WHERE `abc` IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereIn('abc', array(123, 456));

        $builder->whereIn(array(
            array('abc', function ($query) {
                $query->whereIn('abc', array(123, 456));
            }),
            array('abc', 'in', function ($query) {
                $query->whereIn('abc', array(123, 456));
            }),
            array('abc', $newBuilder)
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` IN (SELECT * WHERE `abc` IN (?, ?)) AND `abc` IN (SELECT * WHERE `abc` IN (?, ?)) AND `abc` IN (SELECT * WHERE `abc` IN (?, ?)))'
                ),
                'bindings' => array(123, 456, 123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereIn()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereIn('efg', array(123, 456));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `efg` IN (?, ?)')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereIn(
            array(
                array('efg', array(123, 456)),
                array('efg', array(123, 456))
            )
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`efg` IN (?, ?) OR `efg` IN (?, ?))'
                ),
                'bindings' => array(123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereIn('abc', function ($query) {
            $query->orWhereIn('abc', array(123, 456));
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` IN (SELECT * WHERE `abc` IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhereIn('abc', array(123, 456));

        $builder->orWhereIn('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` IN (SELECT * WHERE `abc` IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereIn('abc', array(123, 456));

        $builder->orWhereIn(array(
            array('abc', 'in', function ($query) {
                $query->orWhereIn('abc', array(123, 456));
            }),
            array('abc', 'in', function ($query) {
                $query->orWhereIn('abc', array(123, 456));
            }),
            array('abc', $newBuilder)
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` IN (SELECT * WHERE `abc` IN (?, ?)) OR `abc` IN (SELECT * WHERE `abc` IN (?, ?)) OR `abc` IN (SELECT * WHERE `abc` IN (?, ?)))'
                ),
                'bindings' => array(123, 456, 123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereNotIn()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereNotIn('efg', array(123, 456));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `efg` NOT IN (?, ?)')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereNotIn(
            array(
                array('efg', array(123, 456)),
                array('efg', array(123, 456))
            )
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`efg` NOT IN (?, ?) AND `efg` NOT IN (?, ?))'
                ),
                'bindings' => array(123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereNotIn('abc', function ($query) {
            $query->whereNotIn('abc', array(123, 456));
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereNotIn('abc', array(123, 456));

        $builder->whereNotIn('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereNotIn('abc', array(123, 456));

        $builder->whereNotIn(array(
            array('abc', function ($query) {
                $query->whereNotIn('abc', array(123, 456));
            }),
            array('abc', 'in', function ($query) {
                $query->whereNotIn('abc', array(123, 456));
            }),
            array('abc', $newBuilder)
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)) AND `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)) AND `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)))'
                ),
                'bindings' => array(123, 456, 123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereNotIn()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereNotIn('efg', array(123, 456));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `efg` NOT IN (?, ?)')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereNotIn(
            array(
                array('efg', array(123, 456)),
                array('efg', array(123, 456))
            )
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`efg` NOT IN (?, ?) OR `efg` NOT IN (?, ?))'
                ),
                'bindings' => array(123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereNotIn('abc', function ($query) {
            $query->orWhereNotIn('abc', array(123, 456));
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhereNotIn('abc', array(123, 456));

        $builder->orWhereNotIn('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?))')
                ),
                'bindings' => array(123, 456)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhereNotIn('abc', array(123, 456));

        $builder->orWhereNotIn(array(
            array('abc', 'in', function ($query) {
                $query->orWhereNotIn('abc', array(123, 456));
            }),
            array('abc', 'in', function ($query) {
                $query->orWhereNotIn('abc', array(123, 456));
            }),
            array('abc', $newBuilder)
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)) OR `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)) OR `abc` NOT IN (SELECT * WHERE `abc` NOT IN (?, ?)))'
                ),
                'bindings' => array(123, 456, 123, 456, 123, 456)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereLike()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereLike('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` LIKE ?')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereLike(function ($query) {
            $query->from('abc')->where('abc', 123);
        }, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND (SELECT * FROM `abc` WHERE `abc` = ?) LIKE ?')
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->whereLike('abc', 123);

        $builder->whereLike($newBuilder, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND (SELECT * WHERE `abc` LIKE ?) LIKE ?')
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereLike()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereLike('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` LIKE ?')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereLike(function ($query) {
            $query->from('abc')->where('abc', 123);
        }, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR (SELECT * FROM `abc` WHERE `abc` = ?) LIKE ?')
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orWhereLike('abc', 123);

        $builder->orWhereLike($newBuilder, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR (SELECT * WHERE `abc` LIKE ?) LIKE ?')
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereExists()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereExists(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->whereExists($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereNotExists()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereNotExists(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND NOT EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->whereNotExists($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND NOT EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereExists()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereExists(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orWhereExists($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereNotExists()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereNotExists(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR NOT EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orWhereNotExists($newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR NOT EXISTS (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereBetween()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereBetween('efg', array(0, 10));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `efg` BETWEEN ? AND ?')
                ),
                'bindings' => array(
                    0, 10
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereBetween()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereBetween('efg', array(0, 10));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `efg` BETWEEN ? AND ?')
                ),
                'bindings' => array(
                    0, 10
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereNotBetween()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereNotBetween('efg', array(0, 10));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `efg` NOT BETWEEN ? AND ?')
                ),
                'bindings' => array(
                    0, 10
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereNotBetween()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereNotBetween('efg', array(0, 10));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `efg` NOT BETWEEN ? AND ?')
                ),
                'bindings' => array(
                    0, 10
                ),
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereAny()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereAny('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ANY (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->whereAny('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ANY (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereAny()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereAny('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ANY (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orWhereAny('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ANY (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereAll()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereAll('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ALL (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->whereAll('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ALL (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereAll()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereAll('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ALL (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orWhereAll('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ALL (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testWhereSome()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->whereSome('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = SOME (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->whereSome('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = SOME (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testOrWhereSome()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orWhereSome('abc', function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = SOME (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orWhereSome('abc', $newBuilder);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = SOME (SELECT * FROM `efg` WHERE `hij` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('wheres')
        );
    }

    public function testGroupByNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->groupByNested(function ($query) {
            $query->groupBy('efg');
        }, '');

        $this->assertEquals(
            array(
                '`efg` ASC'
            ),
            $builder->getQuery('groups.queries')
        );
    }

    public function testArrayGroupByNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $nested = $this->builder();

        $builder->arrayGroupByNested($nested, 1, array('abc', 'desc'), '');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` DESC')
                )
            ),
            $nested->getQuery('groups')
        );
    }

    public function testGroupByRaw()
    {
        $builder = $this->builder();

        $builder->groupByRaw('abc desc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('abc desc')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupByRaw('(SELECT id FROM abc WHERE id IN (?, ?)) desc', array(1, 2));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT id FROM abc WHERE id IN (?, ?)) desc')
                ),
                'bindings' => array(1, 2)
            ),
            $builder->getQuery('groups')
        );
    }

    public function testGroupBy()
    {
        $builder = $this->builder();

        $builder->groupBy('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` ASC')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupBy('abc', 'desc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` DESC')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupBy(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupBy(function ($query) {
            $query->from('efg')->where('hij', 123);
        }, 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->groupBy($newBuilder->from('efg')->where('hij', 123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->groupBy($newBuilder->from('efg')->where('hij', 123), 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );
    }

    public function testGroupBySub()
    {
        $builder = $this->builder();

        $builder->groupBySub('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(abc) ASC')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupBySub(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->groupBySub(function ($query) {
            $query->from('efg')->where('hij', 123);
        }, 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->groupBySub($newBuilder->from('efg')->where('hij', 123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->groupBySub($newBuilder->from('efg')->where('hij', 123), 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('groups')
        );
    }

    public function testSortByAsc()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'sortByAsc');

        $builderMethod->setAccessible(true);

        $this->assertEquals(
            array(
                array('abc', 'ASC'),
                array('efg', 'ASC')
            ),
            $builderMethod->invoke($builder, array('abc', 'efg'))
        );
    }

    public function testSortByDesc()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'sortByDesc');

        $builderMethod->setAccessible(true);

        $this->assertEquals(
            array(
                array('abc', 'DESC'),
                array('efg', 'DESC')
            ),
            $builderMethod->invoke($builder, array('abc', 'efg'))
        );
    }

    public function testGroupByAsc()
    {
        $builder = $this->builder();

        $builder->from('efg');

        $builder->groupByAsc('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` ASC')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->groupByAsc(array(
            array('abc')
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` ASC'
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->groupByAsc(array(
            array('abc'),
            array('hij'),
            array(
                function ($query) {
                    $query->from('efg')->where('hij', 123);
                }
            ),
            array(
                $newBuilder
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` ASC, `hij` ASC, (SELECT * FROM `efg` WHERE `hij` = ?) ASC, (SELECT * FROM `efg` WHERE `hij` = ?) ASC'
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('groups')
        );
    }

    public function testGroupByDesc()
    {
        $builder = $this->builder();

        $builder->from('efg');

        $builder->groupByDesc('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` DESC')
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->groupByDesc(array(
            array('abc')
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` DESC'
                )
            ),
            $builder->getQuery('groups')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->groupByDesc(array(
            array('abc'),
            array('hij'),
            array(
                function ($query) {
                    $query->from('efg')->where('hij', 123);
                }
            ),
            array(
                $newBuilder
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` DESC, `hij` DESC, (SELECT * FROM `efg` WHERE `hij` = ?) DESC, (SELECT * FROM `efg` WHERE `hij` = ?) DESC'
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('groups')
        );
    }

    public function testHavingNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->havingNested(function ($query) {
            $query->having('efg', 1);
        }, '');

        $this->assertEquals(
            array(
                'queries' => array(
                    ' (`efg` = ?)'
                ),
                'bindings' => array(1),
            ),
            $builder->getQuery('havings')
        );
    }

    public function testArrayHavingNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $nested = $this->builder();

        $builder->arrayHavingNested($nested, 1, array('abc', 123), 'and');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $nested->getQuery('havings')
        );
    }

    public function testHavingRaw()
    {
        $builder = $this->builder();

        $builder->havingRaw('`abc` = ?');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('and `abc` = ?')
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->havingRaw('`abc` = ?', array(1));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('and `abc` = ?')
                ),
                'bindings' => array(1)
            ),
            $builder->getQuery('havings')
        );
    }

    public function testOrHavingRaw()
    {
        $builder = $this->builder();

        $builder->orHavingRaw('`abc` = ?');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('or `abc` = ?')
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->orHavingRaw('`abc` = ?', array(1));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('or `abc` = ?')
                ),
                'bindings' => array(1)
            ),
            $builder->getQuery('havings')
        );
    }

    public function testHaving()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->having('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->having(function ($query) {
            $query->having('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->having('abc', 123);

        $builder->having($newBuilder, '=', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND (SELECT * HAVING `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123,
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('abc');

        $newBuilder->having('abc', 123);

        $builder->having($newBuilder, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND (SELECT * FROM `abc` HAVING `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123, 123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->having('abc', '>', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` > ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->having('abc', '>', function ($query) {
            $query->having('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('AND `abc` > (SELECT * HAVING `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->having(array(
            array('abc', '>', 123),
            array('abc', 123),
            array(
                function ($query) {
                    $query->having('abc', '<', 123);
                }
            ),
            array(
                'abc', '<', function ($query) {
                    $query->having('abc', 123);
                }
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'AND (`abc` > ? AND `abc` = ? AND (`abc` < ?) AND `abc` < (SELECT * HAVING `abc` = ?))'
                ),
                'bindings' => array(
                    123,
                    123,
                    123,
                    123
                )
            ),
            $builder->getQuery('havings')
        );
    }

    public function testOrHaving()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orHaving('abc', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` = ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orHaving(function ($query) {
            $query->orHaving('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` = ?)'
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->orHaving('abc', 123);

        $builder->orHaving($newBuilder, '=', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR (SELECT * HAVING `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123,
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $newBuilder = $this->builder();

        $newBuilder->from('abc');

        $newBuilder->orHaving('abc', 123);

        $builder->orHaving($newBuilder, 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR (SELECT * FROM `abc` HAVING `abc` = ?) = ?')
                ),
                'bindings' => array(
                    123, 123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orHaving('abc', '>', 123);

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` > ?')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orHaving('abc', '>', function ($query) {
            $query->orHaving('abc', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('OR `abc` > (SELECT * HAVING `abc` = ?)')
                ),
                'bindings' => array(
                    123
                )
            ),
            $builder->getQuery('havings')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->orHaving(array(
            array('abc', '>', 123),
            array('abc', 123),
            array(
                function ($query) {
                    $query->orHaving('abc', '<', 123);
                }
            ),
            array(
                'abc', '<', function ($query) {
                    $query->orHaving('abc', 123);
                }
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    'OR (`abc` > ? OR `abc` = ? OR (`abc` < ?) OR `abc` < (SELECT * HAVING `abc` = ?))'
                ),
                'bindings' => array(
                    123,
                    123,
                    123,
                    123
                )
            ),
            $builder->getQuery('havings')
        );
    }

    public function testOrderByNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->orderByNested(function ($query) {
            $query->orderBy('efg');
        }, '');

        $this->assertEquals(
            array(
                '`efg` ASC'
            ),
            $builder->getQuery('orders.queries')
        );
    }

    public function testArrayOrderByNested()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $nested = $this->builder();

        $builder->arrayOrderByNested($nested, 1, array('abc', 'desc'), '');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` DESC')
                )
            ),
            $nested->getQuery('orders')
        );
    }

    public function testOrderByRaw()
    {
        $builder = $this->builder();

        $builder->orderByRaw('abc desc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('abc desc')
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderByRaw('(SELECT id FROM abc WHERE id IN (?, ?)) desc', array(1, 2));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT id FROM abc WHERE id IN (?, ?)) desc')
                ),
                'bindings' => array(1, 2)
            ),
            $builder->getQuery('orders')
        );
    }

    public function testOrderBy()
    {
        $builder = $this->builder();

        $builder->orderBy('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` ASC')
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderBy('abc', 'desc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('`abc` DESC')
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderBy(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderBy(function ($query) {
            $query->from('efg')->where('hij', 123);
        }, 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->orderBy($newBuilder->from('efg')->where('hij', 123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->orderBy($newBuilder->from('efg')->where('hij', 123), 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );
    }

    public function testOrderBySub()
    {
        $builder = $this->builder();

        $builder->orderBySub('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(abc) ASC')
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderBySub(function ($query) {
            $query->from('efg')->where('hij', 123);
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->orderBySub(function ($query) {
            $query->from('efg')->where('hij', 123);
        }, 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->orderBySub($newBuilder->from('efg')->where('hij', 123));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) ASC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $newBuilder = $this->builder();

        $builder->orderBySub($newBuilder->from('efg')->where('hij', 123), 'DESC');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('(SELECT * FROM `efg` WHERE `hij` = ?) DESC')
                ),
                'bindings' => array(123)
            ),
            $builder->getQuery('orders')
        );
    }

    public function testOrderByAsc()
    {
        $builder = $this->builder();

        $builder->from('efg');

        $builder->orderByAsc('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` ASC'
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orderByAsc(array(
            array('abc')
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` ASC'
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orderByAsc(array(
            array('abc'),
            array('hij'),
            array(
                function ($query) {
                    $query->from('efg')->where('hij', 123);
                }
            ),
            array(
                $newBuilder
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` ASC, `hij` ASC, (SELECT * FROM `efg` WHERE `hij` = ?) ASC, (SELECT * FROM `efg` WHERE `hij` = ?) ASC'
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('orders')
        );
    }

    public function testOrderByDesc()
    {
        $builder = $this->builder();

        $builder->from('efg');

        $builder->orderByDesc('abc');

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` DESC'
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $builder->orderByDesc(array(
            array('abc')
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` DESC'
                )
            ),
            $builder->getQuery('orders')
        );

        $builder = $this->builder();

        $builder->from('efg');

        $newBuilder = $this->builder();

        $newBuilder->from('efg')->where('hij', 123);

        $builder->orderByDesc(array(
            array('abc'),
            array('hij'),
            array(
                function ($query) {
                    $query->from('efg')->where('hij', 123);
                }
            ),
            array(
                $newBuilder
            )
        ));

        $this->assertEquals(
            array(
                'queries' => array(
                    '`abc` DESC, `hij` DESC, (SELECT * FROM `efg` WHERE `hij` = ?) DESC, (SELECT * FROM `efg` WHERE `hij` = ?) DESC'
                ),
                'bindings' => array(123, 123)
            ),
            $builder->getQuery('orders')
        );
    }

    public function testForSubQuery()
    {
        $builder = $this->builder();

        $builderMethod = new \ReflectionMethod($builder, 'forSubQuery');

        $builderMethod->setAccessible(true);

        $result = $builderMethod->invoke($builder);

        $this->assertTrue(
            $result instanceof Builder
        );
    }

    private function dbResult()
    {
        $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        return call_user_func(array($this, $createMock), 'Wilkques\Database\Connections\ResultInterface');
    }

    private function resultConnect($isFirst = true)
    {
        $connection = $this->connection();

        $result = $this->dbResult();

        $method = $isFirst ? 'fetch' : 'fetchAll';

        $result->expects($this->once())->method($method)->willReturn(array());

        $connection->expects($this->any())->method('exec')->willReturn($result);

        return $connection;
    }

    public function testFrist()
    {
        $builder = $this->builder();

        $connection = $this->resultConnect();

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->first();

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT * FROM `abc` LIMIT 1',
            $builder->toSql()
        );
    }

    public function testFind()
    {
        $builder = $this->builder();

        $connection = $this->resultConnect();

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->find(1);

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT * FROM `abc` WHERE `id` = ? LIMIT 1',
            $builder->toSql()
        );

        $builder = $this->builder();

        $connection = $this->resultConnect();

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->find(1, 'uid');

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT * FROM `abc` WHERE `uid` = ? LIMIT 1',
            $builder->toSql()
        );

        $builder = $this->builder();

        $connection = $this->resultConnect();

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->find(1, 'uid', array('efg', 'hij'));

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT `efg`, `hij` FROM `abc` WHERE `uid` = ? LIMIT 1',
            $builder->toSql()
        );
    }

    public function testGet()
    {
        $builder = $this->builder();

        $connection = $this->resultConnect(false);

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->get();

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT * FROM `abc`',
            $builder->toSql()
        );
    }

    private function resultConnectForInsertAndUpdate()
    {
        $connection = $this->connection();

        $result = $this->dbResult();

        $result->expects($this->once())->method('rowCount')->willReturn(1);

        $connection->expects($this->any())->method('exec')
            ->willReturnCallback(function ($query, $bindings) use ($connection, $result) {
                $connection->setQueryLog(compact('query', 'bindings'));

                return $result;
            });

        return $connection;
    }

    public function testUpdate()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->where('id', 1);

        $builder->update(array('efg' => 1));

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `efg` = ? WHERE `id` = ?',
                'bindings' => array(1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `efg` = 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->update(array('efg' => 1));

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `efg` = ? WHERE `id` = ?',
                'bindings' => array(1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `efg` = 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->update(
            array('efg' => function ($query) {
                $query->from('wxy')->select('id');
            })
        );

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `efg` = (SELECT `id` FROM `wxy`) WHERE `id` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `efg` = (SELECT `id` FROM `wxy`) WHERE `id` = 1',
            $builder->getLastParseQuery()
        );
    }

    public function testIncrement()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->increment('seq');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `seq` = `seq` + ? WHERE `id` = ?',
                'bindings' => array(1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `seq` = `seq` + 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->increment('seq', 1, array('efg' => 1));

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `efg` = ?, `seq` = `seq` + ? WHERE `id` = ?',
                'bindings' => array(1, 1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `efg` = 1, `seq` = `seq` + 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );
    }

    public function testDecrement()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->decrement('seq');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `seq` = `seq` - ? WHERE `id` = ?',
                'bindings' => array(1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `seq` = `seq` - 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->decrement('seq', 1, array('efg' => 1));

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `efg` = ?, `seq` = `seq` - ? WHERE `id` = ?',
                'bindings' => array(1, 1, 1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'UPDATE `abc` SET `efg` = 1, `seq` = `seq` - 1 WHERE `id` = 1',
            $builder->getLastParseQuery()
        );
    }

    public function testInsert()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->insert(array('efg' => 1));

        $this->assertEquals(
            array(
                'query' => 'INSERT INTO `abc` (`efg`) VALUES (?)',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'INSERT INTO `abc` (`efg`) VALUES (1)',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->insert(
            array(
                array('efg' => 1),
                array('efg' => 2),
            )
        );

        $this->assertEquals(
            array(
                'query' => 'INSERT INTO `abc` (`efg`) VALUES (?), (?)',
                'bindings' => array(1, 2),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'INSERT INTO `abc` (`efg`) VALUES (1), (2)',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->insert();

        $this->assertEquals(
            array(
                'query' => 'INSERT INTO `abc` DEFAULT VALUES',
                'bindings' => array(),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'INSERT INTO `abc` DEFAULT VALUES',
            $builder->getLastParseQuery()
        );
    }

    public function testInsertSub()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->insertSub(array('efg'), function ($query) {
            $query->from('wxy')->select('klm')->where('opq', 1);
        });

        $this->assertEquals(
            array(
                'query' => 'INSERT INTO `abc` (`efg`) SELECT `klm` FROM `wxy` WHERE `opq` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'INSERT INTO `abc` (`efg`) SELECT `klm` FROM `wxy` WHERE `opq` = 1',
            $builder->getLastParseQuery()
        );
    }

    public function testDelete()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->delete();

        $this->assertEquals(
            array(
                'query' => 'DELETE FROM `abc` WHERE `id` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'DELETE FROM `abc` WHERE `id` = 1',
            $builder->getLastParseQuery()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->where('id', 1);

        $builder->delete();

        $this->assertEquals(
            array(
                'query' => 'DELETE FROM `abc` WHERE `id` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'DELETE FROM `abc` WHERE `id` = 1',
            $builder->getLastParseQuery()
        );
    }

    public function testReStore()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->reStore();

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `deleted_at` = NULL WHERE `id` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->reStore('delete_time');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `delete_time` = NULL WHERE `id` = ?',
                'bindings' => array(1),
            ),
            $builder->getLastQueryLog()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->reStore('delete_time', '');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `delete_time` = ? WHERE `id` = ?',
                'bindings' => array('', 1),
            ),
            $builder->getLastQueryLog()
        );
    }

    public function testSoftDelete()
    {
        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->softDelete();

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `deleted_at` = ? WHERE `id` = ?',
                'bindings' => array(
                    date('Y-m-d H:i:s'),
                    1,
                ),
            ),
            $builder->getLastQueryLog()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->softDelete('delete_time');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `delete_time` = ? WHERE `id` = ?',
                'bindings' => array(
                    date('Y-m-d H:i:s'),
                    1,
                ),
            ),
            $builder->getLastQueryLog()
        );

        $builder = $this->builder();

        $connection = $this->resultConnectForInsertAndUpdate();

        $builder->setConnection($connection);

        $builder->enableQueryLog();

        $builder->from('abc');

        $builder->find(1);

        $builder->softDelete('delete_time', 'm-d-Y H:i:s');

        $this->assertEquals(
            array(
                'query' => 'UPDATE `abc` SET `delete_time` = ? WHERE `id` = ?',
                'bindings' => array(
                    date('m-d-Y H:i:s'),
                    1,
                ),
            ),
            $builder->getLastQueryLog()
        );
    }

    public function testNewJoinClause()
    {
        $builder = $this->builder();

        $this->assertTrue(
            $builder->newJoinClause($builder, 'inner', 'abc') instanceof JoinClause
        );
    }

    public function testJoin()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->join('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg ON efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->join('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->join('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testJoinWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->joinWhere('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg WHERE efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->joinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->joinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testJoinSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->joinSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->joinSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testJoinWhereSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->joinWhereSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->joinWhereSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'INNER JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testLeftJoin()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoin('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg ON efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testLeftJoinWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoinWhere('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg WHERE efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testLeftJoinSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoinSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->leftJoinSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testLeftJoinWhereSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->leftJoinWhereSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->leftJoinWhereSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'LEFT JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testRightJoin()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoin('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg ON efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testRightJoinWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoinWhere('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg WHERE efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testRightJoinSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoinSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->rightJoinSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testRightJoinWhereSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->rightJoinWhereSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->rightJoinWhereSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'RIGHT JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testCrossJoin()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoin('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg ON efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testCrossJoinWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoinWhere('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg WHERE efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testCrossJoinSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoinSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->crossJoinSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testCrossJoinWhereSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->crossJoinWhereSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->crossJoinWhereSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'CROSS JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testFullOuterJoin()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoin('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg ON efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoin('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testFullOuterJoinWhere()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoinWhere('efg', 'efg.abc_id', 'abc.id');

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg WHERE efg.abc_id = abc.id'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoinWhere('efg', function ($join) {
            $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id')->on('efg.abc_id', 'abc.id');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN efg WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id` AND `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testFullOuterJoinSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoinSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->fullOuterJoinSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN (SELECT * FROM `efg`) AS `efg` ON `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testFullOuterJoinWhereSub()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->fullOuterJoinWhereSub(
            function ($query) {
                $query->from('efg');
            },
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $joinBuilder = $this->builder();

        $joinBuilder->from('efg');

        $builder->fullOuterJoinWhereSub(
            $joinBuilder,
            'efg',
            function ($join) {
                $join->on('efg.abc_id', 'abc.id')->orOn('efg.abc_id', 'abc.id');
            }
        );

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(
                        'FULL OUTER JOIN (SELECT * FROM `efg`) AS `efg` WHERE `efg`.`abc_id` = `abc`.`id` OR `efg`.`abc_id` = `abc`.`id`'
                    )
                )
            ),
            $builder->getQuery('joins')
        );
    }

    public function testLimit()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->limit(1);

        $this->assertEquals(
            array(
                'queries' => array(
                    '?'
                ),
                'bindings' => array(
                    1
                )
            ),
            $builder->getQuery('limits')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->limit(1, 2);

        $this->assertEquals(
            array(
                'queries' => array(
                    '?', '?'
                ),
                'bindings' => array(
                    1, 2
                )
            ),
            $builder->getQuery('limits')
        );

        $builder = $this->builder();

        $builder->from('abc');

        $builder->limit($builder->raw(1));

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression(1)
                ),
                'bindings' => array()
            ),
            $builder->getQuery('limits')
        );
    }

    public function testOffset()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->offset(1);

        $this->assertEquals(
            array(
                'queries' => '?',
                'bindings' => 1
            ),
            $builder->getQuery('offset')
        );
    }

    public function testCurrentPage()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->currentPage(1);

        $this->assertEquals(
            array(
                'queries' => '?',
                'bindings' => 1
            ),
            $builder->getQuery('offset')
        );
    }

    public function testPrePage()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->prePage(1);

        $this->assertEquals(
            array(
                'queries' => array(
                    '?'
                ),
                'bindings' => array(
                    1
                )
            ),
            $builder->getQuery('limits')
        );
    }

    public function testGetForPage()
    {
        $builder = $this->builder();

        $connection = $this->resultConnect(false);

        $result = $this->dbResult();

        $connection->expects($this->any())->method('exec')
            ->willReturnCallback(function ($query, $bindings) use ($connection, $result) {
                $connection->setQueryLog(compact('query', 'bindings'));

                return $result;
            });

        $builder->setConnection($connection);

        $builder->from('abc');

        $builder->prePage(10)->currentPage(1);

        $result = $builder->getForPage();

        $this->assertEquals(
            array(),
            $result
        );

        $this->assertEquals(
            'SELECT * FROM `abc` LIMIT ? OFFSET ?',
            $builder->toSql()
        );

        $this->assertEquals(
            array(
                'query' => 'SELECT * FROM `abc` LIMIT ? OFFSET ?',
                'bindings' => array(10, 0),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'SELECT * FROM `abc` LIMIT 10 OFFSET 0',
            $builder->getLastParseQuery()
        );
    }

    public function testCount()
    {
        $builder = $this->builder();

        $connection = $this->resultConnect();

        $result = $this->dbResult();

        $connection->expects($this->any())->method('exec')
            ->willReturnCallback(function ($query, $bindings) use ($connection, $result) {
                $connection->setQueryLog(compact('query', 'bindings'));

                return $result;
            });

        $builder->setConnection($connection);

        $builder->from('abc');

        $result = $builder->count();

        $this->assertEquals(0, $result);

        $this->assertEquals(
            'SELECT * FROM `abc`',
            $builder->toSql()
        );

        $this->assertEquals(
            array(
                'query' => 'SELECT COUNT(*) AS `aggregate` FROM (SELECT * FROM `abc`) AS `aggregate_table`',
                'bindings' => array(),
            ),
            $builder->getLastQueryLog()
        );

        $this->assertEquals(
            'SELECT COUNT(*) AS `aggregate` FROM (SELECT * FROM `abc`) AS `aggregate_table`',
            $builder->getLastParseQuery()
        );
    }

    public function testUnion()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->union(function ($query) {
            $query->from('efg');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('UNION SELECT * FROM `efg`')
                ),
            ),
            $builder->getQuery('unions')
        );
    }

    public function testUnionAll()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->unionAll(function ($query) {
            $query->from('efg');
        });

        $this->assertEquals(
            array(
                'queries' => array(
                    new Expression('UNION ALL SELECT * FROM `efg`')
                ),
            ),
            $builder->getQuery('unions')
        );
    }

    public function testLockForUpdate()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->lockForUpdate();

        $this->assertEquals(
            'lockForUpdate',
            $builder->getQuery('lock')
        );
    }

    public function testSharedLock()
    {
        $builder = $this->builder();

        $builder->from('abc');

        $builder->sharedLock();

        $this->assertEquals(
            'sharedLock',
            $builder->getQuery('lock')
        );
    }

    public function testContactBacktick()
    {
        $builder = $this->builder();

        $result = $builder->contactBacktick(
            $builder->raw(123)
        );

        $this->assertEquals(new Expression('123'), $result);

        $result = $builder->contactBacktick(array('abc', 'efg'));

        $this->assertEquals(
            '`abc`.`efg`', 
            $result
        );

        $result = $builder->contactBacktick('abc', 'efg');

        $this->assertEquals(
            '`abc`.`efg`', 
            $result
        );

        $result = $builder->contactBacktick('abc.efg');

        $this->assertEquals(
            '`abc`.`efg`', 
            $result
        );
    }

    public function testMethod()
    {
        $methods = array(
            'set' => array('table', 'username', 'password', 'database', 'host', 'raw', 'from',),
            'process' => array('insertGetId',),
            'get' => array('parseQueryLog', 'lastParseQuery', 'lastInsertId', 'queryLog', 'lastQueryLog',),
        );

        $builder = $this->builder();

        $reflectionMethod = new \ReflectionMethod($builder, 'method');

        $reflectionMethod->setAccessible(true);

        foreach ($methods as $bindMethod => $bindMethods) {
            foreach ($bindMethods as $method) {
                $resultMethod = $reflectionMethod->invoke($builder, $method);

                $this->assertEquals($bindMethod . ucfirst($method), $resultMethod);
            }
        }

        $resultMethod = $reflectionMethod->invoke($builder, 'test');

        $this->assertEquals(
            'test', 
            $resultMethod
        );
    }

    public function testSetMethod()
    {
        $builder = $this->builder();

        $this->assertTrue(
            $builder->table('test') instanceof Builder
        );

        $this->assertTrue(
            $builder->setTable('test') instanceof Builder
        );

        try {
            $builder->foobar();
        } catch (\Exception $e) {
            $this->assertTrue(
                $e instanceof \BadMethodCallException
            );

            $this->assertEquals(
                "Method: `foobar` Not Exists",
                $e->getMessage()
            );
        }
    }

    public function testGetMethod()
    {
        $builder = $this->builder();

        $builder->table('test');

        $this->assertEquals(
            array(new Expression('`test`')),
            $builder->getFrom()
        );

        try {
            $builder->foobar();
        } catch (\Exception $e) {
            $this->assertTrue(
                $e instanceof \BadMethodCallException
            );

            $this->assertEquals(
                "Method: `foobar` Not Exists",
                $e->getMessage()
            );
        }
    }
}
