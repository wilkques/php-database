<?php

namespace Wilkques\Database\Tests\Units\Queries\Grammar;

use Mockery\Adapter\Phpunit\MockeryTestCase;

class MySqlGrammarTest extends MockeryTestCase
{
    protected $grammar;

    protected $query;

    public function testLockForUpdate()
    {
        $this->grammar->shouldReceive('lockForUpdate')
            ->once()
            ->andReturn('FOR UPDATE');

        $result = $this->grammar->lockForUpdate();

        $this->assertEquals('FOR UPDATE', $result);
    }

    public function testSharedLock()
    {
        $this->grammar->shouldReceive('sharedLock')
            ->once()
            ->andReturn('LOCK IN SHARE MODE');

        $result = $this->grammar->sharedLock();

        $this->assertEquals('LOCK IN SHARE MODE', $result);
    }
}
