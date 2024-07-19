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
        // $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        // $grammar = call_user_func(array($this, $createMock), 'Wilkques\Database\Queries\Grammar\GrammarInterface');

        // return $grammar;

        // return $this->getMockForAbstractClass('Wilkques\Database\Queries\Grammar\GrammarInterface');

        return $this->getMockForAbstractClass('Wilkques\Database\Queries\Grammar\Grammar');
    }

    private function connection()
    {
        // $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        // $connection = call_user_func(array($this, $createMock), 'Wilkques\Database\Connections\ConnectionInterface');

        // return $connection;

        // return $this->getMockForAbstractClass('Wilkques\Database\Connections\ConnectionInterface');

        return $this->getMockForAbstractClass('Wilkques\Database\Connections\Connections');
    }

    private function process()
    {
        // $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        // $process = call_user_func(array($this, $createMock), 'Wilkques\Database\Queries\Processors\ProcessorInterface');

        // return $process;

        // return $this->getMockForAbstractClass('Wilkques\Database\Queries\Processors\ProcessorInterface');

        return $this->getMockForAbstractClass('Wilkques\Database\Queries\Processors\Processor');
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

        $builder = $builder->getConnection();

        $this->assertTrue(
            $builder instanceof ConnectionInterface
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

        $builder = $builder->getGrammar();

        $this->assertTrue(
            $builder instanceof GrammarInterface
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

        $builder = $builder->getProcessor();

        $this->assertTrue(
            $builder instanceof ProcessorInterface
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
                new \Wilkques\Database\Queries\Expression('`abc`')
            ),
            $from->getFrom()
        );

        $from = $this->builder()->from('abc', 'a');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('`abc` AS `a`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg');
        });

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->from($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg', 'e');
        });

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->from($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->from($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(function ($query) {
            $query->from('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->from($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->fromSub($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg', 'e');
        });

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->fromSub($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg');

        $from = $builder->fromSub($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->fromSub(function ($query) {
            $query->from('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->from('efg', 'e');

        $from = $builder->fromSub($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->from(array(
            'e' => new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('`abc`')
            ),
            $from->getFrom()
        );

        $from = $this->builder()->setTable('abc', 'a');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('`abc` AS `a`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg');
        });

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg');

        $from = $builder->setTable($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg', 'e');
        });

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $from = $builder->setTable($newBuilder);

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg');

        $from = $builder->setTable($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(function ($query) {
            $query->setTable('efg', 'e');
        }, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $newBuilder = $this->builder();

        $newBuilder->setTable('efg', 'e');

        $from = $builder->setTable($newBuilder, 'e');

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `f`')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`)')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg` AS `e`) AS `e`')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $from->getFrom()
        );

        $builder = $this->builder();

        $from = $builder->setTable(array(
            'e' => new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
        ));

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`) AS `e`')
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
                new \Wilkques\Database\Queries\Expression('*')
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
            new \Wilkques\Database\Queries\Expression('*')
        );

        $this->assertEquals(
            array(
                '*'
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(
            new \Wilkques\Database\Queries\Expression('abc'),
            new \Wilkques\Database\Queries\Expression('efg')
        );

        $this->assertEquals(
            array(
                new \Wilkques\Database\Queries\Expression('abc'),
                new \Wilkques\Database\Queries\Expression('efg')
            ),
            $builder->getQuery('columns.queries')
        );

        $builder = $this->builder()->select(array(
            'a' => new \Wilkques\Database\Queries\Expression('abc'),
            'e' => new \Wilkques\Database\Queries\Expression('efg')
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
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `abc`)'),
                new \Wilkques\Database\Queries\Expression('(SELECT * FROM `efg`)')
            ),
            $builder->getQuery('columns.queries')
        );
    }
}
