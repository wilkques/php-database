<?php

namespace Wilkques\Database\PDO;

use Wilkques\Database\ConnectionInterface;

abstract class PDO implements ConnectionInterface
{
    /** @var string */
    protected $host;
    /** @var string */
    protected $username;
    /** @var string */
    protected $password;
    /** @var string */
    protected $dbname;
    /** @var static */
    protected $connection;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     */
    public function __construct($host = null, $username = null, $password = null, $dbname = null)
    {
        $this->setHost($host)->setUsername($username)->setPassword($password)->setDbname($dbname)->newConnecntion();
    }

    /**
     * @param string $host
     * 
     * @return static
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $username
     * 
     * @return static
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $password
     * 
     * @return static
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $dbname
     * 
     * @return static
     */
    public function setDbname($dbname)
    {
        $this->dbname = $dbname;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $sql
     * 
     * @return PDOStatement
     */
    public function prepare($sql)
    {
        return $this->getConnection()->prepare($sql);
    }

    /**
     * @return static
     */
    abstract public function connect();

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
        !$this->getConnection() && $this->setConnection($this->connect());

        return $this->getConnection();
    }

    public function __call($method, $arguments)
    {
        // return $this->newConnecntion()->{$method}(...$arguments);

        $connection = $this->newConnecntion();

        return call_user_func_array(array($connection, $method), $arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        // return (new static)->{$method}(...$arguments);

        $instance = new static;

        return call_user_func_array(array($instance, $method), $arguments);
    }
}