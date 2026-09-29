<?php

namespace Wilkques\Database\Tests\Units\Queries\Support;

use Wilkques\Database\Connections\Connections;

/**
 * Minimal concrete Connections implementation for tests that need a real
 * (non-mocked) connection object — e.g. to exercise Connections' real
 * setHost()/setUsername()/etc. through Builder::__call(), or just to satisfy
 * Builder's constructor type hint. Not built with PHPUnit's
 * getMockForAbstractClass(): that method (and
 * MockBuilder::getMockForAbstractClass()) were removed in PHPUnit 12, which
 * "phpunit/phpunit": "*" silently started resolving to on PHP 8.3 CI runs,
 * breaking every test that used it.
 */
class ConnectionsStub extends Connections
{
    public function newConnection($dns = null)
    {
    }

    public function prepare($sql)
    {
    }

    public function exec($query, $bindings = array())
    {
    }

    public function selectDatabase($database)
    {
    }

    public function getLastInsertId($sequence = null)
    {
    }
}
