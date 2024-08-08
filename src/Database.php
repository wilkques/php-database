<?php

namespace Wilkques\Database;

use Wilkques\Helpers\Arrays;

class Database
{
    /**
     * @param string $driver
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string|int $port
     * @param string $characterSet
     * 
     * @return \Wilkques\Database\Queries\Builder
     * 
     * @throws \InvalidArgumentException
     */
    protected function boot($driver, $host = null, $username = null, $password = null, $database = null, $port = 3306, $characterSet = "utf8mb4")
    {
        $connection = \Wilkques\Database\Connections\Connectors\PDO\Connections::connect(get_defined_vars());

        $builder = new \Wilkques\Database\Queries\Builder($connection);

        $builder->setProcessor(
            new \Wilkques\Database\Queries\Processors\Processor
        );

        switch ($driver) {
            case 'mysql':
                return $builder->setGrammar(
                    new \Wilkques\Database\Queries\Grammar\Drivers\MySql
                );
                break;
        }

        throw new \InvalidArgumentException("Unsupported driver [{$driver}].");
    }

    /**
     * @param string|array $driver
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string|int $port
     * @param string $characterSet
     * 
     * @return \Wilkques\Database\Queries\Builder
     * 
     * @throws \InvalidArgumentException
     */
    public static function connect($driver, $host = null, $username = null, $password = null, $database = null, $port = 3306, $characterSet = "utf8mb4")
    {
        $vars = get_defined_vars();

        if (is_array($driver)) {
            $sortKeys = array_keys($vars);

            array_shift($vars);

            $vars = array_replace(
                $vars,
                $driver
            );

            $vars = Arrays::keyFields($vars, $sortKeys);
        }

        $instance = new static;

        return call_user_func_array(array($instance, 'boot'), $vars);
    }
}
