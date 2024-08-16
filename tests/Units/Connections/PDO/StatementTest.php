<?php

namespace Wilkques\Database\Tests\Units\Connections\PDO\Drivers;

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

    private function init()
    {
        $method = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        // Mock PDOStatement
        $this->pdoStatement = call_user_func(array($this, $method), 'PDOStatement');

        // Mock Connections
        $this->pdoStatement = call_user_func(array($this, $method), 'Wilkques\Database\Connections\PDO\Drivers\MySql');

        // Create the Statement instance
        $this->statement = new Statement($this->pdoStatement, $this->connections);
    }

    public function testGetStatement()
    {
        $this->init();

        $this->assertTrue(
            $this->statement->getStatement() instanceof PDOStatement
        );
    }

    public function testSetStatement()
    {
        $this->init();

        $this->statement->setStatement($this->pdoStatement);

        $this->assertTrue(
            $this->statement->getStatement() instanceof PDOStatement
        );
    }

    public function testGetConnections()
    {
        $this->init();

        $this->assertTrue(
            $this->statement->getConnections() instanceof MySql
        );
    }

    public function testSetConnections()
    {
        $this->init();

        $this->statement->setConnections($this->connections);

        $this->assertTrue(
            $this->statement->getConnections() instanceof MySql
        );
    }

    public function testGetDebug()
    {
        $this->init();

        $this->assertFalse(
            $this->statement->getDebug()
        );
    }

    public function testDebug()
    {
        $this->init();

        $this->statement->debug();

        $this->assertTrue(
            $this->statement->getDebug()
        );
    }

    public function testGetParam()
    {
        $this->init();

        try {
            $this->statement->getParam('abc');
        } catch (\Exception $e) {
            $this->assertEquals(
                'Undefined index: abc',
                $e->getMessage()
            );
        }
    }

    public function testSetParam()
    {
        $this->init();

        $this->statement->setParam('abc', 1);

        $this->assertNotEmpty(
            $this->statement->getParam('abc')
        );
    }

    public function testGetParams()
    {
        $this->init();

        $this->assertEmpty(
            $this->statement->getParams()
        );
    }

    public function testSetParams()
    {
        $this->init();

        $this->statement->setParams(array('abc' => 1));

        $this->assertNotEmpty(
            $this->statement->getParams()
        );
    }

    public function testBindVarsType()
    {
        $this->init();

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
        $this->init();

        $param = 'abc';

        $value = 1;

        $this->pdoStatement->expects($this->once())
            ->method('bindParam')
            ->with(
                $this->equalTo($param),
                $this->equalTo($value),
                $this->equalTo(1)
            );

        $this->statement->bindParam($param, $value);
    }

    public function testBindParamWithDefaultVarsType()
    {
        $this->init();

        $param = 'abc';

        $value = 1;

        $bindVarsType = new ReflectionMethod($this->statement, 'bindVarsType');

        $bindVarsType->setAccessible(true);

        $defaultType = $bindVarsType->invoke($this->statement, $value);

        $this->pdoStatement->expects($this->once())
            ->method('bindParam')
            ->with(
                $this->equalTo($param),
                $this->equalTo($value),
                $this->equalTo($defaultType)
            );

        $this->statement->bindParam($param, $value, $defaultType);
    }

    public function testBindValue()
    {
        $this->init();

        $param = 'abc';

        $value = 1;

        $this->pdoStatement->expects($this->once())
            ->method('bindValue')
            ->with(
                $this->equalTo($param),
                $this->equalTo($value),
                $this->equalTo(1)
            );

        $this->statement->bindValue($param, $value);
    }

    public function testBindValueWithDefaultVarsType()
    {
        $this->init();

        $param = 'param1';

        $value = 1;

        $bindVarsType = new ReflectionMethod($this->statement, 'bindVarsType');

        $bindVarsType->setAccessible(true);

        $defaultType = $bindVarsType->invoke($this->statement, $value);

        $this->pdoStatement->expects($this->once())
            ->method('bindValue')
            ->with(
                $this->equalTo($param),
                $this->equalTo($value),
                $this->equalTo($defaultType)
            );

        $this->statement->bindValue($param, $value, $defaultType);
    }

    public function testBindingWithSimpleArray()
    {
        $this->init();

        $params = array(
            'param1' => 'value1',
            'param2' => 'value2'
        );

        // Set up expectations for bindParam method
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindParam')
            ->withConsecutive(
                array($this->equalTo(1), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo(2), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

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
        $this->init();

        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Set up expectations for bindParam method
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindParam')
            ->withConsecutive(
                array($this->equalTo('param1'), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo('param2'), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

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

    public function testBindParamsWithSimpleArray()
    {
        $this->init();

        $params = array(
            'param1' => 'value1',
            'param2' => 'value2',
        );

        // Expected to be called with the parameters in bindValue
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindParam')
            ->withConsecutive(
                array($this->equalTo(1), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo(2), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

        // Call bindParams with a simple associative array
        $this->statement->bindParams($params);
    }

    public function testBindParamsWithArrayOfArrays()
    {
        $this->init();

        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Set up the expectation for bindParam
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindParam')
            ->withConsecutive(
                array($this->equalTo(1), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo(2), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

        // Call bindParams with an array of arrays
        $this->statement->bindParams($params);
    }

    public function testBindValuesWithSimpleArray()
    {
        $this->init();

        $params = array(
            'param1' => 'value1',
            'param2' => 'value2',
        );

        // Expected to be called with the parameters in bindValue
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                array($this->equalTo(1), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo(2), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

        // Call bindParams with a simple associative array
        $this->statement->bindValues($params);
    }

    public function testBindValuesWithArrayOfArrays()
    {
        $this->init();

        $params = array(
            array('param1' => 'value1'),
            array('param2' => 'value2'),
        );

        // Set up the expectation for bindParam
        $this->pdoStatement->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                array($this->equalTo(1), $this->equalTo('value1'), $this->equalTo(\PDO::PARAM_STR)),
                array($this->equalTo(2), $this->equalTo('value2'), $this->equalTo(\PDO::PARAM_STR))
            );

        // Call bindParams with an array of arrays
        $this->statement->bindValues($params);
    }

    public function testExecuteWithoutParams()
    {
        $this->init();

        // Set up the expectation for execute method
        $this->pdoStatement->expects($this->once())
            ->method('execute')
            ->with($this->isNull());

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->expects($this->never())
            ->method('debugDumpParams');

        // Call the execute method
        $result = $this->statement->execute();

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testExecuteWithParams()
    {
        $this->init();

        $params = array('param1' => 'value1');

        // Set up the expectation for execute method
        $this->pdoStatement->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($params));

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->expects($this->never())
            ->method('debugDumpParams');

        // Call the execute method with parameters
        $result = $this->statement->execute($params);

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testExecuteWithDebugMode()
    {
        $this->init();

        $params = array('param1' => 'value1');

        // Enable debug mode
        $this->statement->debug(true);

        // Set up the expectation for debugDumpParams method
        $this->pdoStatement->expects($this->once())
            ->method('debugDumpParams');

        // Set up the expectation for execute method
        $this->pdoStatement->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($params));

        // Call the execute method with parameters
        $result = $this->statement->execute($params);

        // Verify that the result is an instance of Result
        $this->assertInstanceOf('Wilkques\Database\Connections\PDO\Result', $result);
    }

    public function testMagicCallDebugMethod()
    {
        $this->statement = $this->getMockBuilder('Wilkques\Database\Connections\PDO\Statement')
            ->setMethods(array('setDebug'))
            ->disableOriginalConstructor()
            ->getMock();

        // Expect setDebug to be called with true
        $this->statement->expects($this->once())
            ->method('setDebug')
            ->with($this->equalTo(true));

        // Call the magic method __call
        $this->statement->debug(true);
    }

    public function testMagicCallParamsMethod()
    {
        $this->statement = $this->getMockBuilder('Wilkques\Database\Connections\PDO\Statement')
            ->setMethods(array('setParams'))
            ->disableOriginalConstructor()
            ->getMock();

        // Expect setParams to be called with an array
        $this->statement->expects($this->once())
            ->method('setParams')
            ->with($this->equalTo(array('param1' => 'value1')));

        // Call the magic method __call
        $this->statement->params(array('param1' => 'value1'));
    }

    public function testMagicCallNonExistentMethod()
    {
        $this->init();

        // Call the magic method __call with a method that does not exist
        $result = $this->statement->nonExistentMethod();

        // Ensure the result is null because non-existent methods are not handled
        $this->assertNull($result);
    }
}
