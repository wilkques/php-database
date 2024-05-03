<?php

namespace Wilkques\Database\Connections\Connectors;

abstract class Connector
{
    /**
     * @param array $config
     * 
     * @return array
     */
    public function config($config)
    {
        return array_replace(
            array(
                'host'      => 'localhost',
                'username'  => null,
                'password'  => null,
                'database'  => null,
                'port'      => 3306,
                'charset'   => 'utf8mb4',
            ),
            $config
        );
    }

    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\ConnectionInterface
     */
    public static function connect($config)
    {
        $instance = new static;

        return call_user_func(array($instance, 'connection'), $config);
    }

    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\Connections|\Wilkques\Database\Connections\PDO\MySql
     */
    abstract public function connection($config);
}