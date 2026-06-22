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

    public function testaddQueryBindingsWithBinding()
    {
        $this->query->shouldReceive('addQuery')
            ->with('some_query', 'wheres')
            ->once();

        $this->query->shouldReceive('addBinding')
            ->with('some_binding', 'wheres')
            ->once();

        $result = $this->query->queriesPush('some_query', 'some_binding', 'wheres');

        $this->assertSame($this->query, $result);
    }

    public function testQueriesPushWithoutBinding()
    {
        $this->query->shouldReceive('addQuery')
            ->with('some_query', 'wheres')
            ->once();

        $result = $this->query->queriesPush('some_query', null, 'wheres');

        $this->assertSame($this->query, $result);
    }

    public function testaddQuery()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('queries' => array()),
        ));

        $result = $this->query->addQuery('some_query', 'wheres');

        $expectedQueries = array(
            'wheres' => array('queries' => array('some_query')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testaddBindingWithArray()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $this->query->shouldReceive('bindingsNested')
            ->with(array('value1', 'value2'))
            ->andReturn(array('processed_value1', 'processed_value2'));

        $result = $this->query->addBinding(array('value1', 'value2'), 'wheres');

        $expectedQueries = array(
            'wheres' => array('bindings' => array('processed_value1', 'processed_value2')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testaddBindingWithSingleValue()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $result = $this->query->addBinding('single_value', 'wheres');

        $expectedQueries = array(
            'wheres' => array('bindings' => array('single_value')),
        );

        $this->assertEquals($expectedQueries, $property->getValue($this->query));

        $this->assertSame($this->query, $result);
    }

    public function testaddBindingWithExpression()
    {
        $property = $this->setProtectedProperty($this->query, 'queries', array(
            'wheres' => array('bindings' => array()),
        ));

        $expression = new Expression('NOW()');

        $result = $this->query->addBinding($expression, 'wheres');

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
        $this->query->shouldHaveReceived('fromRaw')
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

        $this->query->shouldReceive('addQuery')
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

        $this->query->shouldReceive('addQuery')
            ->with('`users`.`id`', 'columns')
            ->andReturnSelf();

        $this->query->shouldReceive('addQuery')
            ->with('`users`.`name`', 'columns')
            ->andReturnSelf();

        $result = $this->query->select($columns);

        $this->assertSame($this->query, $result);
    }

    public function testSelectWithWildcard()
    {
        $wildcard = '*';

        $this->query->shouldReceive('addQuery')
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

        $this->query->shouldReceive('addQuery')
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
        $reflection = new \ReflectionClass($this->query);

        $property = $reflection->getProperty('operators');

        $property->setAccessible(true);

        $supportedOperators = $property->getValue($this->query);

        foreach ($supportedOperators as $operator) {
            $this->assertFalse($this->query->invalidOperator($operator));
        }
    }

    public function testInvalidOperatorReturnsFalseForCaseInsensitiveOperators()
    {
        $this->assertFalse($this->query->invalidOperator('LIKE')); // 大寫
        $this->assertFalse($this->query->invalidOperator('Not Like')); // 混合大小寫
    }

    // =========================================================================
    // setTable 隔離測試 (Bug Fix 驗證)
    // =========================================================================

    public function testSetTableReturnsNewQueryInstance()
    {
        $newQuery = Mockery::mock('Wilkques\Database\Queries\Builder');
        $newQuery->shouldReceive('from')->andReturnSelf();

        $this->query->shouldReceive('newQuery')->andReturn($newQuery);

        $result = $this->query->table('users');

        $this->assertNotSame($this->query, $result);
        $this->assertInstanceOf('Wilkques\Database\Queries\Builder', $result);
    }

    public function testSetTableCallsNewQueryBeforeFrom()
    {
        $newQuery = Mockery::mock('Wilkques\Database\Queries\Builder');
        $newQuery->shouldReceive('from')->with('users', null)->once()->andReturnSelf();

        $this->query->shouldReceive('newQuery')->once()->andReturn($newQuery);

        $this->query->table('users');

        $newQuery->shouldHaveReceived('from')->once();
    }

    public function testSetTableDoesNotCarryOverWheres()
    {
        $firstQuery = Mockery::mock('Wilkques\Database\Queries\Builder');
        $firstQuery->shouldReceive('from')->andReturnSelf();

        $secondQuery = Mockery::mock('Wilkques\Database\Queries\Builder');
        $secondQuery->shouldReceive('from')->andReturnSelf();

        $this->query->shouldReceive('newQuery')
            ->andReturn($firstQuery, $secondQuery);

        $first = $this->query->table('users');
        $second = $this->query->table('posts');

        $this->assertNotSame($first, $second);
        $this->assertSame($firstQuery, $first);
        $this->assertSame($secondQuery, $second);
    }

    // =========================================================================
    // WHERE 系列
    // =========================================================================

    public function testWhereRawAddsToWhereQueries()
    {
        $this->query->whereRaw('id = 1');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereRawAddsToWhereQueries()
    {
        $this->query->orWhereRaw('id = 1');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereRawWithBindings()
    {
        $this->query->whereRaw('id = ?', 1);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
        $this->assertNotEmpty($queries['wheres']['bindings']);
    }

    public function testWhereAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->where('id', '=', 1);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereWithTwoArgsDefaultsToEquals()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->where('status', 'active');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhere('id', 1);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereNullAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereNull('deleted_at');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereNullAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereNull('deleted_at');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereNotNullAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereNotNull('email');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereNotNullAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereNotNull('email');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereInAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereIn('id', array(1, 2, 3));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereInAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereIn('id', array(1, 2));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereNotInAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereNotIn('status', array('banned', 'inactive'));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereNotInAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereNotIn('status', array('banned'));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereBetweenAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereBetween('age', array(18, 30));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereBetweenAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereBetween('age', array(18, 30));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereNotBetweenAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->whereNotBetween('score', array(0, 100));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereNotBetweenAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orWhereNotBetween('score', array(0, 100));
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereExistsAddsToWhereQueries()
    {
        $this->query->shouldReceive('createSub')
            ->andReturn(array('SELECT 1 FROM `orders`', array()));

        $self = $this;
        $this->query->whereExists(function ($q) use ($self) {
            $q->shouldReceive('from')->andReturnSelf();
        });
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereNotExistsAddsToWhereQueries()
    {
        $this->query->shouldReceive('createSub')
            ->andReturn(array('SELECT 1 FROM `orders`', array()));

        $self = $this;
        $this->query->whereNotExists(function ($q) use ($self) {
            $q->shouldReceive('from')->andReturnSelf();
        });
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testOrWhereExistsAddsToWhereQueries()
    {
        $this->query->shouldReceive('createSub')
            ->andReturn(array('SELECT 1 FROM `orders`', array()));

        $self = $this;
        $this->query->orWhereExists(function ($q) use ($self) {
            $q->shouldReceive('from')->andReturnSelf();
        });
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    public function testWhereLikeAddsToWhereQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->where('name', 'like', '%john%');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['wheres']['queries']);
    }

    // =========================================================================
    // JOIN 系列
    // =========================================================================

    public function testJoinAddsToJoinQueries()
    {
        $this->query->join('orders', 'users.id', '=', 'orders.user_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['joins']['queries']);
    }

    public function testLeftJoinAddsToJoinQueries()
    {
        $this->query->leftJoin('orders', 'users.id', '=', 'orders.user_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['joins']['queries']);
    }

    public function testRightJoinAddsToJoinQueries()
    {
        $this->query->rightJoin('orders', 'users.id', '=', 'orders.user_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['joins']['queries']);
    }

    public function testCrossJoinAddsToJoinQueries()
    {
        $this->query->crossJoin('tags', 'users.id', '=', 'tags.user_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['joins']['queries']);
    }

    public function testJoinWithTwoColumnArgsDefaultsToEquals()
    {
        $this->query->join('orders', 'users.id', 'orders.user_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['joins']['queries']);
    }

    // =========================================================================
    // ORDER BY 系列
    // =========================================================================

    public function testOrderByRawAddsToOrderQueries()
    {
        $this->query->orderByRaw('FIELD(id, 1, 2, 3)');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['orders']['queries']);
    }

    public function testOrderByAddsToOrderQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orderBy('created_at', 'desc');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['orders']['queries']);
    }

    public function testOrderByDescAddsToOrderQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orderByDesc('updated_at');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['orders']['queries']);
    }

    public function testOrderByAscAddsToOrderQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orderByAsc('name');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['orders']['queries']);
    }

    // =========================================================================
    // GROUP BY 系列
    // =========================================================================

    public function testGroupByRawAddsToGroupQueries()
    {
        $this->query->groupByRaw('YEAR(created_at)');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['groups']['queries']);
    }

    public function testGroupByAddsToGroupQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->groupBy('category_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['groups']['queries']);
    }

    public function testGroupByDescAddsToGroupQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->groupByDesc('created_at');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['groups']['queries']);
    }

    public function testGroupByAscAddsToGroupQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->groupByAsc('category_id');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['groups']['queries']);
    }

    // =========================================================================
    // HAVING 系列
    // =========================================================================

    public function testHavingRawAddsToHavingQueries()
    {
        $this->query->havingRaw('count(*) > 1');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['havings']['queries']);
    }

    public function testOrHavingRawAddsToHavingQueries()
    {
        $this->query->orHavingRaw('count(*) > 1');
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['havings']['queries']);
    }

    public function testHavingAddsToHavingQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->having('count', '>', 5);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['havings']['queries']);
    }

    public function testOrHavingAddsToHavingQueries()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });

        $this->query->orHaving('count', '>', 5);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['havings']['queries']);
    }

    // =========================================================================
    // LIMIT / OFFSET
    // =========================================================================

    public function testLimitSetsLimitQuery()
    {
        $this->query->limit(10);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['limits']);
    }

    public function testOffsetSetsOffsetQuery()
    {
        $this->query->offset(20);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertArrayHasKey('offset', $queries);
    }

    public function testLimitWithOffsetSetsBoth()
    {
        $this->query->limit(10, 20);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['limits']);
        $this->assertCount(2, $queries['limits']['queries']);
    }

    // =========================================================================
    // LOCK
    // =========================================================================

    public function testLockForUpdateSetsLockQuery()
    {
        $this->query->lockForUpdate();
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertEquals('lockForUpdate', $queries['lock']);
    }

    public function testSharedLockSetsLockQuery()
    {
        $this->query->sharedLock();
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertEquals('sharedLock', $queries['lock']);
    }

    // =========================================================================
    // UNION
    // =========================================================================

    public function testUnionAddsToUnionQueries()
    {
        $this->query->shouldReceive('createSub')
            ->andReturn(array('SELECT * FROM `archived_users`', array()));

        $sub = Mockery::mock('Wilkques\Database\Queries\Builder');
        $this->query->union($sub);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['unions']['queries']);
    }

    public function testUnionAllAddsToUnionQueries()
    {
        $this->query->shouldReceive('createSub')
            ->andReturn(array('SELECT * FROM `archived_users`', array()));

        $sub = Mockery::mock('Wilkques\Database\Queries\Builder');
        $this->query->unionAll($sub);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['unions']['queries']);
    }

    // =========================================================================
    // CRUD 執行
    // =========================================================================

    public function testGetFetchesAllRecords()
    {
        $expected = array(array('id' => 1, 'name' => 'Alice'));

        $mockResult = Mockery::mock('result');
        $mockResult->shouldReceive('fetchAll')->andReturn($expected);

        $this->query->shouldReceive('select')->andReturnSelf();
        $this->query->shouldReceive('toSql')->andReturn('SELECT * FROM `users`');
        $this->query->shouldReceive('getBindings')->andReturn(array());
        $this->query->shouldReceive('getQuery')->andReturn(null);
        $this->query->shouldReceive('getConnection')->andReturn($this->connection);

        $this->connection->shouldReceive('exec')->andReturn($mockResult);

        $result = $this->query->get();
        $this->assertEquals($expected, $result);
    }

    public function testDeleteReturnsRowCount()
    {
        $mockResult = Mockery::mock('result');
        $mockResult->shouldReceive('rowCount')->andReturn(3);

        $this->query->shouldReceive('getConnection')->andReturn($this->connection);
        $this->query->shouldReceive('getGrammar')->andReturn($this->grammar);
        $this->query->shouldReceive('getBindings')->andReturn(array());

        $this->grammar->shouldReceive('compilerDelete')->andReturn('DELETE FROM `users`');
        $this->connection->shouldReceive('exec')->andReturn($mockResult);

        $result = $this->query->delete();
        $this->assertEquals(3, $result);
    }

    public function testCountReturnsAggregateValue()
    {
        $mockResult = Mockery::mock('result');
        $mockResult->shouldReceive('fetch')->andReturn(array('aggregate' => 42));

        $this->query->shouldReceive('getConnection')->andReturn($this->connection);
        $this->query->shouldReceive('getGrammar')->andReturn($this->grammar);
        $this->query->shouldReceive('getBindings')->andReturn(array());

        $this->grammar->shouldReceive('compilerCount')->andReturn('SELECT COUNT(*) AS aggregate FROM `users`');
        $this->connection->shouldReceive('exec')->andReturn($mockResult);

        $result = $this->query->count();
        $this->assertEquals(42, $result);
    }

    public function testUpdateReturnsRowCount()
    {
        $mockResult = Mockery::mock('result');
        $mockResult->shouldReceive('rowCount')->andReturn(1);

        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });
        $this->query->shouldReceive('getConnection')->andReturn($this->connection);
        $this->query->shouldReceive('getGrammar')->andReturn($this->grammar);
        $this->query->shouldReceive('getBindings')->andReturn(array());
        $this->query->shouldReceive('addBinding')->andReturnSelf();

        $this->grammar->shouldReceive('compilerUpdate')->andReturn('UPDATE `users` SET `name` = ?');
        $this->connection->shouldReceive('exec')->andReturn($mockResult);

        $result = $this->query->update(array('name' => 'Bob'));
        $this->assertEquals(1, $result);
    }

    public function testInsertReturnsRowCount()
    {
        $mockResult = Mockery::mock('result');
        $mockResult->shouldReceive('rowCount')->andReturn(1);

        $this->query->shouldReceive('bindingsNested')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return array_values($v); });
        $this->query->shouldReceive('getConnection')->andReturn($this->connection);
        $this->query->shouldReceive('getGrammar')->andReturn($this->grammar);
        $this->query->shouldReceive('getBindings')->andReturn(array());
        $this->query->shouldReceive('addBinding')->andReturnSelf();

        $this->grammar->shouldReceive('compilerInsert')->andReturn('INSERT INTO `users` (`name`) VALUES (?)');
        $this->connection->shouldReceive('exec')->andReturn($mockResult);

        $result = $this->query->insert(array(array('name' => 'Alice')));
        $this->assertEquals(1, $result);
    }

    public function testSoftDeleteCallsUpdateWithTimestamp()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });
        $this->query->shouldReceive('update')->once()->andReturn(1);

        $result = $this->query->softDelete();
        $this->assertEquals(1, $result);
    }

    public function testReStoreCallsUpdateWithNull()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });
        $this->query->shouldReceive('update')->once()->andReturn(1);

        $result = $this->query->reStore();
        $this->assertEquals(1, $result);
    }

    public function testReStoreWithCustomValueCallsUpdate()
    {
        $this->query->shouldReceive('update')->once()->andReturn(1);

        $result = $this->query->reStore('deleted_at', '2024-01-01 00:00:00');
        $this->assertEquals(1, $result);
    }

    public function testReStoreThrowsOnNonStringColumn()
    {
        $caught = false;
        try {
            $this->query->reStore(array('deleted_at'));
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }
        $this->assertTrue($caught, 'Expected InvalidArgumentException was not thrown');
    }

    public function testIncrementCallsUpdateWithFormula()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });
        $this->query->shouldReceive('addBinding')->andReturnSelf();
        $this->query->shouldReceive('update')->once()->andReturn(1);

        $result = $this->query->increment('views');
        $this->assertEquals(1, $result);
    }

    public function testDecrementCallsUpdateWithFormula()
    {
        $this->query->shouldReceive('contactBacktick')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($v) { return "`{$v}`"; });
        $this->query->shouldReceive('addBinding')->andReturnSelf();
        $this->query->shouldReceive('update')->once()->andReturn(1);

        $result = $this->query->decrement('stock', 2);
        $this->assertEquals(1, $result);
    }

    public function testIncrementThrowsOnNonNumericAmount()
    {
        $caught = false;
        try {
            $this->query->increment('views', 'notanumber');
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }
        $this->assertTrue($caught, 'Expected InvalidArgumentException was not thrown');
    }

    // =========================================================================
    // getForPage / prePage / currentPage
    // =========================================================================

    public function testPrePageSetsLimitQuery()
    {
        $this->query->prePage(15);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertNotEmpty($queries['limits']);
    }

    public function testCurrentPageSetsOffsetQuery()
    {
        $this->query->currentPage(3);
        $queries = $this->getProtectedProperty($this->query, 'queries');
        $this->assertArrayHasKey('offset', $queries);
    }
}
