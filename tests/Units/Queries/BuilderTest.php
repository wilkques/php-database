<?php

namespace Wilkques\Database\Tests\Units\Queries;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use stdClass;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Expression;

class BuilderTest extends MockeryTestCase
{
    protected $connection;

    protected $grammar;

    protected $processor;

    protected $query;

    protected $arrays;

    protected $builderClassName = 'Wilkques\Database\Queries\Builder';

    private function newQuery()
    {
        return Mockery::spy($this->builderClassName)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    private function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);

        $propertyRef = $reflection->getProperty($property);

        $propertyRef->setAccessible(true);

        $propertyRef->setValue($object, $value);

        return $propertyRef;
    }

    private function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);

        $propertyRef = $reflection->getProperty($property);

        $propertyRef->setAccessible(true);

        return $propertyRef->getValue($object);
    }

    public function testConstructor()
    {
        $query = new Builder($this->connection, $this->grammar, $this->processor);

        $this->assertInstanceOf($this->builderClassName, $query);
    }

    public function testMake()
    {
        $query = Builder::make($this->connection, $this->grammar, $this->processor);

        $this->assertInstanceOf($this->builderClassName, $query);
    }

    public function testGetResolvers()
    {
        $resolvers = $this->query->getResolvers();

        $this->assertEmpty($resolvers);
    }

    public function testResolverRegisterWithNull()
    {
        $result = $this->query->resolverRegister(null);

        $this->assertSame($this->query, $result);

        $resolvers = $this->query->getResolvers();

        $this->assertEmpty($resolvers);
    }

    public function testResolverRegisterWithObject()
    {
        $className = get_class($this->connection);

        $this->arrays->shouldReceive('set')->passthru();

        $this->query->resolverRegister($this->connection);

        $this->query->shouldReceive('getResolvers')
            ->once()
            ->andReturn(array($className => $this->connection));

        $resolvers = $this->query->getResolvers();

        $this->assertArrayHasKey($className, $resolvers);

        $this->assertSame($this->connection, $resolvers[$className]);
    }

    public function testResolverRegisterWithClassName()
    {
        $className = get_class($this->connection);

        $this->arrays->shouldReceive('set')->passthru();

        $this->query->resolverRegister($className, $this->connection);

        $this->query->shouldReceive('getResolvers')
            ->once()
            ->andReturn(array($className => $this->connection));

        $resolvers = $this->query->getResolvers();

        $this->assertArrayHasKey($className, $resolvers);

        $this->assertSame($this->connection, $resolvers[$className]);
    }

    public function testGetResolverReturnsCorrectResolver()
    {
        $this->arrays->shouldReceive('mergeDistinctRecursive')->passthru();

        $this->arrays->shouldReceive('filter')->passthru();

        $this->arrays->shouldReceive('first')->passthru();

        $this->query->resolverRegister($this->connection);

        $connection = current(class_parents($this->connection));

        $resolver = $this->query->getResolver($connection);

        $this->assertSame($this->connection, $resolver);
    }

    public function testGetResolverReturnsNullWhenNoMatch()
    {
        $this->arrays->shouldReceive('mergeDistinctRecursive')->passthru();

        $this->arrays->shouldReceive('filter')->passthru();

        $this->arrays->shouldReceive('first')->passthru();

        $resolver = $this->query->getResolver('NonMatchingClass');

        $this->assertNull($resolver);
    }

    public function testSetConnectionRegistersConnection()
    {
        $this->query->shouldReceive('resolverRegister')
            ->once()
            ->with($this->connection)
            ->andReturn($this->query);

        $result = $this->query->setConnection($this->connection);

        $this->assertSame($this->query, $result);
    }

    public function testGetConnectionReturnsCorrectResolver()
    {
        $this->query->shouldReceive('getResolver')
            ->once()
            ->with('Wilkques\Database\Connections\Connections')
            ->andReturn($this->connection);

        $result = $this->query->getConnection();

        $this->assertSame($this->connection, $result);
    }

    public function testSetGrammarRegistersGrammar()
    {
        $this->query->shouldReceive('resolverRegister')
            ->once()
            ->with($this->grammar)
            ->andReturn($this->query);

        $result = $this->query->setGrammar($this->grammar);

        $this->assertSame($this->query, $result);
    }

    public function testGetGrammarReturnsCorrectResolver()
    {
        $this->query->shouldReceive('getResolver')
            ->once()
            ->with('Wilkques\Database\Queries\Grammar\Grammar')
            ->andReturn($this->grammar);

        $result = $this->query->getGrammar();

        $this->assertSame($this->grammar, $result);
    }

    public function testSetProcessorRegistersProcessor()
    {
        $this->query->shouldReceive('resolverRegister')
            ->once()
            ->with($this->processor)
            ->andReturn($this->query);

        $result = $this->query->setProcessor($this->processor);

        $this->assertSame($this->query, $result);
    }

    public function testGetProcessorReturnsCorrectResolver()
    {
        $this->query->shouldReceive('getResolver')
            ->once()
            ->with('Wilkques\Database\Queries\Processors\ProcessorInterface')
            ->andReturn($this->processor);

        $result = $this->query->getProcessor();

        $this->assertSame($this->processor, $result);
    }

    public function testGetQueriesReturnsCorrectQueries()
    {
        $result = $this->query->getQueries();

        $this->assertEmpty($result);
    }

    public function testSetQueries()
    {
        $queries = array('key1' => 'value1', 'key2' => 'value2');

        $result = $this->query->setQueries($queries);

        $this->assertSame($queries, $this->query->getQueries());

        $this->assertSame($this->query, $result);
    }

    public function testGetQueryReturnsCorrectValue()
    {
        $queries = array('key' => 'value');

        $this->query->shouldReceive('getQueries')
            ->once()
            ->andReturn($queries);

        $result = $this->query->getQuery('key', 'default');

        $this->assertSame('value', $result);
    }

    public function testNewQueryCreatesNewBuilderInstance()
    {
        $query = new Builder($this->connection, $this->grammar, $this->processor);

        $result = $query->newQuery();

        $this->assertInstanceOf(get_class($query), $result);
    }

    public function testPrependDatabaseNameIfCrossDatabaseQuery()
    {
        $query = $this->newQuery();

        $database = 'test';

        $query->shouldReceive('getConnection->getDatabase')
            ->andReturn($database);

        $this->query->shouldReceive('getConnection->getDatabase')
            ->andReturn('try');

        $query->shouldReceive('getFrom')
            ->once()
            ->andReturn(array("`{$database}`table1"));

        $query->shouldReceive('contactBacktick')
            ->once()
            ->with($database, "`{$database}`table1")
            ->andReturn("`{$database}`.`{$database}`.`table1`");

        $query->shouldReceive('setFrom')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('numeric'));

        $result = $this->query->prependDatabaseNameIfCrossDatabaseQuery($query);

        $this->assertSame($query, $result);
    }

    public function testParseSubWithQueryBuilderInstance()
    {
        $query = $this->newQuery();

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM table');

        $query->shouldReceive('getBindings')->andReturn(array());

        $this->query->shouldReceive('prependDatabaseNameIfCrossDatabaseQuery')
            ->with($query)
            ->andReturn($query);

        $result = $this->query->parseSub($query);

        $this->assertEquals(array('SELECT * FROM table', array()), $result);
    }

    public function testParseSubWithString()
    {
        $result = $this->query->parseSub('SELECT * FROM table');

        $this->assertEquals(array('SELECT * FROM table', array()), $result);
    }

    public function testParseSubWithNumeric()
    {
        $result = $this->query->parseSub(123);

        $this->assertEquals(array(123, array()), $result);
    }

    public function testParseSubWithExpression()
    {
        $expression = new Expression('NOW()');

        $result = $this->query->parseSub($expression);

        $this->assertEquals(array($expression, array()), $result);
    }

    public function testParseSubWithInvalidArgument()
    {
        try {
            $this->query->parseSub(new stdClass());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'A subquery must be a query builder instance, a Closure, or a string.',
                $e->getMessage()
            );
        }
    }

    public function testCreateSubWithClosure()
    {
        $callback = function ($query) {
            $query->where('column', 'value');
        };

        $query = $this->newQuery();

        $query->shouldReceive('where')->once()->with('column', 'value')->andReturnSelf();

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM table');

        $query->shouldReceive('getBindings')->andReturn(array());

        $this->query->shouldReceive('forSubQuery')->andReturn($query);

        $this->query->shouldReceive('parseSub')->with($query)->andReturn(array('SELECT * FROM table', array()));

        $result = $this->query->createSub($callback);

        $this->assertEquals(array('SELECT * FROM table', array()), $result);
    }

    public function testCreateSubWithNonClosure()
    {
        $callback = 'SELECT * FROM table';

        $this->query->shouldReceive('parseSub')->with($callback)->andReturn(array($callback, array()));

        $result = $this->query->createSub($callback);

        $this->assertEquals(array($callback, array()), $result);
    }

    public function testCreateSubWithForSubQuery()
    {
        $query = $this->newQuery();

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM table');

        $query->shouldReceive('getBindings')->andReturn(array());

        $this->query->shouldReceive('forSubQuery')->andReturn($query);

        $this->query->shouldReceive('parseSub')->with($query)->andReturn(array('SELECT * FROM table', array()));

        $result = $this->query->createSub($query);

        $this->assertEquals(array('SELECT * FROM table', array()), $result);
    }

    public function testCreateSubWithInvalidCallback()
    {
        try {
            $this->query->parseSub(new stdClass());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'A subquery must be a query builder instance, a Closure, or a string.',
                $e->getMessage()
            );
        }
    }

    public function testToSql()
    {
        $this->grammar->shouldReceive('compilerSelect')
            ->with(Mockery::type($this->builderClassName))
            ->andReturn('SELECT * FROM table');

        $this->query->shouldReceive('getGrammar')->andReturn($this->grammar);

        $result = $this->query->toSql();

        $this->assertEquals('SELECT * FROM table', $result);
    }

    public function testBindingsNestedWithExpression()
    {
        $bindings = array(
            'first' => new Expression('NOW()'),
            'second' => 'value',
            'third' => 123
        );

        $result = $this->query->bindingsNested($bindings);

        $this->assertEquals(array_values($bindings), $result);
    }

    public function testBindingsNestedWithEmptyArray()
    {
        $bindings = array();

        $result = $this->query->bindingsNested($bindings);

        $this->assertEquals($bindings, $result);
    }

    public function testGetBindingsWithArrayBindings()
    {
        $this->setProtectedProperty($this->query, 'bindingComponents', array('component1', 'component2'));

        $this->query->shouldReceive('getQuery')->with('component1.bindings')->andReturn(array('binding1', 'binding2'));

        $this->query->shouldReceive('getQuery')->with('component2.bindings')->andReturn(array('binding3'));

        $result = $this->query->getBindings();

        $expected = array('binding1', 'binding2', 'binding3');

        $this->assertEquals($expected, $result);
    }

    public function testGetBindingsWithExcludedComponents()
    {
        $this->setProtectedProperty($this->query, 'bindingComponents', array('component1', 'component2', 'component3'));

        $this->query->shouldReceive('getQuery')->with('component1.bindings')->andReturn(array('binding1'));

        $this->query->shouldReceive('getQuery')->with('component2.bindings')->andReturn(array('binding2'));

        $this->query->shouldReceive('getQuery')->with('component3.bindings')->andReturn(array('binding3'));

        $result = $this->query->getBindings(array('component2'));

        $expected = array('binding1', 'binding3');

        $this->assertEquals($expected, $result);
    }

    public function testGetBindingsWithNoBindings()
    {
        $this->setProtectedProperty($this->query, 'bindingComponents', array('component1', 'component2'));

        $this->query->shouldReceive('getQuery')->with('component1.bindings')->andReturn(null);

        $this->query->shouldReceive('getQuery')->with('component2.bindings')->andReturn([]);

        $result = $this->query->getBindings();

        $expected = array();

        $this->assertEquals($expected, $result);
    }

    public function testQueriesPushWithBinding()
    {
        $this->query->shouldReceive('queryPush')
            ->with('some_query', 'wheres')
            ->once();

        $this->query->shouldReceive('bindingPush')
            ->with('some_binding', 'wheres')
            ->once();

        $result = $this->query->queriesPush('some_query', 'some_binding', 'wheres');

        $this->assertSame($this->query, $result);
    }

    public function testQueriesPushWithoutBinding()
    {
        $this->query->shouldReceive('queryPush')
            ->with('some_query', 'wheres')
            ->once();

        $result = $this->query->queriesPush('some_query', null, 'wheres');

        $this->assertSame($this->query, $result);
    }

    public function testQueryPush()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('queries' => array()),
        ));

        $result = $this->query->queryPush('some_query', 'wheres');

        $expectedQueries = array(
            'wheres' => array('queries' => array('some_query')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testBindingPushWithArray()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $this->query->shouldReceive('bindingsNested')
            ->with(array('value1', 'value2'))
            ->andReturn(array('processed_value1', 'processed_value2'));

        $result = $this->query->bindingPush(array('value1', 'value2'), 'wheres');

        $expectedQueries = array(
            'wheres' => array('bindings' => array('processed_value1', 'processed_value2')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testBindingPushWithSingleValue()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $result = $this->query->bindingPush('single_value', 'wheres');

        $expectedQueries = array(
            'wheres' => array('bindings' => array('single_value')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testBindingPushWithExpression()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $expression = new Expression('NOW()');

        $result = $this->query->bindingPush($expression, 'wheres');

        $expectedQueries = array(
            'wheres' => array('bindings' => array()),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testSubQueryAsContactBacktickWithStringAlias()
    {
        $this->query->shouldReceive('contactBacktick')
            ->with('alias')
            ->andReturn('`alias`');

        $result = $this->query->subQueryAsContactBacktick('SELECT * FROM table', 'alias');

        $expected = '(SELECT * FROM table) AS `alias`';

        $this->assertEquals($expected, $result);
    }

    public function testSubQueryAsContactBacktickWithExpressionAlias()
    {
        $expression = new Expression('some_expression');

        $this->query->shouldReceive('contactBacktick')
            ->with($expression)
            ->andReturn('`some_expression`');

        $result = $this->query->subQueryAsContactBacktick('SELECT * FROM table', $expression);

        $expected = '(SELECT * FROM table) AS `some_expression`';

        $this->assertEquals($expected, $result);
    }

    public function testSubQueryAsContactBacktickWithoutAlias()
    {
        $result = $this->query->subQueryAsContactBacktick('SELECT * FROM table');

        $expected = '(SELECT * FROM table)';

        $this->assertEquals($expected, $result);
    }

    public function testQueryAsContactBacktickWithStringAlias()
    {
        $this->query->shouldReceive('contactBacktick')
            ->with('some_query')
            ->andReturn('`some_query`');

        $this->query->shouldReceive('contactBacktick')
            ->with('alias')
            ->andReturn('`alias`');

        $result = $this->query->queryAsContactBacktick('some_query', 'alias');

        $expected = '`some_query` AS `alias`';

        $this->assertEquals($expected, $result);
    }

    public function testQueryAsContactBacktickWithExpressionAlias()
    {
        $expression = new Expression('some_expression');

        $this->query->shouldReceive('contactBacktick')
            ->with('some_query')
            ->andReturn('`some_query`');

        $this->query->shouldReceive('contactBacktick')
            ->with($expression)
            ->andReturn('`some_expression`');

        $result = $this->query->queryAsContactBacktick('some_query', $expression);

        $expected = '`some_query` AS `some_expression`';

        $this->assertEquals($expected, $result);
    }

    public function testQueryAsContactBacktickWithoutAlias()
    {
        $this->query->shouldReceive('contactBacktick')
            ->with('some_query')
            ->andReturn('`some_query`');

        $result = $this->query->queryAsContactBacktick('some_query');

        $expected = '`some_query`';

        $this->assertEquals($expected, $result);
    }
    public function testRaw()
    {
        $value = 'NOW()';

        $expression = $this->query->raw($value);

        $this->assertInstanceOf('Wilkques\Database\Queries\Expression', $expression);

        $this->assertEquals($value, $expression->getValue());
    }

    public function testFromRaw()
    {
        $expression = 'some_table';

        $bindings = array('binding1', 'binding2');

        $this->query->shouldReceive('raw')
            ->with($expression)
            ->andReturn(new Expression($expression));

        $this->query->shouldReceive('queriesPush')
            ->with(Mockery::type('Wilkques\Database\Queries\Expression'), $bindings, 'froms')
            ->andReturnSelf();

        $result = $this->query->fromRaw($expression, $bindings);

        $this->assertSame($this->query, $result);
    }

    public function testSetFrom()
    {
        $initialFroms = isset($this->query->queries['froms']['queries']) ? $this->query->queries['froms']['queries'] : array();

        $this->assertEmpty($initialFroms);

        $from = 'some_table';

        $index = 0;

        $result = $this->query->setFrom($from, $index);

        $queries = $this->getProtectedProperty($this->query, 'queries');

        $this->assertEquals($from, $queries['froms']['queries'][$index]);

        $this->assertSame($this->query, $result);
    }

    public function testFromSingleTableWithAlias()
    {
        $this->query->shouldReceive('fromRaw')
            ->with('`table` AS `alias`')
            ->andReturnSelf();

        $result = $this->query->from('table', 'alias');

        $this->assertSame($this->query, $result);
    }

    public function testFromClosure()
    {
        $closure = function () {};

        $this->query->shouldReceive('fromSub')
            ->with(Mockery::type('Closure'), 'alias')
            ->andReturnSelf();

        $result = $this->query->from($closure, 'alias');

        $this->assertSame($this->query, $result);
    }

    public function testFromSelfInstance()
    {
        $subQuery = $this->newQuery();

        $this->query->shouldReceive('fromSub')
            ->with($subQuery, 'alias')
            ->andReturnSelf();

        $result = $this->query->from($subQuery, 'alias');

        $this->assertSame($this->query, $result);
    }

    public function testFromWithFromRaw()
    {
        $this->query->shouldReceive('fromRaw')
            ->with('`raw_query`')
            ->andReturnSelf();

        $result = $this->query->from('raw_query');

        $this->assertSame($this->query, $result);
    }

    public function testFromMultipleQueries()
    {
        $tableName = 'table';

        $closure = function () {};

        $subQuery = $this->newQuery();

        $alias = 'alias';

        $this->query->shouldReceive('queryAsContactBacktick')
            ->with($tableName, $alias)
            ->andReturn('`table` AS `alias`');

        $this->query->shouldReceive('fromRaw')
            ->with('`table` AS `alias`')
            ->andReturnSelf();

        $this->query->shouldReceive('fromSub')
            ->with($closure, null)
            ->andReturnSelf();

        $this->query->shouldReceive('fromSub')
            ->with($subQuery, null)
            ->andReturnSelf();

        $result = $this->query->from([
            $tableName => $alias,
            $closure,
            $subQuery
        ]);

        $this->assertSame($this->query, $result);
    }

    public function testFromSubWithArray()
    {
        $fromArray = array('table1', 'table2');

        $this->query->shouldReceive('from')
            ->with($fromArray)
            ->andReturnSelf();

        $result = $this->query->fromSub($fromArray);

        $this->assertSame($this->query, $result);
    }

    public function testFromSubWithSubQuery()
    {
        $subQuery = $this->newQuery();

        $this->query->shouldReceive('createSub')
            ->with($subQuery)
            ->andReturn(array('sub_query_sql', array()));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with('sub_query_sql', null)
            ->andReturn('(`sub_query_sql`)');

        $this->query->shouldReceive('fromRaw')
            ->with('(`sub_query_sql`)', array())
            ->andReturnSelf();

        $result = $this->query->fromSub($subQuery);

        $this->assertSame($this->query, $result);
    }

    public function testFromSubWithClosure()
    {
        $closure = function ($query) {
            $query->where('column', 'value');
        };

        $this->query->shouldReceive('createSub')
            ->with(Mockery::type('Closure'))
            ->andReturn(array('sub_query_sql', array()));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with('sub_query_sql', null)
            ->andReturn('(`sub_query_sql`)');

        $this->query->shouldReceive('fromRaw')
            ->with('(`sub_query_sql`)', array())
            ->andReturnSelf();

        $result = $this->query->fromSub($closure);

        $this->assertSame($this->query, $result);
    }

    public function testFromSubWithAlias()
    {
        $subQuery = $this->newQuery();

        $alias = 'alias_name';

        $this->query->shouldReceive('createSub')
            ->with($subQuery)
            ->andReturn(array('sub_query_sql', array()));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with('sub_query_sql', $alias)
            ->andReturn('(`sub_query_sql`) AS `alias_name`');

        $this->query->shouldReceive('fromRaw')
            ->with('(`sub_query_sql`) AS `alias_name`', array())
            ->andReturnSelf();

        $result = $this->query->fromSub($subQuery, $alias);

        $this->assertSame($this->query, $result);
    }

    public function testGetFromWithQueriesSet()
    {
        $this->query->shouldReceive('getQuery')
            ->with('froms.queries')
            ->andReturn(array('table1', 'table2'));

        $result = $this->query->getFrom();

        $this->assertEquals(array('table1', 'table2'), $result);
    }

    public function testGetFromWithNoQueries()
    {
        $this->query->shouldReceive('getQuery')
            ->with('froms.queries')
            ->andReturn(array());

        $result = $this->query->getFrom();

        $this->assertEquals(array(), $result);
    }

    public function testSetTableWithoutAlias()
    {
        $table = 'users';

        $this->query->shouldReceive('from')
            ->with($table, null)
            ->andReturnSelf();

        $result = $this->query->setTable($table);

        $this->assertSame($this->query, $result);
    }

    public function testSetTableWithAlias()
    {
        $table = 'users';

        $alias = 'u';

        $this->query->shouldReceive('from')
            ->with($table, $alias)
            ->andReturnSelf();

        $result = $this->query->setTable($table, $alias);

        $this->assertSame($this->query, $result);
    }

    public function testGetTable()
    {
        $expectedResult = array('users');

        $this->query->shouldReceive('getFrom')
            ->andReturn($expectedResult);

        $result = $this->query->getTable();

        $this->assertEquals($expectedResult, $result);
    }

    public function testSelectRaw()
    {
        $column = 'abc';

        $expression = new Expression($column);

        $bindings = array(1, 2);

        $this->query->shouldReceive('raw')
            ->with($expression)
            ->andReturn($expression);

        $this->query->shouldReceive('queriesPush')
            ->with($expression, $bindings, 'columns')
            ->andReturnSelf();

        $result = $this->query->selectRaw($expression, $bindings);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithSingleColumn()
    {
        $column = 'users.id';

        $this->query->shouldReceive('queryAsContactBacktick')
            ->with($column, null)
            ->andReturn('`users`.`id`');

        $this->query->shouldReceive('queryPush')
            ->with('`users`.`id`', 'columns')
            ->andReturnSelf();

        $result = $this->query->select($column);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithMultipleColumns()
    {
        $columns = array('users.id', 'users.name');

        $this->query->shouldReceive('queryAsContactBacktick')
            ->with('users.id', 0)
            ->andReturn('`users`.`id`');

        $this->query->shouldReceive('queryAsContactBacktick')
            ->with('users.name', 1)
            ->andReturn('`users`.`name`');

        $this->query->shouldReceive('queryPush')
            ->with('`users`.`id`', 'columns')
            ->andReturnSelf();

        $this->query->shouldReceive('queryPush')
            ->with('`users`.`name`', 'columns')
            ->andReturnSelf();

        $result = $this->query->select($columns);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithWildcard()
    {
        $wildcard = '*';

        $this->query->shouldReceive('queryPush')
            ->with($wildcard, 'columns')
            ->andReturnSelf();

        $result = $this->query->select($wildcard);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithClosure()
    {
        $closure = function ($query) {
            $query->where('active', 1);
        };

        $this->query->shouldReceive('selectSub')
            ->with($closure, null)
            ->andReturnSelf();

        $result = $this->query->select($closure);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithAlias()
    {
        $column = 'users.id';

        $alias = 'user_id';

        $this->query->shouldReceive('queryAsContactBacktick')
            ->with($column, $alias)
            ->andReturn('`users`.`id` AS `user_id`');

        $this->query->shouldReceive('queryPush')
            ->with('`users`.`id` AS `user_id`', 'columns')
            ->andReturnSelf();

        $result = $this->query->select([$column => $alias]);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithSelfInstance()
    {
        $subQuery = $this->newQuery(); 

        // Mock the selectSub method
        $this->query->shouldReceive('selectSub')
            ->with($subQuery, null)
            ->andReturnSelf();

        // Test the select method with a subquery instance
        $result = $this->query->select($subQuery);

        // Verify the result
        $this->assertSame($this->query, $result);
    }

    public function testSelectSubWithArray()
    {
        $columns = array('users.id', 'users.name');

        // Mock the select method
        $this->query->shouldReceive('select')
            ->with($columns)
            ->andReturnSelf();

        // Test selectSub with an array
        $result = $this->query->selectSub($columns);

        // Verify the result
        $this->assertSame($this->query, $result);
    }

    public function testSelectSubWithSubquery()
    {
        $subQuery = $this->newQuery();

        $query = 'SELECT * FROM users';

        $bindings = array();

        $this->query->shouldReceive('createSub')
            ->with($subQuery)
            ->andReturn(array($query, $bindings));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with($query, null)
            ->andReturn("({$query})");

        $this->query->shouldReceive('selectRaw')
            ->with("({$query})", $bindings)
            ->andReturnSelf();

        $result = $this->query->selectSub($subQuery);

        $this->assertSame($this->query, $result);
    }

    public function testSelectSubWithSubqueryAlias()
    {
        $subQuery = $this->newQuery();

        $alias = 'users';

        $query = 'SELECT * FROM users';

        $bindings = array();

        $this->query->shouldReceive('createSub')
            ->with($subQuery)
            ->andReturn(array($query, $bindings));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with($query, $alias)
            ->andReturn("({$query}) AS {$alias}");

        $this->query->shouldReceive('selectRaw')
            ->with("({$query}) AS {$alias}", $bindings)
            ->andReturnSelf();

        $result = $this->query->selectSub($subQuery, $alias);

        $this->assertSame($this->query, $result);
    }

    public function testSelectSubWithRawExpression()
    {
        $expression = new Expression('COUNT(*)');

        $bindings = [];

        $this->query->shouldReceive('createSub')
            ->with($expression)
            ->andReturn(array($expression, $bindings));

        $this->query->shouldReceive('subQueryAsContactBacktick')
            ->with($expression, null)
            ->andReturn("({$expression})");

        $this->query->shouldReceive('selectRaw')
            ->with("({$expression})", $bindings)
            ->andReturnSelf();

        $result = $this->query->selectSub($expression);

        // Verify the result
        $this->assertSame($this->query, $result);
    }

    public function testInvalidOperatorAndValue()
    {
        // 測試不合法的操作符和空值
        $this->assertTrue($this->query->invalidOperatorAndValue('>', null));  // true
        $this->assertTrue($this->query->invalidOperatorAndValue('<', null));  // true
        $this->assertTrue($this->query->invalidOperatorAndValue('>=', null)); // true
        $this->assertTrue($this->query->invalidOperatorAndValue('<=', null)); // true
        $this->assertTrue($this->query->invalidOperatorAndValue('like', null)); // true
        $this->assertTrue($this->query->invalidOperatorAndValue('ilike', null)); // true
        $this->assertTrue($this->query->invalidOperatorAndValue('between', null)); // true
        $this->assertTrue($this->query->invalidOperatorAndValue('exists', null)); // true
        $this->assertFalse($this->query->invalidOperatorAndValue('=', null)); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('!=', null)); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('is', null)); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('is not', null)); // false

        // 測試合法的操作符和非空值
        $this->assertFalse($this->query->invalidOperatorAndValue('>', 5));  // false
        $this->assertFalse($this->query->invalidOperatorAndValue('=', 5));  // false 
        $this->assertFalse($this->query->invalidOperatorAndValue('=', 5)); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('!=', 5)); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('is', 'value')); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('in', array(1, 2, 3))); // false
        $this->assertFalse($this->query->invalidOperatorAndValue('between', array(1, 10))); // false
    }

    public function testPrepareValueAndOperatorWithDefault()
    {
        // 測試使用預設值
        $result = $this->query->prepareValueAndOperator('value', '>', true);

        $this->assertEquals(array('>', '='), $result);
    }

    public function testPrepareValueAndOperatorWithInvalidCombination()
    {
        // 模擬 invalidOperatorAndValue 方法
        $this->query->shouldReceive('invalidOperatorAndValue')
            ->with('>', null)
            ->andReturn(true);

        // 測試不合法的操作符和空值
        // $this->expectException(InvalidArgumentException::class);
        $this->query->prepareValueAndOperator(null, '>', false);
    }

    public function testInvalidOperatorReturnsTrueForNonString()
    {
        $this->assertTrue($this->query->invalidOperator(123)); // 整數
        $this->assertTrue($this->query->invalidOperator(null)); // null
        $this->assertTrue($this->query->invalidOperator([])); // 陣列
        $this->assertTrue($this->query->invalidOperator(new stdClass())); // 對象
    }

    public function testInvalidOperatorReturnsTrueForUnsupportedOperator()
    {
        $this->assertTrue($this->query->invalidOperator('unsupported_operator')); // 不支持的運算符
    }

    public function testInvalidOperatorReturnsFalseForSupportedOperators()
    {
        $supportedOperators = $this->query->operators;

        foreach ($supportedOperators as $operator) {
            $this->assertFalse($this->query->invalidOperator($operator));
        }
    }

    public function testInvalidOperatorReturnsFalseForCaseInsensitiveOperators()
    {
        $this->assertFalse($this->query->invalidOperator('LIKE')); // 大寫
        $this->assertFalse($this->query->invalidOperator('Not Like')); // 混合大小寫
    }
}
