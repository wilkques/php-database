<?php

namespace Wilkques\Tests;

use PHPUnit\Framework\TestCase;
use Wilkques\Database\Connections\PDO\Drivers\MySql as MySqlConnection;
use Wilkques\Database\Database;
use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Grammar\Drivers\MySql as MySqlGrammar;

class BuilderTest extends TestCase
{
    public function testConnection()
    {
        $builder = Database::connect(
            'mysql', 'mariadb', 'root', 'root', 'try'
        );

        $this->assertTrue(
            $builder instanceof Builder
        );

        $this->assertTrue(
            $builder->getConnection() instanceof MySqlConnection
        );

        $this->assertTrue(
            $builder->getGrammar() instanceof MySqlGrammar
        );

        $builder = Database::connect([
            'driver'    => 'mysql',
            'host'      => 'mariadb',
            'username'  => 'root',
            'password'  => 'root',
            'database'  => 'try',
        ]);

        $this->assertTrue(
            $builder instanceof Builder
        );

        $this->assertTrue(
            $builder->getConnection() instanceof MySqlConnection
        );

        $this->assertTrue(
            $builder->getGrammar() instanceof MySqlGrammar
        );
    }
}