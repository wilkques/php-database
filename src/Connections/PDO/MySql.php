<?php

namespace Wilkques\Database\Connections\PDO;

class MySql extends PDO
{
    /**
     * @return static
     */
    public function getDNS()
    {
        if ($database = $this->getDatabase()) {
            return sprintf(
                "mysql:host=%s;dbname=%s;port=%s;charset=%s",
                $this->getHost(),
                $database,
                $this->getPort(),
                $this->getCharacterSet()
            );
        }

        return sprintf(
            "mysql:host=%s;port=%s;charset=%s",
            $this->getHost(),
            $this->getPort(),
            $this->getCharacterSet()
        );
    }
}
