<?php

namespace Wilkques\Tests\Units\Connections;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Connections\Connections;

class ConnectionsTest extends TestCase
{
    private function connection()
    {
        $driver = getenv('DB_DRIVER');

        $dir = dirname(dirname(__DIR__));

        $dotenv = \Dotenv\Dotenv::createImmutable($dir);

        $dotenv->load();

        $host = getenv('DB_HOST');

        $username = getenv('DB_USER');

        $password = getenv('DB_PASSWORD');

        $database = getenv('DB_NAME_1');

        return $this->getMockForAbstractClass(
            'Wilkques\Database\Connections\Connections',
            compact('driver', 'host', 'username', 'password', 'database')
        );
    }

    public function testConstruct()
    {
        $connections = $this->connection();

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testConnect()
    {
        $driver = getenv('DB_DRIVER');

        $dir = dirname(dirname(__DIR__));

        $dotenv = \Dotenv\Dotenv::createImmutable($dir);

        $dotenv->load();

        $host = getenv('DB_HOST');

        $username = getenv('DB_USER');

        $password = getenv('DB_PASSWORD');

        $database = getenv('DB_NAME_1');

        $connections = \Wilkques\Database\Connections\PDO\Drivers\MySql::connect(
            compact('driver', 'host', 'username', 'password', 'database')
        );

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testSetConnection()
    {
        $connections = $this->connection();

        $connections->setConnection(123);

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetConnection()
    {
        $connections = $this->connection();

        $connections->setConnection(123);

        $this->assertEquals(
            123,
            $connections->getConnection()
        );
    }

    public function testSetConfig()
    {
        $connections = $this->connection();

        $connections->setConfig('abc', 123);

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetConfig()
    {
        $connections = $this->connection();

        $connections->setConfig('abc', 123);

        $this->assertEquals(
            123,
            $connections->getConfig('abc')
        );
    }

    public function testSetHost()
    {
        $connections = $this->connection();

        $connections->setHost('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetHost()
    {
        $connections = $this->connection();

        $connections->setHost('abc');

        $this->assertEquals(
            'abc',
            $connections->getHost()
        );
    }

    public function testSetUsername()
    {
        $connections = $this->connection();

        $connections->setUsername('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetUsername()
    {
        $connections = $this->connection();

        $connections->setUsername('abc');

        $this->assertEquals(
            'abc',
            $connections->getUsername()
        );
    }

    public function testSetPassword()
    {
        $connections = $this->connection();

        $connections->setPassword('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetPassword()
    {
        $connections = $this->connection();

        $connections->setPassword('abc');

        $this->assertEquals(
            'abc',
            $connections->getPassword()
        );
    }

    public function testSetDatabase()
    {
        $connections = $this->connection();

        $connections->setDatabase('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetDatabase()
    {
        $connections = $this->connection();

        $connections->setDatabase('abc');

        $this->assertEquals(
            'abc',
            $connections->getDatabase()
        );
    }

    public function testSetPort()
    {
        $connections = $this->connection();

        $connections->setPort('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetPort()
    {
        $connections = $this->connection();

        $connections->setPort('abc');

        $this->assertEquals(
            'abc',
            $connections->getPort()
        );
    }

    public function testSetCharacterSet()
    {
        $connections = $this->connection();

        $connections->setCharacterSet('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetCharacterSet()
    {
        $connections = $this->connection();

        $connections->setCharacterSet('abc');

        $this->assertEquals(
            'abc',
            $connections->getCharacterSet()
        );
    }

    public function testSetQueryLog()
    {
        $connections = $this->connection();

        $connections->setQueryLog('abc');

        $this->assertTrue(
            $connections instanceof Connections
        );
    }

    public function testGetQueryLog()
    {
        $connections = $this->connection();

        $connections->setQueryLog('abc');

        $this->assertEquals(
            array(
                'abc'
            ),
            $connections->getQueryLog()
        );
    }

    public function testFlushQueryLog()
    {
        $connections = $this->connection();

        $connections->setQueryLog('abc');

        $connections->flushQueryLog();

        $this->assertEquals(
            array(),
            $connections->getQueryLog()
        );
    }

    public function testGetLastQueryLog()
    {
        $connections = $this->connection();

        $connections->setQueryLog('abc');

        $this->assertEquals(
            'abc',
            $connections->getLastQueryLog()
        );
    }

    public function testSetLoggingQueries()
    {
        $connections = $this->connection();

        $connections->setLoggingQueries();

        $this->assertNotTrue(
            $connections->getLoggingQueries()
        );
    }

    public function testEnableQueryLog()
    {
        $connections = $this->connection();

        $connections->enableQueryLog();

        $this->assertTrue(
            $connections->getLoggingQueries()
        );
    }

    public function testDisableQueryLog()
    {
        $connections = $this->connection();

        $connections->disableQueryLog();

        $this->assertNotTrue(
            $connections->getLoggingQueries()
        );
    }

    public function testGetLoggingQueries()
    {
        $connections = $this->connection();

        $this->assertNotTrue(
            $connections->getLoggingQueries()
        );
    }

    public function testIsLogging()
    {
        $connections = $this->connection();

        $this->assertNotTrue(
            $connections->isLogging()
        );
    }

    public function testGetParseQueryLog()
    {
        $connections = $this->connection();

        $this->assertEquals(
            array(),
            $connections->getParseQueryLog()
        );

        $connections->setQueryLog(
            array(
                'query' => 'abc',
                'bindings' => array()
            )
        );

        $this->assertEquals(
            array('abc'),
            $connections->getParseQueryLog()
        );

        $connections = $this->connection();

        $connections->setQueryLog(
            array(
                'query' => 'WHERE `id` = ?',
                'bindings' => array(1)
            )
        );

        $this->assertEquals(
            array('WHERE `id` = 1'),
            $connections->getParseQueryLog()
        );
    }

    public function testGetLastParseQuery()
    {
        $connections = $this->connection();

        $this->assertEquals(
            '',
            $connections->getLastParseQuery()
        );

        $connections->setQueryLog(
            array(
                'query' => 'abc',
                'bindings' => array()
            )
        );

        $this->assertEquals(
            'abc',
            $connections->getLastParseQuery()
        );

        $connections = $this->connection();

        $connections->setQueryLog(
            array(
                'query' => 'WHERE `id` = ?',
                'bindings' => array(1)
            )
        );

        $this->assertEquals(
            'WHERE `id` = 1',
            $connections->getLastParseQuery()
        );
    }
}
