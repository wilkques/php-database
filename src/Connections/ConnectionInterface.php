<?php

namespace Wilkques\Database\Connections;

interface ConnectionInterface
{
    /**
     * @return string
     */
    public function getDbname();

    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null);
}
