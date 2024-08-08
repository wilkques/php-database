<?php

namespace Wilkques\Database\Connections\Connectors\PDO\Drivers;

use Wilkques\Database\Connections\Connectors\Connector;
use Wilkques\Helpers\Arrays;

class MySqlConnector extends Connector
{
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
         * @var \Wilkques\Database\Connections\Connections|\Wilkques\Database\Connections\PDO\Drivers\MySql
         */
        $connection = new \Wilkques\Database\Connections\PDO\Drivers\MySql;

        $connection->setHost($host)
            ->setUsername($username)
            ->setPassword($password)
            ->setPort($port)
            ->setCharacterSet($charset)
            ->newConnection();

        if ($database) {
            $connection->selectDatabase($database);
        }

        return $connection;
    }
}
