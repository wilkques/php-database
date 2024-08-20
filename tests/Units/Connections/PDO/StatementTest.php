<?php

ini_set('xdebug.max_nesting_level', 500);

namespace Wilkques\Database\Tests\Units\Connections\PDO\Drivers;

use Mockery;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;
use Wilkques\Database\Connections\PDO\Statement;
use Wilkques\Helpers\Strings;

class StatementTest extends TestCase
{
    /** @var Statement */
    private $statement;

    /** @var MockObject|PDOStatement */
    private $pdoStatement;

    /** @var MockObject|Connections */
    private $connections;

    private function runDatabase($callback)
    {
        $this->pdoStatement = Mockery::mock('PDOStatement');

        $this->connections = Mockery::mock('Wilkques\Database\Connections\PDO\Drivers\MySql');

        $this->statement = new Statement($this->pdoStatement, $this->connections);

        call_user_func($callback, $this);

        Mockery::close();
    }

    public function testGetStatement()
    {
        $this->runDatabase(function ($statementTest) {
            $this->assertTrue(
                $statementTest->statement->getStatement() instanceof PDOStatement
            );
        });
    }

    public function testSetStatement()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->statement->setStatement($statementTest->pdoStatement);

            $statementTest->assertTrue(
                $statementTest->statement->getStatement() instanceof PDOStatement
            );
        });
    }

    public function testGetConnections()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->assertTrue(
                $statementTest->statement->getConnections() instanceof MySql
            );
        });
    }

    public function testSetConnections()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->statement->setConnections($statementTest->connections);

            $statementTest->assertTrue(
                $statementTest->statement->getConnections() instanceof MySql
            );
        });
    }

    public function testGetDebug()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->assertFalse(
                $statementTest->statement->getDebug()
            );
        });
    }

    public function testDebug()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->statement->debug();

            $statementTest->assertTrue(
                $statementTest->statement->getDebug()
            );
        });
    }

    public function testGetParam()
    {
        $this->runDatabase(function ($statementTest) {
            try {
                $statementTest->statement->getParam('abc');
            } catch (\Exception $e) {
                $statementTest->assertEquals(
                    'Undefined index: abc',
                    $e->getMessage()
                );
            }
        });
    }

    public function testSetParam()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->statement->setParam('abc', 1);

            $statementTest->assertNotEmpty(
                $statementTest->statement->getParam('abc')
            );
        });
    }

    public function testGetParams()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->assertEmpty(
                $statementTest->statement->getParams()
            );
        });
    }

    public function testSetParams()
    {
        $this->runDatabase(function ($statementTest) {
            $statementTest->statement->setParams(array('abc' => 1));

            $statementTest->assertNotEmpty(
                $statementTest->statement->getParams()
            );
        });
    }

    public function testBindVarsType()
    {
        $this->runDatabase(function ($statementTest) {
            $statement = new ReflectionMethod($statementTest->statement, 'bindVarsType');

            $statement->setAccessible(true);

            $type = $statement->invoke($statementTest->statement, true);

            $statementTest->assertTrue(
                $type == \PDO::PARAM_BOOL
            );

            $type = $statement->invoke($statementTest->statement, 1);

            $statementTest->assertTrue(
                $type == \PDO::PARAM_INT
            );

            $type = $statement->invoke($statementTest->statement, NULL);

            $statementTest->assertTrue(
                $type == \PDO::PARAM_NULL
            );

            $type = $statement->invoke($statementTest->statement, 'abc');

            $statementTest->assertTrue(
                $type == \PDO::PARAM_STR
            );

            $type = $statement->invoke($statementTest->statement, Strings::rand(1000000));

            $statementTest->assertTrue(
                $type == \PDO::PARAM_LOB
            );
        });
    }

    public function testBindParam()
    {
        $this->runDatabase(function ($statementTest) {
            $param = 'abc';

            $value = 1;

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->once()
                ->with($param, $value, \PDO::PARAM_INT);

            $statementTest->statement->bindParam($param, $value);
        });
    }

    public function testBindParamWithDefaultVarsType()
    {
        $this->runDatabase(function ($statementTest) {
            $param = 'abc';

            $value = 1;

            $bindVarsType = new ReflectionMethod($statementTest->statement, 'bindVarsType');

            $bindVarsType->setAccessible(true);

            $defaultType = $bindVarsType->invoke($statementTest->statement, $value);

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->once()
                ->with($param, $value, \PDO::PARAM_INT);

            $statementTest->statement->bindParam($param, $value, $defaultType);
        });
    }

    public function testBindValue()
    {
        $this->runDatabase(function ($statementTest) {
            $param = 'abc';

            $value = 1;

            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->once()
                ->with($param, $value, \PDO::PARAM_INT);

            $statementTest->statement->bindValue($param, $value);
        });
    }

    public function testBindValueWithDefaultVarsType()
    {
        $this->runDatabase(function ($statementTest) {
            $param = 'param1';

            $value = 1;

            $bindVarsType = new ReflectionMethod($statementTest->statement, 'bindVarsType');

            $bindVarsType->setAccessible(true);

            $defaultType = $bindVarsType->invoke($statementTest->statement, $value);

            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->once()
                ->with($param, $value, \PDO::PARAM_INT);

            $statementTest->statement->bindValue($param, $value, $defaultType);
        });
    }

    public function testBindingWithSimpleArray()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                'param1' => 'value1',
                'param2' => 'value2'
            );

            // Set up expectations for bindParam method
            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call the binding method with a callback
            $statementTest->statement->binding('bindParam', $params, function ($params) {
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
        });
    }

    public function testBindingWithArrayOfArrays()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                array('param1' => 'value1'),
                array('param2' => 'value2'),
            );

            // Expected to be called with the parameters in bindParam
            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call the binding method with a callback
            $statementTest->statement->binding('bindParam', $params, function ($params) {
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
        });
    }

    public function testBindParamsWithSimpleArray()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                'param1' => 'value1',
                'param2' => 'value2',
            );

            // Expected to be called with the parameters in bindParam
            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call bindParams with a simple associative array
            $statementTest->statement->bindParams($params);
        });
    }

    public function testBindParamsWithArrayOfArrays()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                array('param1' => 'value1'),
                array('param2' => 'value2'),
            );

            // Expected to be called with the parameters in bindParam
            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindParam')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call bindParams with an array of arrays
            $statementTest->statement->bindParams($params);
        });
    }

    public function testBindValuesWithSimpleArray()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                'param1' => 'value1',
                'param2' => 'value2',
            );

            // Expected to be called with the parameters in bindValue
            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call bindParams with a simple associative array
            $statementTest->statement->bindValues($params);
        });
    }

    public function testBindValuesWithArrayOfArrays()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array(
                array('param1' => 'value1'),
                array('param2' => 'value2'),
            );

            // Expected to be called with the parameters in bindValue
            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->with(1, 'value1', \PDO::PARAM_STR)
                ->once();

            $statementTest->pdoStatement->shouldReceive('bindValue')
                ->with(2, 'value2', \PDO::PARAM_STR)
                ->once();

            // Call bindParams with an array of arrays
            $statementTest->statement->bindValues($params);
        });
    }

    public function testExecuteWithoutParams()
    {
        $this->runDatabase(function ($statementTest) {
            // Set up the expectation for execute method
            $statementTest->pdoStatement->shouldReceive('execute')->once();

            // Set up the expectation for debugDumpParams method
            $statementTest->pdoStatement->shouldReceive('debugDumpParams')
                ->never();

            // Call the execute method
            $result = $statementTest->statement->execute();

            // Verify that the result is an instance of Result
            $statementTest->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
        });
    }

    public function testExecuteWithParams()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array('param1' => 'value1');

            // Set up the expectation for execute method
            $statementTest->pdoStatement->shouldReceive('execute')->once();

            // Set up the expectation for debugDumpParams method
            $statementTest->pdoStatement->shouldReceive('debugDumpParams')
                ->never();

            // Call the execute method with parameters
            $result = $statementTest->statement->execute($params);

            // Verify that the result is an instance of Result
            $statementTest->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
        });
    }

    public function testExecuteWithDebugMode()
    {
        $this->runDatabase(function ($statementTest) {
            $params = array('param1' => 'value1');

            // Enable debug mode
            $statementTest->statement->debug(true);

            // Set up the expectation for debugDumpParams method
            $statementTest->pdoStatement->shouldReceive('debugDumpParams')
                ->once();

            // Set up the expectation for execute method
            $statementTest->pdoStatement->shouldReceive('execute')
                ->with(Mockery::on(function ($param) use ($params) {
                    return $param === $params; // Ensure the parameter matches $params
                }))
                ->once();

            // Call the execute method with parameters
            $result = $statementTest->statement->execute($params);

            // Verify that the result is an instance of Result
            $statementTest->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
        });
    }

    public function testMagicCallDebugMethod()
    {
        $mysql = Mockery::mock('Wilkques\Database\Connections\PDO\Statement')->makePartial();

        // Set expectation for setDebug method
        $mysql->shouldReceive('setDebug')
            ->with(true)
            ->once();

        // Call the magic method __call
        $mysql->debug(true);
    }

    public function testMagicCallParamsMethod()
    {
        $mysql = Mockery::mock('Wilkques\Database\Connections\PDO\Statement')->makePartial();

        // Expect setParams to be called with an array
        $mysql->shouldReceive('setParams')
            ->with(array('param1' => 'value1'))
            ->once();

        // Call the magic method __call
        $mysql->params(array('param1' => 'value1'));
    }

    public function testMagicCallNonExistentMethod()
    {
        $this->runDatabase(function ($statementTest) {
            // Call the magic method __call with a method that does not exist
            $result = $statementTest->statement->nonExistentMethod();

            // Ensure the result is null because non-existent methods are not handled
            $statementTest->assertNull($result);
        });
    }
}
