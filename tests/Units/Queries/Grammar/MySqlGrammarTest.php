<?php

namespace Wilkques\Tests\Units\Queries\Grammar;

use PHPUnit\Framework\TestCase;

class MySqlGrammarTest extends TestCase
{
    private function grammar()
    {
        return new \Wilkques\Database\Queries\Grammar\Drivers\MySql;
    }

    public function testLockForUpdate()
    {
        $this->assertEquals(
            "FOR UPDATE",
            $this->grammar()->lockForUpdate()
        );
    }

    public function testSharedLock()
    {
        $this->assertEquals(
            "LOCK IN SHARE MODE",
            $this->grammar()->sharedLock()
        );
    }
}
