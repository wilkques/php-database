<?php

namespace Wilkques\Database;

interface ConnectionInterface
{
    /**
     * @param string $host
     * 
     * @return static
     */
    public function setHost($host);

    /**
     * @return string
     */
    public function getHost();

    /**
     * @param string $username
     * 
     * @return static
     */
    public function setUsername($username);

    /**
     * @return string
     */
    public function getUsername();
    /**
     * @param string $password
     * 
     * @return static
     */
    public function setPassword($password);

    /**
     * @return string
     */
    public function getPassword();

    /**
     * @param string $dbname
     * 
     * @return static
     */
    public function setDbname($dbname);

    /**
     * @return string
     */
    public function getDbname();

    /**
     * @return static
     */
    public function connect();

    /**
     * @param \PDO $connection
     * 
     * @return static
     */
    public function setConnection($connection = null);

    /**
     * @return \PDO
     */
    public function getConnection();

    /**
     * @param string $sql
     * 
     * @return PDOStatement
     */
    public function prepare($sql);

    /**
     * @return bool
     */
    public function beginTransaction();

    /**
     * @return bool
     */
    public function commit();

    /**
     * @return bool
     */
    public function rollback();
}
