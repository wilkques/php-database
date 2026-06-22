<?php

namespace Wilkques\Database\Tests\Units\Connections\PDO;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Wilkques\Database\Connections\PDO\Result;

class ResultTest extends MockeryTestCase
{
    /** @var \PDOStatement|Mockery\MockInterface */
    protected $statement;

    /** @var \Wilkques\Database\Connections\PDO\PDO|Mockery\MockInterface */
    protected $connections;

    /** @var Result */
    protected $result;

    public function testFetchReturnsRowAndClosesStatement()
    {
        $row = array('id' => 1, 'name' => 'Alice');
        $this->statement->shouldReceive('fetch')->andReturn($row);
        $this->statement->shouldReceive('closeCursor')->once();

        $actual = $this->result->fetch();
        $this->assertEquals($row, $actual);
    }

    public function testFetchAllReturnsAllRowsAndClosesStatement()
    {
        $rows = array(array('id' => 1), array('id' => 2));
        $this->statement->shouldReceive('fetchAll')->andReturn($rows);
        $this->statement->shouldReceive('closeCursor')->once();

        $actual = $this->result->fetchAll();
        $this->assertEquals($rows, $actual);
    }

    public function testFetchNumericPassesNumFetchMode()
    {
        $this->statement->shouldReceive('fetch')->with(\PDO::FETCH_NUM)->andReturn(array(1, 'Alice'));
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchNumeric();
        $this->assertEquals(array(1, 'Alice'), $actual);
    }

    public function testFetchAssociativePassesAssocFetchMode()
    {
        $this->statement->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(array('id' => 1));
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchAssociative();
        $this->assertEquals(array('id' => 1), $actual);
    }

    public function testFetchFirstColumnPassesColumnFetchMode()
    {
        $this->statement->shouldReceive('fetch')->with(\PDO::FETCH_COLUMN)->andReturn('Alice');
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchFirstColumn();
        $this->assertEquals('Alice', $actual);
    }

    public function testFetchAllNumericPassesNumFetchMode()
    {
        $this->statement->shouldReceive('fetchAll')->with(\PDO::FETCH_NUM)->andReturn(array(array(1), array(2)));
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchAllNumeric();
        $this->assertEquals(array(array(1), array(2)), $actual);
    }

    public function testFetchAllAssociativePassesAssocFetchMode()
    {
        $rows = array(array('id' => 1), array('id' => 2));
        $this->statement->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn($rows);
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchAllAssociative();
        $this->assertEquals($rows, $actual);
    }

    public function testFetchAllFirstColumnPassesColumnFetchMode()
    {
        $this->statement->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->andReturn(array('Alice', 'Bob'));
        $this->statement->shouldReceive('closeCursor');

        $actual = $this->result->fetchAllFirstColumn();
        $this->assertEquals(array('Alice', 'Bob'), $actual);
    }

    public function testRowCountReturnsCountAndClosesStatement()
    {
        $this->statement->shouldReceive('rowCount')->andReturn(5);
        $this->statement->shouldReceive('closeCursor')->once();

        $actual = $this->result->rowCount();
        $this->assertEquals(5, $actual);
    }

    public function testColumnCountReturnsCountAndClosesStatement()
    {
        $this->statement->shouldReceive('columnCount')->andReturn(3);
        $this->statement->shouldReceive('closeCursor')->once();

        $actual = $this->result->columnCount();
        $this->assertEquals(3, $actual);
    }

    public function testFreeCallsCloseCursor()
    {
        $this->statement->shouldReceive('closeCursor')->once();
        $this->result->free();
    }

    public function testGetLastInsertIdDelegatesToConnectionsAndClosesStatement()
    {
        $this->connections->shouldReceive('getLastInsertId')->andReturn(42);
        $this->statement->shouldReceive('closeCursor')->once();

        $actual = $this->result->getLastInsertId();
        $this->assertEquals(42, $actual);
    }

    public function testGetStatementReturnsStatement()
    {
        $this->assertSame($this->statement, $this->result->getStatement());
    }

    public function testGetConnectionsReturnsConnections()
    {
        $this->assertSame($this->connections, $this->result->getConnections());
    }
}
