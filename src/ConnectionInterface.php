<?php

namespace Wilkques\Database;

interface ConnectionInterface
{
    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null);

    /**
     * @param string $sql
     * 
     * @return PDOStatement
     */
    public function prepare($sql);
}
