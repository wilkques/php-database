<?php

namespace Wilkques\Database\Connections;

abstract class Connections
{
    /** @var array */
    protected $config = array();

    /** @var static */
    protected $connection;

    /** @var bool */
    protected $loggingQueries = false;

    /** @var array */
    protected $queryLog = array();

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string|int $port
     * @param string $characterSet
     */
    public function __construct($host = null, $username = null, $password = null, $database = null, $port = 3306, $characterSet = "utf8mb4")
    {
        $this->setHost($host)
            ->setUsername($username)
            ->setPassword($password)
            ->setDatabase($database)
            ->setPort($port)
            ->setCharacterSet($characterSet);
    }

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
    public static function connect($host = null, $username = null, $password = null, $database = null, $port = 3306, $characterSet = "utf8mb4")
    {
        return new static($host, $username, $password, $database, $port, $characterSet);
    }

    /**
     * @param mixed $connection
     * 
     * @return static
     */
    public function setConnection($connection = null)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $key
     * @param string|int $value
     * 
     * @return static
     */
    public function setConfig(string $key, $value)
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * 
     * @return static
     */
    public function getConfig(string $key = null)
    {
        return $key ? ($this->config[$key] ?? null) : $this->config;
    }

    /**
     * @param string $host
     * 
     * @return static
     */
    public function setHost($host)
    {
        return $this->setConfig("host", $host);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->getConfig("host");
    }

    /**
     * @param string $username
     * 
     * @return static
     */
    public function setUsername($username)
    {
        return $this->setConfig("username", $username);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getConfig("username");
    }

    /**
     * @param string $password
     * 
     * @return static
     */
    public function setPassword($password)
    {
        return $this->setConfig("password", $password);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getConfig("password");
    }

    /**
     * @param string $dbname
     * 
     * @return static
     */
    public function setDatabase($dbname)
    {
        return $this->setConfig("database", $dbname);
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->getConfig("database");
    }

    /**
     * @param string|int $port
     * 
     * @return static
     */
    public function setPort($port)
    {
        return $this->setConfig("port", $port);
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->getConfig("port");
    }

    /**
     * @param string $characterSet
     * 
     * @return static
     */
    public function setCharacterSet($characterSet = "utf8mb4")
    {
        $this->setConfig('characterSet', $characterSet);

        return $this;
    }

    /**
     * @return string
     */
    public function getCharacterSet()
    {
        return $this->getConfig('characterSet');
    }

    /**
     * @param mixed $queryLog
     * 
     * @return static
     */
    public function setQueryLog($queryLog)
    {
        $this->queryLog[] = $queryLog;

        return $this;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];

        return $this;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getLastQueryLog()
    {
        return end($this->queryLog);
    }

    /**
     * @param bool $status
     * 
     * @return static
     */
    public function setLoggingQueries(bool $status = false)
    {
        $this->loggingQueries = $status;

        return $this;
    }

    /**
     * @return void
     */
    public function enableQueryLog()
    {
        $this->setLoggingQueries(true);
    }

    /**
     * @return void
     */
    public function disableQueryLog()
    {
        $this->setLoggingQueries(false);
    }

    /**
     * @return bool
     */
    public function getLoggingQueries()
    {
        return $this->loggingQueries;
    }

    /**
     * @return bool
     */
    public function isLogging()
    {
        return $this->loggingQueries === true;
    }

    /**
     * @return array
     */
    public function getParseQueryLog()
    {
        return array_map(function ($queryLog) {
            $stringSQL = str_replace('?', '"%s"', $queryLog['query']);

            $bindings = $queryLog['bindings'];

            array_unshift($bindings, $stringSQL);

            return call_user_func_array('sprintf', $bindings);
        }, $this->getQueryLog());
    }

    /**
     * @return string
     */
    public function getLastParseQuery()
    {
        $queries = $this->getParseQueryLog();

        return end($queries);
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    abstract public function newConnection(string $dns = null);
}
