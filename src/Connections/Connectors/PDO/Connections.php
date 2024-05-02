<?php

namespace Wilkques\Database\Connections\Connectors\PDO;

use InvalidArgumentException;
use Wilkques\Helpers\Arrays;

class Connections
{
    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\ConnectionInterface
     */
    public function connection($config)
    {
        $driver = Arrays::get($config, 'driver');

        switch ($driver) {
            case 'mysql':
                $connection = new \Wilkques\Database\Connections\Connectors\PDO\MySqlConnector;

                return $connection->connection($config);
                break;
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}].");
    }
}
