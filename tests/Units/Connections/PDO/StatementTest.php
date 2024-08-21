<?php

namespace Wilkques\Database\Tests\Units\Connections\PDO;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PDOStatement;
use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;
use Wilkques\Database\Connections\PDO\Statement;
use Wilkques\Helpers\Strings;

class StatementTest extends MockeryTestCase
{
    /** @var Statement */
    protected $statement;

    /** @var MockObject|PDOStatement */
    protected $pdoStatement;

    /** @var MockObject|Connections */
    protected $connections;

    public function testGetStatement()
    {
        $this->assertTrue(
            $this->statement->getStatement() instanceof PDOStatement
        );
    }

    public function testSetStatement()
    {
        $this->statement->setStatement($this->pdoStatement);

        $this->assertTrue(
            $this->statement->getStatement() instanceof PDOStatement
        );
    }

    public function testGetConnections()
    {
        $this->assertTrue(
            $this->statement->getConnections() instanceof MySql
        );
    }

    public function testSetConnections()
    {
        $this->statement->setConnections($this->connections);

        $this->assertTrue(
            $this->statement->getConnections() instanceof MySql
        );
    }

    public function testGetDebug()
    {
        $this->assertFalse(
            $this->statement->getDebug()
        );
    }

    public function testDebug()
    {
        $this->statement->setDebug();

        $this->assertTrue(
            $this->statement->getDebug()
        );
    }

    public function testGetParam()
    {
        try {
            $this->statement->getParam('abc');
        } catch (\Exception $e) {
            if (version_compare(PHP_VERSION, '8.0', '>=') && version_compare(PHP_VERSION, '8.1', '<')) {
                $errorMessage = 'Undefined array key "abc"';
            } else {
                $errorMessage = 'Undefined index: abc';
            }

            $this->expectExceptionMessage($errorMessage);
        }
    }

    public function testSetParam()
    {
        $this->statement->setParam('abc', 1);

        $this->assertNotEmpty(
            $this->statement->getParam('abc')
        );
    }

    public function testGetParams()
    {
        $this->assertEmpty(
            $this->statement->getParams()
        );
    }

    public function testSetParams()
    {
        $this->statement->setParams(array('abc' => 1));

        $this->assertNotEmpty(
            $this->statement->getParams()
        );
    }

    public function testBindVarsType()
    {
        $statement = new ReflectionMethod($this->statement, 'bindVarsType');

        $statement->setAccessible(true);

        $type = $statement->invoke($this->statement, true);

        $this->assertTrue(
            $type == \PDO::PARAM_BOOL
        );

        $type = $statement->invoke($this->statement, 1);

        $this->assertTrue(
            $type == \PDO::PARAM_INT
        );

        $type = $statement->invoke($this->statement, NULL);

        $this->assertTrue(
            $type == \PDO::PARAM_NULL
        );

        $type = $statement->invoke($this->statement, 'abc');

        $this->assertTrue(
            $type == \PDO::PARAM_STR
        );

        $type = $statement->invoke($this->statement, Strings::rand(1000000));

        $this->assertTrue(
            $type == \PDO::PARAM_LOB
        );
    }

    public function testBindParam()
    {
        $param = 'abc';

        $value = 1;

        $this->pdoStatement->shouldReceive('bindParam')
            ->once()
            ->with($param, $value, \PDO::PARAM_INT);

        $this->statement->bindParam($param, $value);
    }

    public function testBindParamWithDefaultVarsType()
    {
        $param = 'abc';

        $value = 1;

        $bindVarsType = new ReflectionMethod($this->statement, 'bindVarsType');

        $bindVarsType->setAccessible(true);

        $defaultType = $bindVarsType->invoke($this->statement, $value);

        $this->pdoStatement->shouldReceive('bindParam')
            ->once()
            ->with($param, $value, \PDO::PARAM_INT);

        $this->statement->bindParam($param, $value, $defaultType);
    }

    public function testBindValue()
    {
        $param = 'abc';

        $value = 1;

        $this->pdoStatement->shouldReceive('bindValue')
            ->once()
            ->with($param, $value, \PDO::PARAM_INT);

        $this->statement->bindValue($param, $value);
    }

    public function testBindValueWithDefaultVarsType()
    {
        $param = 'param1';

        $value = 1;

        $bindVarsType = new ReflectionMethod($this->statement, 'bindVarsType');

        $bindVarsType->setAccessible(true);

        $defaultType = $bindVarsType->invoke($this->statement, $value);

        $this->pdoStatement->shouldReceive('bindValue')
            ->once()
            ->with($param, $value, \PDO::PARAM_INT);

        $this->statement->bindValue($param, $value, $defaultType);
    }

    public function testBindingWithSimpleArray()
    {
        $params = array(
            'param1' => 'value1',
            'param2' => 'value2'
        );

        // Set up expectations for bindParam method
        $this->pdoStatement->shouldReceive('bindParam')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindParam')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call the binding method with a callback
        $this->statement->binding('bindParam', $params, function ($params) {
            $newParams = array();

            foreach ($params as $item) {
                if (is_array($item)) {
                    $newParams = array_merge($newParams, $item);
                } else {
                    array_push($newParams, $item);
                }
            }

            return $newParams;
        });
    }

    public function testBindingWithArrayOfArrays()
    {
        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Expected to be called with the parameters in bindParam
        $this->pdoStatement->shouldReceive('bindParam')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindParam')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call the binding method with a callback
        $this->statement->binding('bindParam', $params, function ($params) {
            $newParams = array();

            foreach ($params as $item) {
                if (is_array($item)) {
                    $item = array_values($item);

                    $newParams = array_merge($newParams, $item);
                } else {
                    array_push($newParams, $item);
                }
            }

            return $newParams;
        });
    }

    public function testBindParamsWithSimpleArray()
    {
        $params = array(
            'param1' => 'value1',
            'param2' => 'value2',
        );

        // Expected to be called with the parameters in bindParam
        $this->pdoStatement->shouldReceive('bindParam')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindParam')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call bindParams with a simple associative array
        $this->statement->bindParams($params);
    }

    public function testBindParamsWithArrayOfArrays()
    {
        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Expected to be called with the parameters in bindParam
        $this->pdoStatement->shouldReceive('bindParam')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindParam')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call bindParams with an array of arrays
        $this->statement->bindParams($params);
    }

    public function testBindValuesWithSimpleArray()
    {
        $params = array(
            'param1' => 'value1',
            'param2' => 'value2',
        );

        // Expected to be called with the parameters in bindValue
        $this->pdoStatement->shouldReceive('bindValue')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindValue')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call bindParams with a simple associative array
        $this->statement->bindValues($params);
    }

    public function testBindValuesWithArrayOfArrays()
    {
        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Expected to be called with the parameters in bindValue
        $this->pdoStatement->shouldReceive('bindValue')
            ->with(1, 'value1', \PDO::PARAM_STR)
            ->once();

        $this->pdoStatement->shouldReceive('bindValue')
            ->with(2, 'value2', \PDO::PARAM_STR)
            ->once();

        // Call bindParams with an array of arrays
        $this->statement->bindValues($params);
    }

    public function testExecuteWithoutParams()
    {
        // Set up the expectation for execute method
        $this->pdoStatement->shouldReceive('execute')->once();

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->shouldReceive('debugDumpParams')
            ->never();

        // Call the execute method
        $result = $this->statement->execute();

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testExecuteWithParams()
    {
        $params = array('param1' => 'value1');

        // Set up the expectation for execute method
        $this->pdoStatement->shouldReceive('execute')->once();

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->shouldReceive('debugDumpParams')
            ->never();

        // Call the execute method with parameters
        $result = $this->statement->execute($params);

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testExecuteWithDebugMode()
    {
        $params = array('param1' => 'value1');

        // Enable debug mode
        $this->statement->debug(true);

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->shouldReceive('debugDumpParams')
            ->once();

        // Set up the expectation for execute method
        $this->pdoStatement->shouldReceive('execute')
            ->with(Mockery::on(function ($param) use ($params) {
                return $param === $params; // Ensure the parameter matches $params
            }))
            ->once();

        // Call the execute method with parameters
        $result = $this->statement->execute($params);

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testMagicCallDebugMethod()
    {
        $this->statement->debug();

        $this->assertTrue($this->statement->getDebug());
    }

    public function testMagicCallParamsMethod()
    {
        $this->statement->params(array('param1' => 'value1'));

        $this->assertEquals(
            array('param1' => 'value1'),
            $this->statement->getParams()
        );
    }

    public function testMagicCallNonExistentMethod()
    {
        try {
            // Call the magic method __call with a method that does not exist
            $this->statement->nonExistentMethod();
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Method: nonExistentMethod Not exists',
                $e->getMessage()
            );
        }
    }
}
