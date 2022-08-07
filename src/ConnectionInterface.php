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
}
