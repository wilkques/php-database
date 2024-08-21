<?php

namespace Wilkques\Database\Tests\Units\Connections\PDO\Drivers;

use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;
use Wilkques\Database\Tests\BaseTestCase;

class MySqlTest extends BaseTestCase
{
    /** @var MySql */
    protected $connection;

    protected function init()
    {
        $dir = dirname(dirname(dirname(dirname(__DIR__))));

        $this->configLoad($dir);
    }

    protected function connection()
    {
        $host = $this->getConfigItem('DB_HOST');

        $username = $this->getConfigItem('DB_USER');

        $password = $this->getConfigItem('DB_PASSWORD');

        $database = $this->getConfigItem('DB_NAME_1');

        $this->connection = MySql::connect($host, $username, $password, $database);
    }

    protected function setupDatabase()
    {
        $sql = "CREATE TABLE users_for_test (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `info` BLOB,
            PRIMARY KEY ( `id` ) 
        );";

        $this->connection->exec($sql);
    }

    protected function cleanupDatabase()
    {
        $this->connection->exec("DROP TABLE IF EXISTS users_for_test");
    }

    public function testSetAttribute()
    {
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->assertTrue(
            $this->connection instanceof MySql
        );
    }

    public function testQuery()
    {
        $result = $this->connection->query('SELECT 1');

        $this->assertEquals(
            array(1 => 1),
            $result->fetch()
        );
    }

    public function testPrepare()
    {
        $result = $this->connection->prepare('SELECT 1')->execute();

        $this->assertEquals(
            array(1 => 1),
            $result->fetch()
        );
    }

    public function testConnection()
    {
        $pdo = $this->connection->connection();

        $this->assertTrue(
            $pdo instanceof \PDO
        );
    }

    public function testBeginTransaction()
    {
        $connection = $this->connection;

        $connection->beginTransaction();

        $this->assertTrue(
            $connection->inTransation(),
            'Transaction should be active after beginTransaction.'
        );

        $connection->commit();
    }

    public function testCommit()
    {
        $this->connection->beginTransaction();

        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Commit Test'));

        $this->connection->commit();

        $result = $this->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Commit Test'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present after commit.');
    }

    public function testRollback()
    {
        $this->connection->beginTransaction();

        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Rollback Test'));

        $this->connection->rollback();

        $result = $this->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Rollback Test'));

        $row = $result->fetch();

        $this->assertEmpty($row, 'Data should not be present after rollback.');
    }

    public function testInTransaction()
    {
        $connection = $this->connection;

        $connection->beginTransaction();

        $this->assertTrue($connection->inTransation(), 'Transaction should be active after beginTransaction.');

        $connection->rollback();

        $this->assertFalse($connection->inTransation(), 'Transaction should not be active after rollback.');
    }

    public function testGetLastInsertId()
    {
        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

        $lastInsertId = $this->connection->getLastInsertId();

        $result = $this->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database.');

        $this->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
    }

    public function testGetLastInsertIdWithSequence()
    {
        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

        $lastInsertId = $this->connection->getLastInsertId('users_id_seq');

        $result = $this->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database.');

        $this->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
    }

    public function testNewConnection()
    {
        $connection = $this->connection;

        $connection->newConnection();

        $this->assertNotNull($connection->getConnection(), 'Connection should be established with newConnection.');
    }

    public function testReConnection()
    {
        $connection = $this->connection;

        $connection->reConnection();

        $this->assertNotNull($connection->getConnection(), 'Connection should be re-established with reConnection.');
    }

    public function testRun()
    {
        $connection = new ReflectionMethod($this->connection, 'run');

        $connection->setAccessible(true);

        $connection->invoke($this->connection, "INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

        $result = $connection->invoke($this->connection, "SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database after running a query.');
    }

    public function testExec()
    {
        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

        $result = $this->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database after running a query.');
    }

    public function testTryAgainIfCausedByLostConnection()
    {
        try {
            $exception = new \Exception("Connection lost");

            $connection = new ReflectionMethod($this->connection, 'tryAgainIfCausedByLostConnection');

            $connection->setAccessible(true);

            $connection->invoke($this->connection, $exception, "INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

            $result = $connection->invoke($this->connection, "SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

            $row = $result->fetch();

            $this->assertInstanceOf('PDOStatement', $row, 'The result should be an instance of PDOStatement.');
        } catch (\Exception $e) {
            $this->assertEquals(
                'Connection lost',
                $e->getMessage()
            );
        }
    }

    public function testReconnectIfMissingConnection()
    {
        $connection = $this->connection;

        $connectionMethod = new ReflectionMethod($connection, 'reconnectIfMissingConnection');

        $connectionMethod->setAccessible(true);

        $result = $connectionMethod->invoke($connection);

        $this->assertNotNull($result, 'Connection should be established with reconnectIfMissingConnection.');
    }

    public function testSelectDatabase()
    {
        $database = $this->getConfigItem('DB_NAME_2');

        $result = $this->connection->selectDatabase($database);

        $this->assertTrue(
            $result instanceof MySql
        );
    }
}
