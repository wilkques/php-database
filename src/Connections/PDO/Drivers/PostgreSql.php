<?php

namespace Wilkques\Database\Connections\PDO\Drivers;

use Wilkques\Database\Connections\PDO\PDO;

class PostgreSql extends PDO
{
    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string|int $port
     * @param string $characterSet
     *
     * @return static
     */
    public static function connect($host = null, $username = null, $password = null, $database = null, $port = 5432, $characterSet = "UTF8")
    {
        return parent::connect($host, $username, $password, $database, $port, $characterSet);
    }

    /**
     * PostgreSQL requires the encoding name to be a quoted string literal
     * for `SET NAMES` (`SET NAMES 'UTF8'`), unlike MySQL's bare-identifier
     * form (`SET NAMES utf8mb4`) used by the base class.
     *
     * @param string|null $dns
     *
     * @return \PDO
     */
    public function connection($dns = null)
    {
        $pdo = new \PDO(
            $dns ?: $this->getDNS(),
            $this->getUsername(),
            $this->getPassword()
        );

        if ($character = $this->getCharacterSet()) {
            $pdo->exec("SET NAMES '{$character}'");
        }

        return $pdo;
    }

    /**
     * @return string
     */
    public function getDNS()
    {
        if ($database = $this->getDatabase()) {
            return sprintf(
                "pgsql:host=%s;dbname=%s;port=%s",
                $this->getHost(),
                $database,
                $this->getPort()
            );
        }

        return sprintf(
            "pgsql:host=%s;port=%s",
            $this->getHost(),
            $this->getPort()
        );
    }

    /**
     * PostgreSQL has no `USE <db>` statement, so switching databases means
     * reconnecting with the new database in the DSN.
     *
     * @param string $database
     *
     * @return static
     */
    public function selectDatabase($database)
    {
        return $this->setDatabase($database)->reConnection();
    }
}
