<?php

namespace Wilkques\Database\Connections;

interface ConnectionInterface
{
    /**
     * @return string
     */
    public function getDatabase();

    /**
     * @param array $queryLog
     * 
     * @return static
     */
    public function setQueryLog($queryLog);

    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null);
    
    /**
     * @return array
     */
    public function getLastQueryLog();

    /**
     * @param string $sql
     * 
     * @return \Wilkques\Database\Connections\PDO\Statement
     */
    public function prepare($sql);
    
    /**
     * @param string|null $query
     * @param array $bindings
     * 
     * @return Result
     */
    public function exec($query, $bindings = []);
}
