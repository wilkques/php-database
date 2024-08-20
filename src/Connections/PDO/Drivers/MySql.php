<?php

namespace Wilkques\Database\Connections\PDO\Drivers;

use Wilkques\Database\Connections\PDO\PDO;

class MySql extends PDO
{
    /**
     * @return static
     */
    public function getDNS()
    {
        if ($database = $this->getDatabase()) {
            return sprintf(
                "mysql:host=%s;dbname=%s;port=%s",
                $this->getHost(),
                $database,
                $this->getPort(),
                $this->getCharacterSet()
            );
        }

        return sprintf(
            "mysql:host=%s;port=%s",
            $this->getHost(),
            $this->getPort(),
            $this->getCharacterSet()
        );
    }
}
