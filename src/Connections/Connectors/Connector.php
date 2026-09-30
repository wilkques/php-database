<?php

namespace Wilkques\Database\Connections\Connectors;

abstract class Connector
{
    /**
     * @var array
     */
    protected $defaultConfing = array();

    /**
     * @param array $config
     *
     * @return array
     */
    public function config($config)
    {
        // `Database::boot()`/`connect()` pass `port`/`charset` as `null`
        // when the caller omits them (rather than baking in one driver's
        // defaults for every driver). Drop null entries here so they fall
        // through to this driver's own defaults below instead of
        // overwriting them with `null`.
        $config = array_filter($config, function ($value) {
            return !is_null($value);
        });

        return array_replace($this->defaultConfing, $config);
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
     * @return \Wilkques\Database\Connections\Connections|\Wilkques\Database\Connections\PDO\MySql|\Wilkques\Database\Connections\PDO\PostgreSql
     */
    abstract public function connection($config);
}