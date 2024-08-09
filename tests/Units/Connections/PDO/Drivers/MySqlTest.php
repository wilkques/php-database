<?php

namespace Wilkques\Tests\Units\Connections\PDO\Drivers;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Wilkques\Database\Connections\PDO\Drivers\MySql;

class MySqlTest extends TestCase
{
    /** @var MySql */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection();

        // 清理测试环境
        $this->cleanupDatabase();

        // Set up test database
        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        // 清理测试环境
        $this->cleanupDatabase();
    }

    private function connection()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';

        $username = getenv('DB_USER') ?: 'user';

        $password = getenv('DB_PASSWORD') ?: 'root';

        $database = getenv('DB_NAME') ?: 'test';

        $connection = MySql::connect($host, $username, $password, $database);

        $this->connection = $connection->newConnection();
    }

    private function setupDatabase()
    {
        $sql = "CREATE TABLE users_for_test (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `info` BLOB,
            PRIMARY KEY ( `id` ) 
        );";

        $this->connection->exec($sql);
    }

    private function cleanupDatabase()
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
        $this->connection->beginTransaction();

        $this->assertTrue(
            $this->connection->inTransation(),
            'Transaction should be active after beginTransaction.'
        );
    }

    public function testCommit()
    {
        $this->connection->beginTransaction();

        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Commit Test'));

        $this->connection->commit();

        // 查询并验证数据
        $result = $this->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Commit Test'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present after commit.');
    }

    public function testRollback()
    {
        $this->connection->beginTransaction();

        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Rollback Test'));

        $this->connection->rollback();

        // 查询并验证数据
        $result = $this->connection->exec("SELECT * FROM users_for_test WHERE info = ?", array('Rollback Test'));

        $row = $result->fetch();

        $this->assertEmpty($row, 'Data should not be present after rollback.');
    }

    public function testInTransaction()
    {
        $this->connection->beginTransaction();

        $this->assertTrue($this->connection->inTransation(), 'Transaction should be active after beginTransaction.');

        $this->connection->rollback();

        $this->assertFalse($this->connection->inTransation(), 'Transaction should not be active after rollback.');
    }

    public function testGetLastInsertId()
    {
        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

        $lastInsertId = $this->connection->getLastInsertId();

        // 查询数据库以获取插入的 ID
        $result = $this->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database.');

        $this->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
    }

    public function testGetLastInsertIdWithSequence()
    {
        // 注意：MySQL 不使用序列，但我们演示如何调用方法
        $this->connection->exec("INSERT INTO users_for_test (info) VALUES (?)", array('Jane Doe'));

        $lastInsertId = $this->connection->getLastInsertId('users_id_seq'); // 假设存在名为 'users_id_seq' 的序列

        // 查询数据库以获取插入的 ID
        $result = $this->connection->exec("SELECT id FROM users_for_test WHERE info = ?", array('Jane Doe'));

        $row = $result->fetch();

        $this->assertNotEmpty($row, 'Data should be present in the database.');

        $this->assertEquals($row['id'], $lastInsertId, 'The last insert ID should match the ID returned by getLastInsertId.');
    }

    public function testNewConnection()
    {
        $this->connection->newConnection();

        $this->assertNotNull($this->connection->getConnection(), 'Connection should be established with newConnection.');
    }

    public function testReConnection()
    {
        $this->connection->reConnecntion();

        $this->assertNotNull($this->connection->getConnection(), 'Connection should be re-established with reConnecntion.');
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

            // 验证结果
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
        $connection = new ReflectionMethod($this->connection, 'reconnectIfMissingConnection');

        $connection->setAccessible(true);

        $result = $connection->invoke($this->connection);

        $this->assertNotNull($result, 'Connection should be established with reconnectIfMissingConnection.');
    }

    public function testSelectDatabase()
    {
        $result = $this->connection->selectDatabase('try');

        $this->assertTrue(
            $result instanceof MySql
        );
    }
}
