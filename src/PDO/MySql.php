<?php

namespace Wilkques\Database\PDO;

class MySql extends PDO
{
    /**
     * @return static
     */
    public function connect()
    {
        try {
            $pdo = new \PDO("mysql:host={$this->getHost()};dbname={$this->getDbname()}", $this->getUsername(), $this->getPassword());

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $pdo->query("set character set utf8mb4");

            return $pdo;
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->getConnection()->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * @return bool
     */
    public function inTransation()
    {
        return $this->getConnection()->inTransaction();
    }
}
