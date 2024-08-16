<?php

namespace Wilkques\Database\Tests\Units\Queries;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Queries\Expression;

class ExpressionTest extends TestCase
{
    public function testConstruct()
    {
        $raw = new Expression('abc');

        $this->assertTrue(
            $raw instanceof Expression
        );
    }
    
    public function testGetValue()
    {
        $raw = new Expression('abc');

        $this->assertEquals(
            'abc',
            $raw->getValue()
        );
    }
    
    public function testToString()
    {
        $raw = new Expression('abc');

        $this->assertEquals(
            'abc',
            (string) $raw
        );
    }
}