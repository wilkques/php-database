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
class DB implements \JsonSerializable, \ArrayAccess
{
    /** @var array */
    protected $data;
    /** @var array */
    protected $bindData = [];
    /** @var array */
    protected $queryLog = [];
    /** @var array */
    protected $conditionData = [];
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
     * @param mixed $conditionData
     * 
     * @return static
     */
    public function setConditionData($conditionData)
    {
        $this->conditionData[] = $conditionData;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditionData()
    {
        return $this->conditionData;
    }

    /**
     * @param mixed|null $conditionData
     * 
     * @return static
     */
    public function withConditionData($conditionData = null)
    {
        $this->conditionData = $conditionData;

        return $this;
    }

    /**
     * @param array $bindData
     * 
     * @return static
     */
    protected function bindQueryLog($bindData)
    {
        $query = $this->getQueryLog();

        $data = end($query);

        $key = key($query);

        $queryString = $this->getQuery();

        $this->queryLog[$key] = compact('queryString', 'bindData');

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * @return array
     */
    public function latestQueryLog()
    {
        return end($this->getQueryLog());
    }

    /**
     * @param array|string $key
     * @param string $condition
     * @param mixed $value
     * 
     * @return static
     */
    public function where($key, $condition = null, $value = null)
    {
        if (is_array($key)) {
            array_map(function ($item) {
                call_user_func_array(array($this, 'where'), $item);
            }, $key);

            return $this;
        }

        return $this->setConditionData($value)
            ->setConditionQuery($key, $condition);
    }

    /**
     * @param array|string $key
     * @param string $condition
     * @param mixed $value
     * 
     * @return static
     */
    public function orWhere($key, $condition = null, $value = null)
    {
        if (is_array($key)) {
            array_map(function ($item) {
                call_user_func_array(array($this, 'orWhere'), $item);
            }, $key);

            return $this;
        }

        return $this->setConditionData($value)
            ->setConditionQuery($key, $condition, "OR");
    }

    /**
     * @param string $column
     * @param array  $data
     * 
     * @return static
     */
    public function whereIn($column, $data)
    {
        !is_string($column) && $this->argumentsThrowError(" First Arguments must be string");

        !is_array($data) && $this->argumentsThrowError(" Second Arguments must be array");

        array_map(function ($item) {
            is_array($item) && $this->argumentsThrowError(" Second Arguments only one-dimensional array");
        }, $data);

        $query = implode(", ", array_fill(0, count($data), "?"));

        return $this->withConditionData($data)->withConditionQuery("`{$column}` IN ({$query})");
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
     * @return static
     */
    public function setBindData()
    {
        $bindData = func_num_args() > 1 ? func_get_args() : func_get_args()[0];

        array_map(function ($item) {
            if (is_array($item)) return $this->setBindData($item);

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
     * @return static
     */
    public function get()
    {
        $this->compilerSelect();

        return $this->exec();
    }

    /**
     * @return static
     */
    public function first()
    {
        $this->limit(1)->compilerSelect();

        return $this->exec();
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function update($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        $this->setBindData(array_values($data))->compilerUpdate($data);

        return $this->exec();
    }

    /**
     * @return static
     */
    public function delete()
    {
        $this->compilerDelete();

        return $this->exec();
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function insert($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        $this->withConditionData()->setBindData(array_values($data))->compilerInsert($data);

        return $this->exec();
    }

    /**
     * @return static
     */
    public function exec()
    {
        return $this->setData($this->execReturn($this->compiler()));
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

    /**
     * @return static
     */
    protected function compiler()
    {
        try {
            $statement = $this->getConnection()->prepare($this->getQuery());

            if ($data = $this->compilerBindDataHandle()) {
                $this->dataBinding($data, $statement);
            }

            $this->bindQueryLog($data);

            $statement->execute();

            return $statement;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    protected function compilerBindDataHandle(&$data = [])
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
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        $methods = array(
            'table', 'username', 'password', 'dbname', "host", "query", "bindData", "select",
            "orderBy", "groupBy", "limit", "offset", "connection", "grammar", "currentPage",
            "prePage"
        );

        if (in_array($method, $methods)) {
            $method = "set" . ucfirst($method);
        }

        return $method;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param string $offset
     * 
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param string $offset
     * 
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) unset($this->data[$offset]);
    }

    /**
     * Get a data by key
     *
     * @param string The key data to retrieve
     * @access public
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Assigns a value to the specified data
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     * @access public
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Whether or not an data exists by key
     *
     * @param string An data key to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Unsets an data by key
     *
     * @param string The key to unset
     * @access public
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
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
        $method = $this->method($method);

        $connection = $this->getConnection();

        if (method_exists($this->getConnection(), $method)) {
            return call_user_func_array(array($connection, $method), $arguments);
        }

        $grammar = $this->getGrammar();

        if (method_exists($grammar, $method)) {
            $grammar = call_user_func_array(array($grammar, $method), $arguments);

            if (is_object($grammar)) return $this;

            return $grammar;
        }

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
        $instance = new static;

        return call_user_func_array(array($instance, $method), $arguments);
    }
}
