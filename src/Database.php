<?php

namespace Wilkques\Database;

class Database implements \JsonSerializable, \ArrayAccess
{
    /** @var array */
    protected $data;
    /** @var array */
    protected $bindData = array();
    /** @var array */
    protected $conditionData = array();
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
     * @param array|string $key
     * @param string|mixed|null $condition
     * @param mixed|null $value
     * 
     * @return static
     */
    public function where($key, $condition = null, $value = null)
    {
        return $this->whereCondition($key, $condition, $value);
    }

    /**
     * @param array|string $key
     * @param string|mixed|null $condition
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhere($key, $condition = null, $value = null)
    {
        return $this->whereCondition($key, $condition, $value, "OR", "orWhere");
    }

    /**
     * @param array|string $key
     * @param string|mixed $condition
     * @param mixed|null $value
     * @param string $andOr
     * @param string $method
     * 
     * @return static
     */
    protected function whereCondition($key, $condition, $value = null, string $andOr = "AND", string $method = "where")
    {
        if (is_array($key)) {
            array_map(function ($item) use ($method) {
                call_user_func_array(array($this, $method), $item);
            }, $key);

            return $this;
        }

        if (!$value) {
            $value = $condition;
            $condition = "=";
        }

        return $this->setConditionData($value)
            ->setConditionQuery($key, $condition, $andOr);
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
     * @param array $bindData
     * 
     * @return static
     */
    public function withBindData($bindData = array())
    {
        $this->bindData = $bindData;

        return $this;
    }

    /**
     * @return static
     */
    public function setBindData()
    {
        $bindData = func_num_args() > 1 ? func_get_args() : func_get_args()[0];

        if (is_array($bindData)) {
            array_map(function ($item) {
                if (is_array($item)) return $this->setBindData($item);

                $this->bindData[] = $item;
            }, $bindData);
        } else {
            $this->bindData[] = $bindData;
        }

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
            switch (true) {
                case is_bool($item):
                    $varType = \PDO::PARAM_BOOL;
                    break;
                case is_int($item):
                    $varType = \PDO::PARAM_INT;
                    break;
                case is_null($item):
                    $varType = \PDO::PARAM_NULL;
                    break;
                default:
                    $varType = \PDO::PARAM_STR;
            }

            $statement->bindParam(++$index, $item, $varType);
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
        $this->compilerSelect(true);

        return $this->exec();
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->setBindQuery("COUNT(*) as count")->compilerSelect();

        return (int) $this->exec()->count;
    }

    /**
     * @return static
     */
    public function getForPage()
    {
        $this->getGrammar()->getForPage();

        $items = $this->execReturn($this->compiler());

        $total = $this->count();

        return $this->setData(compact('total', 'items'));
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function update($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        $this->withBindData()->setBindData(array_values($data))->compilerUpdate($data);

        return $this->exec();
    }

    /**
     * @param string $column
     * @param int|string $value
     * @param array $data
     * 
     * @return static
     */
    public function increment($column, $value = 1, $data = array())
    {
        !is_numeric($value) && $this->argumentsThrowError(" second Arguments must be numeric");

        $bindData = array_values($data);

        $bindData[] = $value;

        $this->withBindData()->setBindData($bindData)->compilerUpdate(
            array_merge($data, array("{$column}" => "{$column} +"))
        );

        return $this->exec();
    }

    /**
     * @param string $column
     * @param int|string $value
     * @param array $data
     * 
     * @return static
     */
    public function decrement($column, $value = 1, $data = array())
    {
        !is_numeric($value) && $this->argumentsThrowError(" second Arguments must be numeric");

        $bindData = array_values($data);

        $bindData[] = $value;

        $this->withBindData()->setBindData($bindData)->compilerUpdate(
            array_merge($data, array("{$column}" => "{$column} -"))
        );

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
     * @param string $column
     * @param string $dateTimeFormat
     * 
     * @return static
     */
    public function softDelete($column = 'deleted_at', $dateTimeFormat = "Y-m-d H:i:s")
    {
        !is_string($column) && $this->argumentsThrowError(" first Arguments must be string");

        $value = date($dateTimeFormat);

        $this->withBindData()->setBindData($value)->compilerUpdate([
            $column => $value
        ]);

        return $this->exec();
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function reStore($column = 'deleted_at')
    {
        !is_string($column) && $this->argumentsThrowError(" first Arguments must be string");

        $value = null;

        $this->withBindData()->setBindData($value)->compilerUpdate([
            $column => $value
        ]);

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

        return $this->exec()->withBindData();
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
        preg_match("/(SELECT|select|Select)\s+(COUNT|\`|\w|\*)/i", $this->getQuery(), $matches);

        if ($matches) {
            if (preg_match("/(LIMIT 1)/i", $this->getQuery()) || in_array('COUNT', $matches)) {
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
    public function compilerBindDataHandle($data = array())
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
     * @param string|callable|\Exception $error
     * 
     * @throws \Exception|\Wilkques\Database\Exceptions\DataNotExistsException
     * 
     * @return static
     */
    public function throws($error = "Data not exiexts")
    {
        if (!$this->toArray()) {
            if (is_callable($error))
                throw $error($this);

            if (is_string($error))
                throw new \Wilkques\Database\Exceptions\DataNotExistsException($error);

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
        // $this->getConnection()->setConnection();
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

        if (!$connection && in_array($method, ["connection", "setConnection"])) {
            return call_user_func_array(array($this, "setConnection"), $arguments);
        }

        if (method_exists($connection, $method)) {
            $database = call_user_func_array(array($connection, $method), $arguments);

            if (is_object($database)) return $this;

            return $database;
        }

        $grammar = $this->getGrammar();

        if (!$grammar && in_array($method, ["setGrammar", "grammar"])) {
            return call_user_func_array(array($this, "setGrammar"), $arguments);
        }

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
