<?php

namespace Wilkques\Database\Connections;

interface ConnectionInterface
{
    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null);
}
