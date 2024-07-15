<?php

namespace Wilkques\Database\Connections\Connectors\PDO\Drivers;

use Wilkques\Database\Connections\Connectors\Connector;

class MySqlConnector extends Connector
{
    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\ConnectionInterface
     */
    public function connection($config)
    {
        list(
            'host'          => $host,
            'username'      => $username,
            'password'      => $password,
            'database'      => $database,
            'port'          => $port,
            'charset'       => $charset,
        ) = $this->config($config);

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
