<?php

namespace Wilkques\Database\Connections\PDO;

use Exception;
use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Connections\Connections;
use Wilkques\Database\Connections\Traits\DetectsLostConnections;

abstract class PDO extends Connections implements ConnectionInterface
{
    use DetectsLostConnections;

    /**
     * @param int $attribute
     * @param mixed $value
     * 
     * @return static
     */
    public function setAttribute($attribute, $value)
    {
        /** @var \PDO */
        $pdo = $this->getConnection();

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
        return new Result($this->getConnection()->query($sql), $this);
    }

    /**
     * @param string $sql
     * 
     * @return Statement
     */
    public function prepare($sql)
    {
        return new Statement($this->getConnection()->prepare($sql), $this);
    }

    /**
     * @param string|null $dns
     * 
     * @return \PDO
     */
    public function connection(string $dns = null)
    {
        return new \PDO(
            $dns ?: $this->getDNS(),
            $this->getUsername(),
            $this->getPassword()
        );
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
     * @param string|null $sequence
     * 
     * @return int|string
     */
    public function getLastInsertId($sequence = null)
    {
        return $this->getConnection()->lastInsertId($sequence);
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    public function newConnection(string $dns = null)
    {
        return $this->setConnection($this->connection($dns))
            ->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    public function reConnecntion(string $dns = null)
    {
        return $this->newConnection($dns);
    }

    /**
     * @param string|null $query
     * @param array $bindings
     * 
     * @return Result
     */
    protected function run($query, $bindings = array())
    {
        $statement = $this->prepare($query);

        $bindings = $bindings ?: array();

        if (!empty($bindings)) {
            $statement->bindParams($bindings);
        }

        if ($this->isLogging()) {
            $bindings = $statement->getParams();

            $this->setQueryLog(compact('query', 'bindings'));
        }

        return $statement->execute();
    }

    /**
     * @param string|null $query
     * @param array $bindings
     * 
     * @return Result
     */
    public function exec($query, $bindings = array())
    {
        try {
            $this->reconnectIfMissingConnection();

            return $this->run($query, $bindings);
        } catch (Exception $e) {
            $result = $this->tryAgainIfCausedByLostConnection($e, $query, $bindings);

            if ($result instanceof Exception) {
                throw $result;
            }

            return $result;
        }
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  Exception  $e
     * @param  string  $query
     * @param  array  $bindings
     * 
     * @return Result
     *
     * @throws Exception
     */
    protected function tryAgainIfCausedByLostConnection(Exception $e, $query, $bindings)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reConnecntion();

            return $this->run($query, $bindings);
        }

        throw $e;
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return static
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->getConnection())) {
            $this->reConnecntion();
        }

        return $this;
    }

    /**
     * @param string $database
     * 
     * @return static
     */
    public function selectDatabase($database)
    {
        $this->exec("use `{$database}`;");

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getDNS();
}
