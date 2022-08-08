<?php

namespace Wilkques\Database\Connections\PDO;

use Wilkques\Database\Connections\ConnectionInterface;

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
    /** @var int|string */
    protected $port;
    /** @var string */
    protected $characterSet = "utf8mb4";
    /** @var static */
    protected $connection;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     */
    public function __construct($host = null, $username = null, $password = null, $dbname = null, $port = 3306)
    {
        $this->setHost($host)
            ->setUsername($username)
            ->setPassword($password)
            ->setDbname($dbname)
            ->setPort($port)
            ->newConnect()
            ->setPdoAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @return string
     */
    abstract public function getDNS();

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
     * @param string|int $port
     * 
     * @return static
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
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
     * @return static
     */
    public function setPdoAttribute($attribute, $value)
    {
        $this->getConnection()->setAttribute($attribute, $value);

        return $this;
    }

    /**
     * @param string $characterSet
     * 
     * @return static
     */
    public function setCharacterSet($characterSet = "utf8mb4")
    {
        $this->characterSet = $characterSet;

        return $this;
    }

    /**
     * @return string
     */
    public function getCharacterSet()
    {
        return $this->characterSet;
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
}