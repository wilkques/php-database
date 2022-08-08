<?php

namespace Wilkques\Database\Queries\Grammar;

abstract class Grammar implements GrammarInterface
{
    /** @var string */
    protected $query;
    /** @var string */
    protected $table;
    /** @var string */
    protected $lock = "";
    /** @var array */
    protected $bindQueries = array();

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
     * @param array $bindQueries
     * 
     * @return static
     */
    public function withBindQueries(array $bindQueries)
    {
        $this->bindQueries = $bindQueries;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function setBindQueries(string $key, $value = null)
    {
        $bindQueries = $this->getBindQueries();

        array_set($bindQueries, $key, $value);

        $this->bindQueries = $bindQueries;

        return $this;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * 
     * @return string|array
     */
    public function getBindQueries(string $key = null, $default = null)
    {
        $bindQueries = $this->bindQueries;

        return array_get($bindQueries, $key, $default);
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
        if (is_string($query)) {
            $query = preg_replace("/(\w+)|,\s?+$/i",  "`$1`", $query);
        }

        $index = $this->nextArrayIndex($this->getBindQueries("select"));

        return $this->setBindQueries("select.{$index}", (string) $query);
    }

    /**
     * @param array $data
     * 
     * @return int
     */
    public function nextArrayIndex($data)
    {
        $index = 0;

        $data && $index = array_key_last($data) + 1;

        return $index;
    }

    /**
     * @param string $bindParam
     * @param string $column
     * @param string $condition
     * @param string $operate
     * @param string $value
     * 
     * @return static
     */
    public function setConditionQuery(string $bindParam, $column, $condition = null, $operate = null, $value = "?")
    {
        $bindQueries = $this->getBindQueries($bindParam);

        $index = $this->nextArrayIndex($bindQueries);

        $operate = $bindQueries ? "{$operate} " : "";

        $sql = "{$operate}`{$column}` {$condition} {$value}";

        if (is_object($column) && $this->isSameClassName($column, \Wilkques\Database\Queries\Expression::class)) {
            $sql = $operate . (string) $column;
        }

        return $this->setBindQueries("{$bindParam}.{$index}", $sql);
    }

    /**
     * @param string $column
     * @param string $condition
     * @param string $operate
     * @param string $value
     * 
     * @return static
     */
    public function setWhereQuery($column, $condition = null, $operate = null, $value = "?")
    {
        return $this->setConditionQuery("where", $column, $condition, $operate, $value);
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
                $this->setWhereQuery($item, "IS", "AND", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setWhereQuery($column, "IS", "AND", "NULL");
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
                $this->setWhereQuery($item, "IS", "OR", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setWhereQuery($column, "IS", "OR", "NULL");
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
                $this->setWhereQuery($item, "IS NOT", "AND", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setWhereQuery($column, "IS NOT", "AND", "NULL");
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
                $this->setWhereQuery($item, "IS NOT", "OR", "NULL");
            }, $column);
        } else if (is_string($column)) {
            $this->setWhereQuery($column, "IS NOT", "OR", "NULL");
        }

        return $this;
    }

    /**
     * @param string $bindQuery
     * 
     * @return static
     */
    public function setBindQuery($bindQuery = "")
    {
        return $this->setBindQueries("bindQuery", $bindQuery);
    }

    /**
     * @return string
     */
    public function getBindQuery()
    {
        return $this->getBindQueries("bindQuery");
    }

    /**
     * @param string $bindColumnQuery
     * 
     * @return static
     */
    public function setBindColumnQuery($bindColumnQuery = "")
    {
        return $this->setBindQueries("bindColumnQuery", $bindColumnQuery);
    }

    /**
     * @return string
     */
    public function getBindColumnQuery()
    {
        return $this->getBindQueries("bindColumnQuery");
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function setUpdate($data)
    {
        array_map(function ($item, $index) {
            $column = is_object($item) ? $item : $index;

            $this->setConditionQuery("update", $column, "=", ",");
        }, $data, array_keys($data));

        return $this;
    }

    /**
     * @return static
     */
    public function compilerUpdate()
    {
        $update = join("", $this->getbindQueries("update"));

        $sql = "UPDATE `{$this->getTable()}` SET {$update}";

        $where = $this->getOnlyBindQueries(["where"]);

        $where && $sql .= " " . $this->arrayToSql($where);

        return $this->setQuery($sql);
    }

    /**
     * @param string $index
     * @param string $item
     * @param string $condition
     * 
     * @return string
     */
    protected function indexReCondition($index, $item, $condition = "=")
    {
        if (preg_match("/($index)(?(?=\s?(\+|\-)))/i", $item, $matches)) {
            // match + or -
            $matches[2] && $condition .= " `{$matches[1]}` {$matches[2]}";
        }

        return $condition;
    }

    /**
     * @param string $conditionQuery
     * 
     * @return static
     */
    public function withConditionQuery($conditionQuery)
    {
        return $this->setBindQueries("conditionQuery", $conditionQuery);
    }

    /**
     * @return string
     */
    public function getConditionQuery()
    {
        return $this->getBindQueries("conditionQuery");
    }

    /**
     * @return string
     */
    protected function compilerWhere()
    {
        return $this->getConditionQuery() ? " WHERE {$this->getConditionQuery()}" : "";
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
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindQueries(array $keys)
    {
        return \array_only($this->getBindQueries(), $keys);
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindFieldQueries(array $keys)
    {
        return \array_field($this->getOnlyBindQueries($keys), $keys);
    }

    /**
     * @return array
     */
    public function getForSelectQueries()
    {
        $keys = ["where", "groupBy", "orderBy", "limit", "offset", "lock"];

        return $this->getOnlyBindFieldQueries($keys);
    }

    /**
     * @return static
     */
    public function compilerSelect()
    {
        $column = $this->getBindQueries("select", "*");

        $column = is_string($column) ? $column : join(", ", $column);

        $sql = "SELECT {$column} FROM `{$this->getTable()}`";

        $selectAry = $this->getForSelectQueries();

        $selectAry && $sql .= " " . $this->arrayToSql($selectAry);

        return $this->setQuery($sql);
    }

    /**
     * @param array $array
     * @param string|array $separator
     * 
     * @return string
     */
    protected function arrayToSql(array $array, $separator = " ")
    {
        return join(" ", array_map(function ($item, $index) use ($separator) {
            return strtoupper($index) . " " . (is_array($item) ? join($separator, $item) : $item);
        }, $array, array_keys($array)));
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
     * @param array|string $column
     */
    public function setSelect($column = ['*'])
    {
        func_num_args() > 1 && $column = func_get_args();

        if (is_array($column)) {
            array_map(function ($item) {
                !is_string($item) || (is_object($item) && $this->isNotSameClassName($item, \Wilkques\Database\Queries\Expression::class)) &&
                    $this->argumentsThrowError(" first Arguments must be array or string or \Wilkques\Database\Queries\Expression class");

                $this->selectBindQuery($item);
            }, $column);
        } else if (is_string($column) || $this->isSameClassName($column, \Wilkques\Database\Queries\Expression::class)) {
            $this->selectBindQuery($column);
        } else {
            $this->argumentsThrowError(" first Arguments must be array or string");
        }

        return $this;
    }

    /**
     * @param object $object
     * @param string $className
     * 
     * @return bool
     */
    public function isSameClassName($object, string $className)
    {
        return get_class($object) === $className;
    }

    /**
     * @param object $object
     * @param string $className
     * 
     * @return bool
     */
    public function isNotSameClassName($object, string $className)
    {
        return !$this->isSameClassName($object, $className);
    }

    /**
     * @param string $column
     * @param string $sort
     * 
     * @return static
     */
    public function setOrderBy($column, $sort = "ASC")
    {
        $orderBy = $this->getOrderBy();

        $query = "`{$column}` {$sort}";

        $orderBy && $orderBy = " ORDER BY {$query}";

        $orderBy = $this->columnBindQuery($orderBy, $query);

        return $this->setBindQueries("orderBy", $orderBy);
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->getBindQueries("orderBy");
    }

    /**
     * @param string $orderby
     * 
     * @return static
     */
    public function setGroupBy($groupBy)
    {
        $groupBy = $this->getGroupBy();

        $groupBy && $groupBy = " GROUP BY `{$groupBy}`";

        $groupBy = $this->columnBindQuery($groupBy, $groupBy);

        return $this->setBindQueries("groupBy", $groupBy);
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->getBindQueries("groupBy");
    }

    /**
     * @param int|string $limit
     * 
     * @return static
     */
    public function setLimit($limit = "?")
    {
        return $this->setBindQueries("limit", $limit);
    }

    /**
     * @return int|string
     */
    public function getLimit()
    {
        return $this->getBindQueries("limit", 1);
    }

    /**
     * @param int|string $offset
     * 
     * @return static
     */
    public function setOffset($offset = "?")
    {
        return $this->setBindQueries("offset", $offset);
    }

    /**
     * @return int|string
     */
    public function getOffset()
    {
        return $this->getBindQueries("offset");
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
     * @param array $carry
     * @param array $item
     * 
     * @return array
     */
    protected function insertQueriesReduce($carry, $item)
    {
        $item = array_keys($item);

        $insertColumns = $this->getBindQueries("insert.columns");

        if (!$insertColumns) {
            $this->setBindQueries("insert.columns", $item);

            $item = array_only($item, array_keys($item));
        }

        $index = $carry === null ? 0 : ((int) array_key_last($carry) + 1);

        foreach ($item as $key => $value) {
            $carry[$index][] = "?";
        }

        return $carry;
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function setInsert(array $data)
    {
        if (array_key_exists(0, $data)) {
            $data = array_reduce($data, array($this, "insertQueriesReduce"));

            return $this->setBindQueries("insert.values", $data);
        }

        return $this->setBindQueries("insert.columns", array_keys($data))
            ->setBindQueries("insert.values.0", array_fill(0, count($data), "?"));
    }

    /**
     * @return static
     */
    public function compilerInsert()
    {
        $inserts = $this->getBindQueries("insert");

        $columns = join("`, `", $inserts["columns"]);

        $values = $this->arrayToInsertValuesSql($inserts["values"]);

        $sql = "INSERT INTO `{$this->getTable()}` (`{$columns}`) VALUES ({$values})";
        
        return $this->setQuery($sql);
    }

    /**
     * @param array $array
     * 
     * @return string
     */
    protected function arrayToInsertValuesSql(array $array)
    {
        return join("), (", array_map(function ($item) {
            return join(", ", $item);
        }, $array));
    }

    /**
     * @return static
     */
    public function compilerDelete()
    {
        $sql = "DELETE FROM `{$this->getTable()}`";

        $where = $this->getOnlyBindQueries(["where"]);

        $where && $sql .= " " . $this->arrayToSql($where);

        return $this->setQuery($sql);
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
