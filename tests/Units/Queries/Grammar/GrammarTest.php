<?php

namespace Wilkques\Database\Tests\Units\Queries\Grammar;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Wilkques\Database\Queries\Expression;

class GrammarTest extends MockeryTestCase
{
    protected $grammar;

    protected $query;

    public function testArrayNestedWithExpressions()
    {
        $expression = new Expression('NOW()');

        $array = array($expression, 'name', 'age');

        $result = $this->grammar->arrayNested($array);

        $expected = array('NOW()', 'name', 'age');

        $this->assertEquals($expected, $result);
    }

    public function testArrayNestedWithCallback()
    {
        $array = array('name', 'age');

        $callback = function ($value) {
            return strtoupper($value);
        };

        $result = $this->grammar->arrayNested($array, $callback);

        $expected = array('NAME', 'AGE');

        $this->assertEquals($expected, $result);
    }

    public function testArrayNestedWithForceValue()
    {
        $array = array('name', 'age');

        $forceValue = 'default';

        $result = $this->grammar->arrayNested($array, $forceValue);

        $expected = array('default', 'default');

        $this->assertEquals($expected, $result);
    }

    public function testCompilerColumns()
    {
        $this->query->shouldReceive('getQuery')
            ->once()
            ->andReturn(array('id', 'name'));

        $sql = $this->grammar->compilerColumns($this->query);

        $this->assertEquals('id, name', $sql);
    }

    public function testCompilerSelect()
    {
        $this->query->shouldReceive('getQuery')
            ->once()
            ->with('columns.queries', array('*'))
            ->andReturn(array('id', 'name'));

        $sql = $this->grammar->compilerSelect($this->query);

        $this->assertStringMatchesFormat('SELECT id, name', $sql);
    }

    public function testCompilerFroms()
    {
        $this->query->shouldReceive('getQuery')
            ->once()
            ->andReturn(array('users'));

        $sql = $this->grammar->compilerFroms($this->query);

        $this->assertEquals('FROM users', $sql);
    }

    public function testCompilerWheres()
    {
        $this->query->shouldReceive('getQuery')
            ->once()
            ->andReturn(array('AND id = 1'));

        $sql = $this->grammar->compilerWheres($this->query);

        $this->assertEquals('WHERE id = 1', $sql);
    }

    public function testCompilerHavingsWithValues()
    {
        $this->query->shouldReceive('getQuery')
            ->with('havings.queries', array())
            ->andReturn(array('AND SUM(amount) > 100', 'AVG(price) < 50'));

        $this->query->shouldReceive('firstJoinReplace')
            ->with('SUM(amount) > 100 AVG(price) < 50')
            ->andReturn('SUM(amount) > 100 AVG(price) < 50');

        $result = $this->grammar->compilerHavings($this->query);

        $expected = 'HAVING SUM(amount) > 100 AVG(price) < 50';

        $this->assertEquals($expected, $result);
    }

    public function testCompilerHavingsWithEmptyValues()
    {
        $this->query->shouldReceive('getQuery')
            ->with('havings.queries', array())
            ->andReturn(array());

        $result = $this->grammar->compilerHavings($this->query);

        $this->assertFalse($result);
    }

    public function testCompilerLimitsWithValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('limits.queries', array())
            ->andReturn(array(10, 20));

        // Call the method under test
        $result = $this->grammar->compilerLimits($this->query);

        // Define the expected output
        $expected = 'LIMIT 10, 20';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerLimitsWithEmptyValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('limits.queries', array())
            ->andReturn(array());

        // Call the method under test
        $result = $this->grammar->compilerLimits($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerGroupsWithValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('groups.queries', array())
            ->andReturn(array('name', 'age'));

        // Call the method under test
        $result = $this->grammar->compilerGroups($this->query);

        // Define the expected output
        $expected = 'GROUP BY name, age';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerGroupsWithEmptyValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('groups.queries', array())
            ->andReturn(array());

        // Call the method under test
        $result = $this->grammar->compilerGroups($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerOrdersWithValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('orders.queries', array())
            ->andReturn(array('name ASC', 'age DESC'));

        // Call the method under test
        $result = $this->grammar->compilerOrders($this->query);

        // Define the expected output
        $expected = 'ORDER BY name ASC, age DESC';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerOrdersWithEmptyValues()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('orders.queries', array())
            ->andReturn(array());

        // Call the method under test
        $result = $this->grammar->compilerOrders($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerOffsetWithNumericValue()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('offset.queries', false)
            ->andReturn(10);

        // Call the method under test
        $result = $this->grammar->compilerOffset($this->query);

        // Define the expected output
        $expected = 'OFFSET 10';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerOffsetWithExpression()
    {
        // Create an instance of Expression with a specific value
        $expression = new Expression(15);

        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('offset.queries', false)
            ->andReturn($expression);

        // Call the method under test
        $result = $this->grammar->compilerOffset($this->query);

        // Define the expected output
        $expected = 'OFFSET 15';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerOffsetWithFalseValue()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('offset.queries', false)
            ->andReturn(false);

        // Call the method under test
        $result = $this->grammar->compilerOffset($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerLockWithMethodName()
    {
        // Define a custom method in Grammar class for testing
        $this->grammar->shouldReceive('customLockMethod')
            ->andReturn('FOR UPDATE');

        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('lock', false)
            ->andReturn('customLockMethod');

        // Call the method under test
        $result = $this->grammar->compilerLock($this->query);

        // Define the expected output
        $expected = 'FOR UPDATE';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerLockWithFalseValue()
    {
        // Define the mock behavior for getQuery
        $this->query->shouldReceive('getQuery')
            ->with('lock', false)
            ->andReturn(false);

        // Call the method under test
        $result = $this->grammar->compilerLock($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerJoinsWithJoins()
    {
        // Define mock behavior for getQuery to return join strings
        $this->query->shouldReceive('getQuery')
            ->with('joins.queries', array())
            ->andReturn(array('JOIN users ON users.id = orders.user_id', 'JOIN products ON products.id = orders.product_id'));

        // Call the method under test
        $result = $this->grammar->compilerJoins($this->query);

        // Define the expected output
        $expected = 'JOIN users ON users.id = orders.user_id JOIN products ON products.id = orders.product_id';

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testCompilerJoinsWithoutJoins()
    {
        // Define mock behavior for getQuery to return an empty array
        $this->query->shouldReceive('getQuery')
            ->with('joins.queries', array())
            ->andReturn(array());

        // Call the method under test
        $result = $this->grammar->compilerJoins($this->query);

        // Assert the result matches the expected output
        $this->assertFalse($result);
    }

    public function testCompilerComponent()
    {
        // Mock `getQuery` for various components
        $this->query->shouldReceive('getQuery')
            ->with('columns', false)
            ->andReturn(array('id', 'name'));

        $this->query->shouldReceive('getQuery')
            ->with('froms', false)
            ->andReturn(array('users'));

        $this->query->shouldReceive('getQuery')
            ->with('joins', false)
            ->andReturn(array('JOIN orders ON orders.user_id = users.id'));

        $this->query->shouldReceive('getQuery')
            ->with('wheres', false)
            ->andReturn(array('status = active'));

        $this->query->shouldReceive('getQuery')
            ->with('groups', false)
            ->andReturn(array('department'));
        $this->query->shouldReceive('getQuery')
            ->with('havings', false)
            ->andReturn(array('COUNT(*) > 1'));

        $this->query->shouldReceive('getQuery')
            ->with('orders', false)
            ->andReturn(array('name DESC'));

        $this->query->shouldReceive('getQuery')
            ->with('limits', false)
            ->andReturn(array('10'));

        $this->query->shouldReceive('getQuery')
            ->with('offset', false)
            ->andReturn(array('20'));

        $this->query->shouldReceive('getQuery')
            ->with('lock', false)
            ->andReturn(false);

        // Mock the compiler methods
        $this->grammar->shouldReceive('compilerColumns')
            ->with($this->query)
            ->andReturn('id, name');

        $this->grammar->shouldReceive('compilerFroms')
            ->with($this->query)
            ->andReturn('FROM users');

        $this->grammar->shouldReceive('compilerJoins')
            ->with($this->query)
            ->andReturn('JOIN orders ON orders.user_id = users.id');

        $this->grammar->shouldReceive('compilerWheres')
            ->with($this->query)
            ->andReturn('WHERE status = active');

        $this->grammar->shouldReceive('compilerGroups')
            ->with($this->query)
            ->andReturn('GROUP BY department');

        $this->grammar->shouldReceive('compilerHavings')
            ->with($this->query)
            ->andReturn('HAVING COUNT(*) > 1');

        $this->grammar->shouldReceive('compilerOrders')
            ->with($this->query)
            ->andReturn('ORDER BY name DESC');

        $this->grammar->shouldReceive('compilerLimits')
            ->with($this->query)
            ->andReturn('LIMIT 10');

        $this->grammar->shouldReceive('compilerOffset')
            ->with($this->query)
            ->andReturn('OFFSET 20');

        // Call the method under test
        $result = $this->grammar->compilerComponent($this->query);

        // Define the expected output
        $expected = array(
            'columns' => 'id, name',
            'froms' => 'FROM users',
            'joins' => 'JOIN orders ON orders.user_id = users.id',
            'wheres' => 'WHERE status = active',
            'groups' => 'GROUP BY department',
            'havings' => 'HAVING COUNT(*) > 1',
            'orders' => 'ORDER BY name DESC',
            'limits' => 'LIMIT 10',
            'offset' => 'OFFSET 20',
        );

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    public function testConcatenateWithVariousSegments()
    {
        // Test with non-empty segments
        $segments = array('SELECT', 'id, name', 'FROM', 'users', '', 'WHERE', 'status = active');

        $result = $this->grammar->concatenate($segments);

        $expected = 'SELECT id, name FROM users WHERE status = active';

        $this->assertEquals($expected, $result);

        // Test with all empty segments
        $segments = array('', '', '', '');

        $result = $this->grammar->concatenate($segments);

        $this->assertEquals('', $result);

        // Test with some empty segments
        $segments = array('SELECT', '', 'FROM', 'users', '', 'WHERE', 'status = active');

        $result = $this->grammar->concatenate($segments);

        $expected = 'SELECT FROM users WHERE status = active';

        $this->assertEquals($expected, $result);

        // Test with a single segment
        $segments = array('SELECT * FROM users');

        $result = $this->grammar->concatenate($segments);

        $this->assertEquals('SELECT * FROM users', $result);
    }

    public function testCompilerUpdate()
    {
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('users'));

        $sql = $this->grammar->compilerUpdate($this->query, array('name'));

        $this->assertEquals("UPDATE users SET name = ? ", $sql);
    }

    public function testCompilerUpdateWithoutJoins()
    {
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('users'));

        $this->grammar->shouldReceive('compilerWheres')
            ->andReturn('WHERE id = ?');

        $result = $this->grammar->compilerUpdate($this->query, array('name', 'age'));

        $expected = "UPDATE users SET name = ?, age = ? WHERE id = ?";

        $this->assertEquals($expected, $result);
    }

    public function testCompilerUpdateWithJoins()
    {
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('users'));

        $this->query->shouldReceive('getQuery')
            ->with('joins')
            ->andReturn(array(1));

        $this->query->shouldReceive('getQuery')
            ->with('joins.queries', array())
            ->andReturn(array('INNER JOIN roles ON users.role_id = roles.id'));

        $this->grammar->shouldReceive('compilerWheres')
            ->andReturn('WHERE id = ?');

        $result = $this->grammar->compilerUpdate($this->query, array('name', 'age'));

        $expected = "UPDATE users INNER JOIN roles ON users.role_id = roles.id SET name = ?, age = ? WHERE id = ?";

        $this->assertEquals($expected, $result);
    }

    public function testProtectedCompilerUpdateWithoutJoins()
    {
        // Mock the compilerWheres method
        $this->grammar->shouldReceive('compilerWheres')
            ->with($this->query)
            ->andReturn('WHERE status = ?');

        // Expected SQL
        $expected = 'UPDATE posts SET title = ?, status = ? WHERE status = ?';

        // Call the protected method using Reflection
        $reflection = new \ReflectionClass($this->grammar);

        $method = $reflection->getMethod('compilerUpdateWithoutJoins');

        $method->setAccessible(true);

        // Call the method
        $result = $method->invoke($this->grammar, $this->query, 'posts', 'title = ?, status = ?');

        $this->assertEquals($expected, $result);
    }

    public function testProtectedCompilerUpdateWithJoins()
    {
        $this->grammar->shouldReceive('compilerJoins')
            ->with($this->query)
            ->andReturn('JOIN comments ON posts.id = comments.post_id');

        // Mock the compilerWheres method
        $this->grammar->shouldReceive('compilerWheres')
            ->with($this->query)
            ->andReturn('WHERE status = ?');

        // Sample columns
        $columns = 'title = ?, status = ?';

        // Expected SQL
        $expected = 'UPDATE posts JOIN comments ON posts.id = comments.post_id SET title = ?, status = ? WHERE status = ?';

        // Call the protected method using Reflection
        $reflection = new \ReflectionClass($this->grammar);

        $method = $reflection->getMethod('compilerUpdateWithJoins');

        $method->setAccessible(true);

        // Call the method
        $result = $method->invoke($this->grammar, $this->query, 'posts', $columns);

        $this->assertEquals($expected, $result);
    }

    public function testCompilerUnions()
    {
        // Mock the getQuery method to return UNION clauses
        $this->query->shouldReceive('getQuery')
            ->with('unions.queries', array())
            ->andReturn(array('SELECT * FROM posts', 'SELECT * FROM comments'));

        // Mock the arrayNested method if needed
        $this->grammar->shouldReceive('arrayNested')
            ->with(array('SELECT * FROM posts', 'SELECT * FROM comments'))
            ->andReturn(array('SELECT * FROM posts', 'SELECT * FROM comments'));

        // Expected SQL
        $expected = 'SELECT * FROM posts SELECT * FROM comments';

        // Call the method
        $result = $this->grammar->compilerUnions($this->query);

        $this->assertEquals($expected, $result);
    }

    public function testCompilerInsertWithData()
    {
        // Mock getFrom method
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('posts'));

        // Mock contactBacktick method
        $this->query->shouldReceive('contactBacktick')
            ->with(array('title'))
            ->andReturn('`title`')->passthru();

        $this->query->shouldReceive('contactBacktick')
            ->with('body')
            ->andReturn('`body`')->passthru();

        // Mock arrayNested method
        $this->grammar->shouldReceive('arrayNested')
            ->with(array('Hello', 'World'), "?")
            ->andReturn(array('?', '?'));

        $this->grammar->shouldReceive('contactBacktick')
            ->with(array('title'))
            ->andReturn('`title`');

        // Expected SQL
        $expected = "INSERT INTO posts (`title`, `body`) VALUES (?, ?)";

        // Call the method
        $result = $this->grammar->compilerInsert($this->query, array(
            array('title' => 'Hello', 'body' => 'World')
        ));

        $this->assertEquals($expected, $result);
    }

    public function testCompilerInsertWithDefaultValues()
    {
        // Mock getFrom method
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('posts'));

        // Expected SQL
        $expected = "INSERT INTO posts DEFAULT VALUES";

        // Call the method
        $result = $this->grammar->compilerInsert($this->query, array());

        $this->assertEquals($expected, $result);
    }

    public function testCompilerInsertWithSubQuery()
    {
        // Mock getFrom method
        $this->query->shouldReceive('getFrom')
            ->andReturn(array('posts'));

        $sqlSubquery = "(SELECT title, body FROM temp)";

        // Call the method
        $result = $this->grammar->compilerInsert($this->query, array(
            array('title' => 'Hello', 'body' => 'World')
        ), $sqlSubquery);

        $this->assertEquals("INSERT INTO posts (`title`, `body`) {$sqlSubquery}", $result);
    }

    public function testProtectedCompilerInsertWithoutSubQuery()
    {
        // Sample data
        $from = 'posts';
        $columns = '`title`, `body`';
        $values = "'Hello', 'World'";

        // Expected SQL
        $expected = "INSERT INTO {$from} ({$columns}) VALUES ({$values})";

        // Call the method
        $result = $this->grammar->compilerInsertWithoutSubQuery($from, $columns, $values);

        $this->assertEquals($expected, $result);
    }

    public function testProtectedCompilerInsertWithSubQuery()
    {
        // Sample data
        $from = 'posts';
        $columns = '`title`, `body`';
        $sql = 'SELECT `title`, `body` FROM `temp_table`';

        // Expected SQL
        $expected = "INSERT INTO {$from} ({$columns}) {$sql}";

        // Call the method
        $result = $this->grammar->compilerInsertWithSubQuery($from, $columns, $sql);

        $this->assertEquals($expected, $result);
    }

    public function testCompilerDeleteWithJoins()
    {
        $this->query->shouldReceive('getQuery')
            ->with('joins')
            ->andReturn(array(1));

        $this->query->shouldReceive('getQuery')
            ->with('joins.queries', array())
            ->andReturn(array('INNER JOIN users ON posts.user_id = users.id'));

        $this->query->shouldReceive('getQuery')
            ->with('froms.queries', array())
            ->andReturn(array('posts'));

        $this->grammar->shouldReceive('compilerWheres')
            ->andReturn('WHERE status = "active"');

        $expected = 'DELETE FROM posts INNER JOIN users ON posts.user_id = users.id WHERE status = "active"';

        $result = $this->grammar->compilerDelete($this->query);

        $this->assertEquals($expected, $result);
    }

    public function testCompilerDeleteWithoutJoins()
    {
        $this->query->shouldReceive('getQuery')
            ->with('froms.queries', array())
            ->andReturn(array('posts'));

        $this->grammar->shouldReceive('compilerWheres')
            ->andReturn('WHERE status = "active"');

        $expected = 'DELETE FROM posts WHERE status = "active"';

        $result = $this->grammar->compilerDelete($this->query);

        $this->assertEquals($expected, $result);
    }

    public function testProtectedCompilerDeleteWithoutJoins()
    {
        // Mock the methods used within compilerDeleteWithoutJoins
        $this->grammar->shouldReceive('compilerFroms')
            ->with($this->query)
            ->andReturn('FROM posts');

        $this->grammar->shouldReceive('compilerWheres')
            ->with($this->query)
            ->andReturn('WHERE status = "inactive"');

        // Call the protected method directly using Reflection
        $method = new \ReflectionMethod($this->grammar, 'compilerDeleteWithoutJoins');

        $method->setAccessible(true);

        $result = $method->invoke($this->grammar, $this->query);

        $expected = 'DELETE FROM posts WHERE status = "inactive"';

        $this->assertEquals($expected, $result);
    }

    public function testProtectedCompilerDeleteWithJoins()
    {
        // Mock the methods used within compilerDeleteWithJoins
        $this->grammar->shouldReceive('compilerFroms')
            ->with($this->query)
            ->andReturn('FROM posts');

        $this->grammar->shouldReceive('compilerJoins')
            ->with($this->query)
            ->andReturn('JOIN users ON posts.user_id = users.id');

        $this->grammar->shouldReceive('compilerWheres')
            ->with($this->query)
            ->andReturn('WHERE status = "inactive"');

        // Call the protected method directly using Reflection
        $method = new \ReflectionMethod($this->grammar, 'compilerDeleteWithJoins');

        $method->setAccessible(true);

        $result = $method->invoke($this->grammar, $this->query);

        $expected = 'DELETE FROM posts JOIN users ON posts.user_id = users.id WHERE status = "inactive"';

        $this->assertEquals($expected, $result);
    }

    public function testCompilerCount()
    {
        // Mock the compilerSelect method
        $this->grammar->shouldReceive('compilerSelect')
            ->with($this->query)
            ->andReturn('SELECT * FROM posts WHERE status = "active"');

        // Call the method under test
        $result = $this->grammar->compilerCount($this->query);

        // Define the expected SQL
        $expected = 'SELECT COUNT(*) AS `aggregate` FROM (SELECT * FROM posts WHERE status = "active") AS `aggregate_table`';

        // Assert that the generated SQL matches the expected SQL
        $this->assertEquals($expected, $result);
    }

    public function testSupportsSavepoints()
    {
        // Assert that the supportsSavepoints method returns true
        $this->assertTrue($this->grammar->supportsSavepoints());
    }

    public function testCompileSavepoint()
    {
        $name = 'my_savepoint';

        // Assert that the compileSavepoint method returns the correct SQL string
        $this->assertEquals('SAVEPOINT my_savepoint', $this->grammar->compileSavepoint($name));
    }

    public function testCompileSavepointRollBack()
    {
        $name = 'my_savepoint';

        // Assert that the compileSavepointRollBack method returns the correct SQL string
        $this->assertEquals('ROLLBACK TO SAVEPOINT my_savepoint', $this->grammar->compileSavepointRollBack($name));
    }
}
