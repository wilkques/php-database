<?php

namespace Wilkques\Tests\Queries\Processors;

use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    private function builder($queries = array())
    {
        $abstract = $this->getMockBuilder('Wilkques\Database\Queries\Builder');

        $abstract->disableOriginalConstructor();

        /** @var \Wilkques\Database\Queries\Builder */
        $abstract = $abstract->getMockForAbstractClass();

        return $abstract->setQueries($queries);
    }

    private function grammar()
    {
        return $this->getMockForAbstractClass(
            'Wilkques\Database\Queries\Grammar\Grammar',
            array(),
            '',
            false
        );
    }

    private function dbResult()
    {
        $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        return call_user_func(array($this, $createMock), 'Wilkques\Database\Connections\ResultInterface');
    }

    private function connection()
    {
        return $this->getMockForAbstractClass(
            'Wilkques\Database\Connections\Connections',
            array(),
            '',
            false
        );
    }

    private function processor()
    {
        $createMock = method_exists($this, 'createMock') ? 'createMock' : 'getMock';

        $mock = call_user_func(array($this, $createMock), 'Wilkques\Database\Queries\Processors\ProcessorInterface');

        $mock->expects($this->once())->method('processInsertGetId')->willReturn(1);

        return $mock;
    }

    private function resultConnectForInsertAndUpdate()
    {
        $connection = $this->connection();

        $result = $this->dbResult();

        $connection->expects($this->any())->method('exec')
            ->willReturnCallback(function ($query, $bindings) use ($connection, $result) {
                $connection->setQueryLog(compact('query', 'bindings'));

                return $result;
            });

        return $connection;
    }

    public function testProcessInsertGetId()
    {
        $builder = $this->builder();

        $builder->setConnection(
            $this->resultConnectForInsertAndUpdate()
        );

        $builder->setGrammar(
            $this->grammar()
        );

        $builder->from('abc');

        $processor = $this->processor();

        $result = $processor->processInsertGetId($builder, array('abc' => 1));

        $this->assertTrue(
            is_numeric($result)
        );

        $this->assertEquals(1, $result);
    }
}
