<?php

namespace Wilkques\Database\Connections\PDO;

class MySql extends PDO
{
    /**
     * @return static
     */
    public function getDNS()
    {
        return sprintf(
            "mysql:host=%s;dbname=%s;port=%s;charset=%s",
            $this->getHost(),
            $this->getDbname(),
            $this->getPort(),
            $this->getCharacterSet()
        );
    }
}
