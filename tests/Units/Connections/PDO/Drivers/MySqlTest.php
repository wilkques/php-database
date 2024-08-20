<?php

namespace Wilkques\Database\Tests\Units\Connections\PDO\Drivers;

use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;
use Wilkques\Database\Tests\BaseTestCase;
use Wilkques\Helpers\Arrays;

class MySqlTest extends BaseTestCase
{
    /** @var MySql */
    private $connection;

    private function init()
    {
        $dir = dirname(dirname(dirname(dirname(__DIR__))));

        $this->envLoad($dir);
    }

    /**
     * @return MySql
     */
    private function connection()
    {
        $mysql = \container()->get('Wilkques\Database\Connections\PDO\Drivers\MySql');

        if (!$mysql) {
            $host = $this->getConfigItem('DB_HOST');

            $username = $this->getConfigItem('DB_USER');

            $password = $this->getConfigItem('DB_PASSWORD');

            $database = $this->getConfigItem('DB_NAME_1');

            $connection = MySql::connect($host, $username, $password, $database);

            \container()->singleton('Wilkques\Database\Connections\PDO\Drivers\MySql', $connection->newConnection());

            return \container('Wilkques\Database\Connections\PDO\Drivers\MySql');
        }

        return $mysql;
    }

    private function setupDatabase()
    {
        $sql = "CREATE TABLE users_for_test (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `info` BLOB,
            PRIMARY KEY ( `id` ) 
        );";

        $this->connection()->exec($sql);
    }

    private function cleanupDatabase()
    {
        $this->connection()->exec("DROP TABLE IF EXISTS users_for_test");
    }

    public function testSetAttribute()
    {
        $this->init();

        $this->connection()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->assertTrue(
            $this->connection() instanceof MySql
        );
    }

    public function testQuery()
    {
        $this->init();

        $result = $this->connection()->query('SELECT 1');

        $this->assertEquals(
            array(1 => 1),
            $result->fetch()
        );
    }

    public function testPrepare()
    {
        $this->init();

        $result = $this->connection()->prepare('SELECT 1')->execute();

        $this->assertEquals(
            array(1 => 1),
            $result->fetch()
        );
    }

    public function testConnection()
    {
        $this->init();

        $pdo = $this->connection()->connection();

        $this->assertTrue(
            $pdo instanceof \PDO
        );
    }

    public function testBeginTransaction()
    {
        $this->init();

        $connection = $this->connection();

        $connection->beginTransaction();

        $this->assertTrue(
            $connection->inTransation(),
            'Transaction should be active after beginTransaction.'
        );

        $connection->commit();
    }

    private function runDatabase($callback)
    {
        $this->init();

        $this->connection = $this->connection();

        $this->cleanupDatabase();

        $this->setupDatabase();

        call_user_func($callback, $this);

        $this->cleanupDatabase();
    }

    public function testCommit()
    {
        $this->runDatabase(function ($mysqlTest) {
            $mysqlTest->connection->beginTransaction();

            $mysqlTest->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Commit Test'));

            $mysqlTest->connection->commit();

            $result = $mysqlTest->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Commit Test'));

            $row = $result->fetch();

            $mysqlTest->assertNotEmpty($row, 'Data should be present after commit.');
        });
    }

    public function testRollback()
    {
        $this->runDatabase(function ($mysqlTest) {
            $mysqlTest->connection->beginTransaction();

            $mysqlTest->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Rollback Test'));

            $mysqlTest->connection->rollback();

            $result = $mysqlTest->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Rollback Test'));

            $row = $result->fetch();

            $mysqlTest->assertEmpty($row, 'Data should not be present after rollback.');
        });
    }

    public function testInTransaction()
    {
        $this->init();

        $connection = $this->connection();

        $connection->beginTransaction();

        $this->assertTrue($connection->inTransation(), 'Transaction should be active after beginTransaction.');

        $connection->rollback();

        $this->assertFalse($connection->inTransation(), 'Transaction should not be active after rollback.');
    }

    public function testGetLastInsertId()
    {
        $this->runDatabase(function ($mysqlTest) {
            $mysqlTest->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

            $lastInsertId = $mysqlTest->connection->getLastInsertId();

            $result = $mysqlTest->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

            $row = $result->fetch();

            $mysqlTest->assertNotEmpty($row, 'Data should be present in the database.');

            $mysqlTest->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
        });
    }

    public function testGetLastInsertIdWithSequence()
    {
        $this->runDatabase(function ($mysqlTest) {
            $mysqlTest->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

            $lastInsertId = $mysqlTest->connection->getLastInsertId('users_id_seq');

            $result = $mysqlTest->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

            $row = $result->fetch();

            $mysqlTest->assertNotEmpty($row, 'Data should be present in the database.');

            $mysqlTest->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
        });
    }

    public function testNewConnection()
    {
        $this->init();

        $connection = $this->connection();

        $connection->newConnection();

        $this->assertNotNull($connection->getConnection(), 'Connection should be established with newConnection.');
    }

    public function testReConnection()
    {
        $connection = $this->connection();

        $connection->reConnection();

        $this->assertNotNull($connection->getConnection(), 'Connection should be re-established with reConnection.');
    }

    public function testRun()
    {
        $this->runDatabase(function ($mysqlTest) {
            $connection = new ReflectionMethod($mysqlTest->connection, 'run');

            $connection->setAccessible(true);

            $connection->invoke($mysqlTest->connection, "INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

            $result = $connection->invoke($mysqlTest->connection, "SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

            $row = $result->fetch();

            $mysqlTest->assertNotEmpty($row, 'Data should be present in the database after running a query.');
        });
    }

    public function testExec()
    {
        $this->runDatabase(function ($mysqlTest) {
            $mysqlTest->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

            $result = $mysqlTest->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

            $row = $result->fetch();

            $mysqlTest->assertNotEmpty($row, 'Data should be present in the database after running a query.');
        });
    }

    public function testTryAgainIfCausedByLostConnection()
    {
        $this->runDatabase(function ($mysqlTest) {
            try {
                $exception = new \Exception("Connection lost");

                $connection = new ReflectionMethod($mysqlTest->connection, 'tryAgainIfCausedByLostConnection');

                $connection->setAccessible(true);

                $connection->invoke($mysqlTest->connection, $exception, "INSERT INTO users_for_test (info) VALUES (?)", array('Test User'));

                $result = $connection->invoke($mysqlTest->connection, "SELECT * FROM users_for_test WHERE info = ?", array('Test User'));

                $row = $result->fetch();

                $mysqlTest->assertInstanceOf('PDOStatement', $row, 'The result should be an instance of PDOStatement.');
            } catch (\Exception $e) {
                $mysqlTest->assertEquals(
                    'Connection lost',
                    $e->getMessage()
                );
            }
        });
    }

    public function testReconnectIfMissingConnection()
    {
        $this->init();

        $connection = $this->connection();

        $connectionMethod = new ReflectionMethod($connection, 'reconnectIfMissingConnection');

        $connectionMethod->setAccessible(true);

        $result = $connectionMethod->invoke($connection);

        $this->assertNotNull($result, 'Connection should be established with reconnectIfMissingConnection.');
    }

    public function testSelectDatabase()
    {
        $this->init();

        $database = $this->getConfigItem('DB_NAME_2');

        $result = $this->connection()->selectDatabase($database);

        $this->assertTrue(
            $result instanceof MySql
        );
    }
}
