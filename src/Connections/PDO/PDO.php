<?php

namespace Wilkques\Database\Connections\PDO;

use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Connections\Connections;

abstract class PDO extends Connections implements ConnectionInterface
{
    /**
     * @param int $attribute
     * @param mixed $value
     * 
     * @return static
     */
    public function setAttribute($attribute, $value)
    {
        /** @var \PDO */
        $pdo = $this->newConnecntion();

        $pdo->setAttribute($attribute, $value);

        return $this;
    }

    /**
     * @param string $sql
     * 
     * @return Result
     */
    public function query($sql)
    {
        return new Result($this->newConnecntion()->query($sql));
    }

    /**
     * @param string $sql
     * 
     * @return Statement
     */
    public function prepare($sql)
    {
        return new Statement($this->newConnecntion()->prepare($sql));
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    public function connect(string $dns = null)
    {
        try {
            return new \PDO(
                $dns ?: $this->getDNS(),
                $this->getUsername(),
                $this->getPassword()
            );
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->newConnecntion()->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->newConnecntion()->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->newConnecntion()->rollBack();
    }

    /**
     * @return bool
     */
    public function inTransation()
    {
        return $this->newConnecntion()->inTransaction();
    }

    /**
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->newConnecntion()->lastInsertId();
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    public function newConnect(string $dns = null)
    {
        return $this->setConnection($this->connect($dns))
            ->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @return \PDO
     */
    public function newConnecntion()
    {
        !$this->getConnection() && $this->newConnect();

        return $this->getConnection();
    }

    /**
     * @return string
     */
    abstract public function getDNS();
}
