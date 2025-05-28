<?php

namespace Wilkques\Database\Tests\Units\Queries\Processors;

use Mockery\Adapter\Phpunit\MockeryTestCase;

class ProcessorTest extends MockeryTestCase
{
    protected $query;

    protected $connection;

    protected $processor;

    public function testProcessInsertGetId()
    {
        $this->connection->shouldReceive('getConnection->lastInsertId')
            ->once()
            ->andReturn(1);

        $this->query->shouldReceive('getConnection')
            ->once()
            ->andReturn($this->connection);

        $this->query->shouldReceive('insert')
            ->once()
            ->with(array('key' => 'value'));

        $result = $this->processor->processInsertGetId($this->query, array('key' => 'value'));

        $this->assertEquals(1, $result);
    }

    public function testProcessInsertGetIdWithSequence()
    {
        $this->connection->shouldReceive('getConnection->lastInsertId')
            ->once()
            ->with('id')
            ->andReturn(1);

        $this->query->shouldReceive('getConnection')
            ->once()
            ->andReturn($this->connection);

        $this->query->shouldReceive('insert')
            ->once()
            ->with(array('key' => 'value'));

        $result = $this->processor->processInsertGetId($this->query, array('key' => 'value'), 'id');

        $this->assertEquals(1, $result);
    }
}
