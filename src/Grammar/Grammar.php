<?php

namespace Wilkques\Database\Grammar;

use Wilkques\Database\GrammarInterface;

abstract class Grammar implements GrammarInterface
{
    /** @var string */
    protected $query;
    /** @var string */
    protected $table;
    /** @var string */
    protected $bindColumnQuery = "";
    /** @var string */
    protected $bindQuery = "";
    /** @var integer */
    protected $prePage = 10;
    /** @var integer */
    protected $currentPage = 1;
    /** @var string */
    protected $orderBy = "";
    /** @var string */
    protected $groupBy = "";
    /** @var integer */
    protected $limit = null;
    /** @var integer */
    protected $offset = null;
    /** @var string */
    protected $conditionQuery = "";
    /** @var string */
    protected $lock = "";

    /**
     * @param string $table
     * 
     * @return static
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $query
     * @param string $column
     * @param string $options
     * 
     * @return string
     */
    protected function columnBindQuery($query, $column, $options = "")
    {
        $query .= $query == "" ? "`{$column}`{$options}" : ", `{$column}`{$options}";

        return $query;
    }

    /**
     * @param string $query
     * 
     * @return static
     */
    protected function selectBindQuery($query)
    {
        $query = preg_replace("/(\s+as\s+)/i", "` AS `", $query);

        $bindQuery = $this->getBindQuery();

        return $this->setBindQuery($this->columnBindQuery($bindQuery, $query));
    }

    /**
     * @param string $bindQuery
     * 
     * @return static
     */
    public function setBindQuery($bindQuery = "")
    {
        $this->bindQuery = $bindQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getBindQuery()
    {
        return $this->bindQuery;
    }

    /**
     * @param string $bindColumnQuery
     * 
     * @return static
     */
    public function setBindColumnQuery($bindColumnQuery = "")
    {
        $this->bindColumnQuery = $bindColumnQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getBindColumnQuery()
    {
        return $this->bindColumnQuery;
    }

    /**
     * @param string $key
     * @param string $condition
     * 
     * @return static
     */
    protected function updateBindQuery($key, $condition)
    {
        $bindQuery = $this->getBindQuery();

        $query = " {$condition} ?";

        return $this->setBindQuery($this->columnBindQuery($bindQuery, $key, $query));
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function compilerUpdate($data)
    {
        $this->setLimit()->setOffset()->setBindQuery();

        array_map(function ($item, $index) {
            if (is_array($item)) $this->compilerUpdate($item);

            $this->updateBindQuery($index, "=");
        }, $data, array_keys($data));

        return $this->setQuery(
            "UPDATE `{$this->getTable()}` SET {$this->getBindQuery()} WHERE {$this->getConditionQuery()}"
        );
    }

    /**
     * @param string $conditionQuery
     * 
     * @return static
     */
    public function withConditionQuery($conditionQuery)
    {
        $this->conditionQuery = $conditionQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getConditionQuery()
    {
        return $this->conditionQuery;
    }

    /**
     * @param string $key
     * @param string $condition
     * @param string $andOr
     * @param string $value
     * 
     * @return static
     */
    public function setConditionQuery($key, $condition, $andOr = "AND", $value = "?")
    {
        if ($this->conditionQuery != "") $this->conditionQuery .= " {$andOr} ";

        $this->conditionQuery .= "`{$key}` {$condition} {$value}";

        return $this;
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function whereNull($column)
    {
        if (is_array($column)) {
            array_map(function ($item) {
                $this->setConditionQuery($item, "IS", "AND", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setConditionQuery($column, "IS", "AND", "NULL");
        }

        return $this;
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function whereOrNull($column)
    {
        if (is_array($column)) {
            array_map(function ($item) {
                $this->setConditionQuery($item, "IS", "OR", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setConditionQuery($column, "IS", "OR", "NULL");
        }

        return $this;
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function whereNotNull($column)
    {
        if (is_array($column)) {
            array_map(function ($item) {
                $this->setConditionQuery($item, "IS NOT", "AND", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setConditionQuery($column, "IS NOT", "AND", "NULL");
        }

        return $this;
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function whereOrNotNull($column)
    {
        if (is_array($column)) {
            array_map(function ($item) {
                $this->setConditionQuery($item, "IS NOT", "OR", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setConditionQuery($column, "IS NOT", "OR", "NULL");
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function compilerWhere()
    {
        return $this->getConditionQuery() == "" ? "" : " WHERE {$this->getConditionQuery()}";
    }

    /**
     * @param string $lock
     * 
     * @return static
     */
    public function setLock($lock = "")
    {
        $this->lock = $lock;

        return $this;
    }

    /**
     * @return string
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function argumentsThrowError($message = "")
    {
        throw new \UnexpectedValueException(
            sprintf(
                "DB::%s arguments is error.%s",
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
                $message
            )
        );
    }

    /**
     * @return static
     */
    public function compilerSelect($withFirst = false)
    {
        $column = $this->getBindQuery() ?: "*";

        $sql = "SELECT {$column} FROM `{$this->getTable()}`%s%s%s%s%s%s";

        $query = sprintf(
            $sql,
            $this->compilerWhere(),
            $this->getGroupBy(),
            $this->getOrderBy(),
            $this->compilerLimit($withFirst),
            $this->compilerOffset(),
            $this->getLock()
        );

        return $this->setQuery($query);
    }

    /**
     * @param bool $withFirst
     * 
     * @return string
     */
    protected function compilerLimit($withFirst = false)
    {
        if ($withFirst) return " LIMIT 1";

        return $this->getLimit() !== null ? " LIMIT ?" : "";
    }

    /**
     * @return string
     */
    protected function compilerOffset()
    {
        return $this->getOffset() !== null ? " OFFSET ?" : "";
    }

    /**
     * @param string $query
     * 
     * @return static
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param integer $prePage
     * 
     * @return static
     */
    public function setPrePage($prePage = 10)
    {
        $this->prePage = $prePage;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPrePage()
    {
        return $this->prePage;
    }

    /**
     * @param integer $prePage
     * 
     * @return static
     */
    public function setCurrentPage($currentPage = 1)
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param array|string $column
     */
    public function setSelect($column = ['*'])
    {
        func_num_args() > 1 && $column = func_get_args();

        if (is_array($column)) {
            array_map(function ($item) {
                !is_string($item) && $this->argumentsThrowError(" first Arguments must be array or string");

                $this->selectBindQuery($item);
            }, $column);
        } else if (is_string($column)) {
            $this->selectBindQuery($column);
        } else {
            $this->argumentsThrowError(" first Arguments must be array or string");
        }

        return $this;
    }

    /**
     * @return static
     */
    public function getForPage()
    {
        $offset = ($this->getCurrentPage() - 1) * $this->getPrePage();

        return $this->setLimit($this->getPrePage())
            ->setOffset($offset)
            ->compilerSelect();
    }

    /**
     * @param string $column
     * @param string $sort
     * 
     * @return static
     */
    public function setOrderBy($column, $sort = "ASC")
    {
        $query = "`{$column}` {$sort}";

        $this->orderBy == "" && $this->orderBy = " ORDER BY {$query}";

        $this->orderBy = $this->columnBindQuery($this->orderBy, $query);

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param string $orderby
     * 
     * @return static
     */
    public function setGroupBy($groupBy)
    {
        $this->groupBy == "" && $this->groupBy = " GROUP BY `{$groupBy}`";

        $this->groupBy = $this->columnBindQuery($this->groupBy, $groupBy);

        return $this;
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param integer $limit
     * 
     * @return static
     */
    public function setLimit($limit = null)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param integer $offset
     * 
     * @return static
     */
    public function setOffset($offset = null)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param string $query
     * 
     * @return string
     */
    protected function insertBindQuery($query)
    {
        $bindQuery = $this->getBindQuery();

        $bindQuery .= $bindQuery == "" ? "{$query}" : ", {$query}";

        return $bindQuery;
    }

    /**
     * @param string $query
     * 
     * @return string
     */
    protected function insertBindColumnQuery($query)
    {
        $bindQuery = $this->getBindColumnQuery();

        return $this->columnBindQuery($bindQuery, $query);
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    protected function insertHandle($data)
    {
        array_map(function ($item) {
            if (is_array($item)) {
                $this->insertHandle($item);
            }
        }, $data);

        if (isset($data[0])) return $this;

        return $this->setBindColumnQuery(
            sprintf("(`%s`)", implode("`, `", array_keys($data)))
        )->setBindQuery(
            $this->insertBindQuery(
                sprintf("(%s)", implode(", ", array_fill(0, count($data), "?")))
            )
        );
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function compilerInsert($data)
    {
        return $this->setLimit()
            ->setOffset()
            ->setBindQuery()
            ->insertHandle($data)
            ->setQuery(
                "INSERT INTO `{$this->getTable()}` {$this->getBindColumnQuery()} VALUES {$this->getBindQuery()}"
            );
    }

    /**
     * @return static
     */
    public function compilerDelete()
    {
        return $this->setLimit()->setOffset()->setQuery(
            "DELETE FROM `{$this->getTable()}` WHERE {$this->getConditionQuery()}"
        );
    }

    /**
     * @return static
     */
    abstract function lockForUpdate();

    /**
     * @return static
     */
    abstract function sharedLock();
}
