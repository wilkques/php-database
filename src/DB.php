<?php

namespace Wilkques\Database;

/**
 * php >= 5.4
 * 
 * 簡易資料庫操作
 * 
 * create by: wilkques
 * 
 * @method static static connection(ConnectionInterface $connection) set Connection
 * @method static static table(string $table) set table name
 * @method static static username(string $username) set db user name
 * @method static static password(string $password) set db password
 * @method static static dbname(string $dbname) set db name
 * @method static static host(string $host) set db host
 * @method static static newConnect() new db connect
 * @method static static query(string $query) set sql query
 * @method static static bindData(array $data) set bind data
 * @method static static orderBy(string $column, string $sort = "ASC") set order by
 * @method static static groupBy(string $column, string $sort) set group by
 * @method static static limit(int $limit) set limit
 * @method static static offset(int $offset) set offset
 * @method static static select(array|string $column) set column with select
 * @method static static where(array|string $key, $condition = null, $value = null) set where
 * @method static static orWhere(array|string $key, $condition = null, $value = null)
 * @method static static whereIn(string $column, array $data)
 * @method static static whereNull(string|array $column)
 * @method static static whereOrNull(string|array $column)
 * @method static static whereNotNull(string|array $column)
 * @method static static whereOrNotNull(string|array $column)
 * @method static static beginTransaction()
 * @method static static commit()
 * @method static static rollBack()
 * @method static static grammar(GrammarInterface $grammar) set sql server grammar
 * @method static static lockForUpdate() set for update lock
 * @method static static sharedLock() set shared lock
 * @method static static currentPage(int $currentPage) set now page
 * @method static static prePage(int $prePage) set prepage
 */
class DB
{
    /** @var array */
    protected $data;
    /** @var array */
    protected $query;
    /** @var array */
    protected $bindData = [];
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
    /** @var ConnectionInterface */
    protected $connection;
    /** @var GrammarInterface */
    protected $grammar;

    /**
     * @param ConnectionInterface $connection
     * @param GrammarInterface $grammar
     */
    public function __construct(ConnectionInterface $connection = null, GrammarInterface $grammar = null)
    {
        $this->setConnection($connection)->setGrammar($grammar);
    }

    /**
     * @param ConnectionInterface $connection
     * 
     * @return static
     */
    public function setConnection(ConnectionInterface $connection = null)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param GrammarInterface $grammar
     * 
     * @return static
     */
    public function setGrammar(GrammarInterface $grammar = null)
    {
        $this->grammar = $grammar;

        return $this;
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

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
        $query .= $query == "" ? "{$column}{$options}" : ", {$column}{$options}";

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
     * @return static
     */
    public function setBindData()
    {
        $bindData = func_get_args();

        array_map(function ($item) {
            is_array($item) && $this->throws("DB::bindData arguments is error. array Arguments only one-dimensional array");

            $this->bindData[] = $item;
        }, $bindData);

        return $this;
    }

    /**
     * @return array
     */
    public function getBindData()
    {
        return $this->bindData;
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    protected function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return static
     */
    protected function complierSelect()
    {
        return $this->setQuery($this->buildSelectQuery())->exec();
    }

    /**
     * @return string
     */
    protected function buildSelectQuery()
    {
        $column = $this->getBindQuery() ?: "*";

        $sql = "SELECT {$column} FROM `{$this->getTable()}`";

        $sql .= $this->getConditionQuery() == "" ? "" : " WHERE {$this->getConditionQuery()}";

        $sql .= $this->getGroupBy() == "" ? "" : $this->getGroupBy();

        $sql .= $this->getOrderBy() == "" ? "" : $this->getOrderBy();

        $sql .= $this->getLimit() ? " LIMIT ?" : "";

        $sql .= $this->getOffset() !== null ? " OFFSET ?" : "";

        $sql .= $this->getLock();

        return $sql;
    }

    /**
     * @param array $bindData
     * 
     * @return static
     */
    protected function bindQueryLog($bindData)
    {
        $query = $this->query;

        $data = end($query);

        $key = key($query);

        $this->query[$key] = $data + compact('bindData');

        return $this;
    }

    /**
     * @return static
     */
    protected function complier()
    {
        $statement = $this->getConnection()->prepare($this->getQuery());

        if ($data = $this->complierBindDataHandle()) {
            $this->dataBinding($data, $statement);
        }

        $this->bindQueryLog($data);

        $statement->execute();

        return $statement;
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    protected function complierBindDataHandle(&$data = [])
    {
        $data = $this->getBindData();

        $conditionData = $this->getConditionData();

        if (!empty($conditionData)) {
            array_map(function ($item) use (&$data) {
                $data[] = $item;
            }, $conditionData);
        }
        $this->getLimit() && $data[] = $this->getLimit();

        $this->getOffset() !== null && $data[] = $this->getOffset();

        return $data;
    }

    /**
     * @param array $data
     * @param \PDOStatement $statement
     * 
     * @return static
     */
    protected function dataBinding($data, &$statement)
    {
        if (!$data) return $this;

        array_map(function ($item, $index) use (&$statement) {
            $statement->bindParam(++$index, $item, \PDO::PARAM_STR | \PDO::PARAM_INT);
        }, $data, array_keys($data));

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryLog()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function latestQueryLog()
    {
        return end($this->getQueryLog());
    }

    /**
     * @param string $query
     * 
     * @return static
     */
    public function setQuery($query)
    {
        $this->query[]['queryString'] = $query;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return end($this->query)['queryString'];
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
            ->complierSelect();
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
     * @return static
     */
    public function get()
    {
        return $this->complierSelect();
    }

    /**
     * @return static
     */
    public function first()
    {
        return $this->limit(1)->complierSelect();
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function update($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        $this->setLimit()->setOffset()->setBindQuery();

        array_map(function ($item, $index) {
            if (is_array($item)) $this->update($item);

            $this->setBindData($item)->updateBindQuery($index, "=");
        }, $data, array_keys($data));

        return $this->setQuery(
            "UPDATE `{$this->getTable()}` SET {$this->getBindQuery()} WHERE {$this->getConditionQuery()}"
        )->exec();
    }

    /**
     * @return static
     */
    public function delete()
    {
        return $this->throws()->setLimit()->setOffset()->setQuery(
            "DELETE FROM `{$this->getTable()}` WHERE {$this->getConditionQuery()}"
        )->exec();
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
     * @return null|array
     */
    protected function insertHandle($data)
    {
        array_map(function ($item) {
            if (is_array($item)) {
                $this->insertHandle($item);
            } else {
                $this->setBindData($item);
            }
        }, $data);

        if (isset($data[0])) return $this;

        $this->setBindColumnQuery(
            sprintf("(`%s`)", implode("`, `", array_keys($data)))
        )->setBindQuery(
            $this->insertBindQuery(
                sprintf("(%s)", implode(", ", array_fill(0, count($data), "?")))
            )
        );

        return $data;
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function insert($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        $this->setLimit()->setOffset()->withConditionData()->setBindQuery()->insertHandle($data);

        return $this->setQuery(
            "INSERT INTO `{$this->getTable()}` {$this->getBindColumnQuery()} VALUES {$this->getBindQuery()}"
        )->exec();
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
     * @param string|callable|exception $error
     * 
     * @throws BadRequestException|Exception
     * 
     * @return static
     */
    public function throws($error = "Data not exiexts")
    {
        if (!$this->toArray()) {
            if (is_callable($error))
                throw $error($this);

            if (is_string($error))
                throw new \Exception($error);

            if ($error instanceof \Exception)
                throw $error;

            $this->argumentsThrowError(
                " first Arguments must be string or callable or exception"
            );
        }

        return $this;
    }

    /**
     * 直接執行 sql 語句
     * 
     * @return static
     */
    public function exec()
    {
        return $this->setData($this->execReturn($this->complier()));
    }

    /**
     * @param \PDOStatement $statement
     * 
     * @return array|int
     */
    protected function execReturn($statement)
    {
        if (preg_match("/(SELECT|select|Select)/i", $this->getQuery())) {
            if ($this->getLimit() === 1) {
                return $statement->fetch(\PDO::FETCH_ASSOC);
            }

            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $statement->rowCount();
    }

    public function __destruct()
    {
        $this->getConnection()->setConnection();
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public function __call($method, $arguments)
    {
        $methods = array(
            'table', 'username', 'password', 'dbname', "host", "query", "bindData", "select",
            "orderBy", "groupBy", "limit", "offset", "connection", "grammar", "currentPage",
            "prePage"
        );

        if (in_array($method, $methods)) {
            $method = "set" . ucfirst($method);
        }

        $connection = $this->getConnection();

        if (method_exists($this->getConnection(), $method)) {
            // return $this->getConnection()->{$method}(...$arguments);

            return call_user_func_array(array($connection, $method), $arguments);
        }

        $grammar = $this->getGrammar();

        if (method_exists($grammar, $method)) {
            // $grammar = $this->getGrammar()->{$method}(...$arguments);

            $grammar = call_user_func_array(array($grammar, $method), $arguments);

            if (is_object($grammar)) return $this;

            return $grammar;
        }

        // return $this->{$method}(...$arguments);

        return call_user_func_array(array($this, $method), $arguments);
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        // return (new static)->{$method}(...$arguments);

        $instance = new static;

        return call_user_func_array(array($instance, $method), $arguments);
    }
}
