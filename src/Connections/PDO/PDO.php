<?php

namespace Wilkques\Database\Connections\PDO;

use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Connections\Connections;

abstract class PDO extends Connections implements ConnectionInterface
{  
    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     */
    public function __construct($host = null, $username = null, $password = null, $dbname = null, $port = 3306)
    {
        parent::__construct($host, $username, $password, $dbname, $port);
            
        $this->newConnect()->setPdoAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @return static
     */
    public function setPdoAttribute($attribute, $value)
    {
        $this->getConnection()->setAttribute($attribute, $value);

        return $this;
    }

    /**
     * @param string $sql
     * 
     * @return Result
     */
    public function query($sql)
    {
        return new Result($this->getConnection()->query($sql));
    }

    /**
     * @param string $sql
     * 
     * @return Statement
     */
    public function prepare($sql)
    {
        return new Statement($this->getConnection()->prepare($sql));
    }

    /**
     * @return static
     */
    public function connect()
    {
        try {
            return new \PDO(
                $this->getDNS(),
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

    /**
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * @return static
     */
    public function newConnect()
    {
        return $this->setConnection($this->connect());
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