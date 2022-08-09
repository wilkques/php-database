<?php

namespace Wilkques\Database\Connections\PDO;

class Result
{
    /** @var \PDOStatement */
    protected $statement;

    /**
     * @param \PDOStatement $statement
     */
    public function __construct(\PDOStatement $statement)
    {
        $this->setStatement($statement);
    }

    /**
     * @param \PDOStatement $statement
     * 
     * @return static
     */
    public function setStatement(\PDOStatement $statement)
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
    public function fetchOne()
    {
        return $this->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirst()
    {
        return $this->fetch(\PDO::FETCH_ASSOC);
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
    public function fetchFirstColumn()
    {
        return $this->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        return $this->getStatement()->rowCount();
    }

    /**
     * @return int
     */
    public function columnCount(): int
    {
        return $this->getStatement()->columnCount();
    }

    public function free(): void
    {
        $this->getStatement()->closeCursor();
    }

    /**
     * @return mixed|false
     *
     * @throws \Exception
     */
    public function fetch(int $mode = \PDO::FETCH_ASSOC)
    {
        return $this->getStatement()->fetch($mode);
    }

    /**
     * @return list<mixed>
     *
     * @throws \Exception
     */
    public function fetchAll(int $mode = \PDO::FETCH_ASSOC)
    {
        return $this->getStatement()->fetchAll($mode);
    }
}
