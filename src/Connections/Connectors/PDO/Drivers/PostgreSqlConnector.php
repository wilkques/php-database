<?php

namespace Wilkques\Database\Connections\Connectors\PDO\Drivers;

use Wilkques\Database\Connections\Connectors\Connector;
use Wilkques\Helpers\Arrays;

class PostgreSqlConnector extends Connector
{
    /**
     * @var array
     */
    protected $defaultConfing = array(
        'host'      => 'localhost',
        'username'  => null,
        'password'  => null,
        'database'  => null,
        'port'      => 5432,
        'charset'   => 'UTF8',
    );

    /**
     * @param array $config
     *
     * @return \Wilkques\Database\Connections\Connections
     */
    public function connection($config)
    {
        $config = $this->config($config);

        $host = Arrays::get($config, 'host');

        $username = Arrays::get($config, 'username');

        $password = Arrays::get($config, 'password');

        $database = Arrays::get($config, 'database');

        $port = Arrays::get($config, 'port');

        $charset = Arrays::get($config, 'charset');

        /**
         * @var \Wilkques\Database\Connections\Connections|\Wilkques\Database\Connections\PDO\Drivers\PostgreSql
         */
        $connection = new \Wilkques\Database\Connections\PDO\Drivers\PostgreSql;

        $connection->setHost($host)
            ->setUsername($username)
            ->setPassword($password)
            ->setPort($port)
            ->setCharacterSet($charset)
            ->setDatabase($database)
            ->newConnection();

        return $connection;
    }
}
