<?php

namespace Wilkques\Database\Connections\PDO;

use Exception;
use Wilkques\Database\Connections\Connections;
use Wilkques\Database\Connections\Traits\DetectsLostConnections;

abstract class PDO extends Connections
{
    use DetectsLostConnections;

    /** @var int */
    protected $transactions = 0;

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
    public function connection($dns = null)
    {
        $pdo = new \PDO(
            $dns ?: $this->getDNS(),
            $this->getUsername(),
            $this->getPassword()
        );

        if ($character = $this->getCharacterSet()) {
            $pdo->exec("SET NAMES {$character}");
        }

        return $pdo;
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->transactions++) {
            $this->reconnectIfMissingConnection();

            try {
                return $this->getConnection()->beginTransaction();
            } catch (\Exception $e) {
                $this->handleBeginTransactionException($e);
            }
        } else if ($this->transactions >= 1 && $this->grammar->supportsSavepoints()) {
            return $this->exec(
                $this->grammar->compileSavepoint('trans' . ($this->transactions + 1))
            );
        }
    }

    /**
     * Handle an exception from a transaction beginning.
     *
     * @param  \Throwable|\Exception  $e
     * @return void
     *
     * @throws \Throwable|\Exception
     */
    protected function handleBeginTransactionException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reConnection()->getConnection()->beginTransaction();
        } else {
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function commit()
    {
        if (!--$this->transactions) {
            return $this->getConnection()->commit();
        }

        return $this->transactions >= 0;
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        try {
            if (--$this->transactions && $this->grammar->supportsSavepoints()) {
                $this->exec(
                    $this->grammar->compileSavepoint('trans' . ($this->transactions + 1))
                );

                return true;
            }

            return $this->getConnection()->rollBack();
        } catch (\Exception $e) {
            $this->handleRollBackException($e);
        }
    }

    /**
     * Handle an exception from a rollback.
     *
     * @param  \Throwable|\Exception  $e
     * @return void
     *
     * @throws \Throwable|\Exception
     */
    protected function handleRollBackException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;

            $this->reConnection()->getConnection()->rollBack();
        }

        throw $e;
    }

    /**
     * @return bool
     */
    public function inTransation()
    {
        return $this->transactions > 0;
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
    public function newConnection($dns = null)
    {
        return $this->setConnection($this->connection($dns))
            ->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param string|null $dns
     * 
     * @return static
     */
    public function reConnection($dns = null)
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
            $this->reConnection();

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
            $this->reConnection();
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
        $this->setDatabase($database)->exec("use `{$database}`;");

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getDNS();
}
