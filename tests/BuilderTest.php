<?php

namespace Wilkques\Helpers\Tests\Grammar;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Expression;
use Wilkques\Database\Queries\Grammar\GrammarInterface;
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
            $getResolverMethod->invoke($builder, 'Wilkques\Database\Connections\ConnectionInterface') instanceof ConnectionInterface
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
            $connection instanceof ConnectionInterface
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
            $grammar instanceof GrammarInterface
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
            array('123'),
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

        $builderMethod = new \ReflectionMethod($builder, 'arrayWhereNested');

        $builderMethod->setAccessible(true);

        $nested = $this->builder();

        $builderMethod->invoke($builder, $nested, 1, array('abc', 123), 'and');

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

        // $builder = $this->builder();

        // $builder->whereNull(function ($query) {
        //     $query->where('abc');
        // });

        // $this->assertEquals(
        //     array(
        //         new Expression('AND `abc` IS NULL')
        //     ),
        //     $builder->getQuery('wheres.queries')
        // );
    }
}
