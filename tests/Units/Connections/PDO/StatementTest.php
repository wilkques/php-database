<?php

namespace Wilkques\Tests\Units\Connections\PDO\Drivers;

use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;
use Wilkques\Database\Connections\PDO\Result;
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

    protected function setUp()
    {
        parent::setUp();

        // Mock PDOStatement
        $this->pdoStatement = $this->createMock('Wilkques\Database\Connections\PDO\Statement');

        // Mock Connections
        $this->connections = $this->createMock('Wilkques\Database\Connections\PDO\Drivers\MySql');

        // Create the Statement instance
        $this->statement = new Statement($this->pdoStatement, $this->connections);
    }

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
        $this->statement->debug();

        $this->assertTrue(
            $this->statement->getDebug()
        );
    }

    public function testGetParam()
    {
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
}
