<?php

namespace Wilkques\Database\Connections\PDO;

use Wilkques\Database\Connections\Connections;
use Wilkques\Database\Connections\ResultInterface;

class Result implements ResultInterface
{
    /** @var \PDOStatement */
    protected $statement;

    /** @var Connections */
    protected $connections;

    /**
     * @param \PDOStatement $statement
     */
    public function __construct($statement, Connections $connections)
    {
        $this->setStatement($statement)->setConnections($connections);
    }

    /**
     * @param \PDOStatement $statement
     * 
     * @return static
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;

        return $this;
    }

    /**
     * @return \PDOStatement
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * @param Connections $connections
     * 
     * @return static
     */
    public function setConnections(Connections $connections)
    {
        $this->connections = $connections;

        return $this;
    }

    /**
     * @return Connections
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(\PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn()
    {
        return $this->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric()
    {
        return $this->fetchAll(\PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative()
    {
        return $this->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllFirstColumn()
    {
        return $this->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return int
     */
    public function rowCount()
    {
        $result = $this->getStatement()->rowCount();

        $this->free();

        return $result;
    }

    /**
     * @return int
     */
    public function columnCount(): int
    {
        $result = $this->getStatement()->columnCount();

        $this->free();

        return $result;
    }

    /**
     * @return void
     */
    public function free(): void
    {
        $this->getStatement()->closeCursor();
    }

    /**
     * @return mixed|false
     *
     * @throws \Exception
     */
    public function fetch($mode = \PDO::FETCH_ASSOC)
    {
        $result = $this->getStatement()->fetch($mode);

        $this->free();

        return $result;
    }

    /**
     * @return list<mixed>
     *
     * @throws \Exception
     */
    public function fetchAll($mode = \PDO::FETCH_ASSOC)
    {
        $result = $this->getStatement()->fetchAll($mode);

        $this->free();

        return $result;
    }

    /**
     * @return int|string
     */
    public function getLastInsertId()
    {
        $result = $this->getConnections()->getLastInsertId();

        $this->free();

        return $result;
    }
}
