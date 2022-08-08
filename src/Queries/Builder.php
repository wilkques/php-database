<?php

namespace Wilkques\Database\Queries;

use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Queries\Grammar\GrammarInterface;
use Wilkques\Database\Queries\Process\ProcessInterface;

class Builder
{
    /** @var ConnectionInterface */
    protected $connection;
    /** @var GrammarInterface */
    protected $grammar;
    /** @var ProcessInterface */
    protected $process;
    /** @var array */
    protected $bindData = array();
    /** @var array */
    protected $paginate = array(
        "prePage"       => 10,
        "currentPage"   => 0,
    );

    /**
     * @param ConnectionInterface $connection
     * @param GrammarInterface $grammar
     * @param ProcessInterface $process
     */
    public function __construct(
        ConnectionInterface $connection,
        GrammarInterface $grammar = null,
        ProcessInterface $process = null
    ) {
        $this->setConnection($connection)->setGrammar($grammar)->setProcess($process);
    }

    /**
     * @param ConnectionInterface $connection
     * 
     * @return static
     */
    public function setConnection(ConnectionInterface $connection)
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
     * @param ProcessInterface $process
     * 
     * @return static
     */
    public function setProcess(ProcessInterface $process = null)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * @return ProcessInterface
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param int|string $limit
     * 
     * @return static
     */
    public function setLimit($limit)
    {
        $this->getGrammar()->setLimit();

        $index = $this->nextArrayIndex($this->getLimit());

        return $this->setBindData("limit.{$index}", $limit);
    }

    /**
     * @param int|string
     */
    public function getLimit()
    {
        return $this->getBindData("limit");
    }

    /**
     * @param int|string $offset
     * 
     * @return static
     */
    public function setOffset($offset)
    {
        $this->getGrammar()->setOffset();

        $index = $this->nextArrayIndex($this->getOffset());

        return $this->setBindData("offset.{$index}", $offset);
    }

    /**
     * @param int|string
     */
    public function getOffset()
    {
        return $this->getBindData("offset");
    }

    /**
     * @param int|string $prePage
     * 
     * @return static
     */
    public function setPrePage($prePage = 10)
    {
        $this->paginate["prePage"] = $prePage;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getPrePage()
    {
        return $this->paginate["prePage"];
    }

    /**
     * @param int $prePage
     * 
     * @return static
     */
    public function setCurrentPage(int $currentPage = 1)
    {
        $this->paginate["currentPage"] = $currentPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->paginate["currentPage"];
    }

    /**
     * @param mixed $value
     * @param mixed|null $bindValue
     * 
     * @return array
     */
    public function raw($value, $bindValue = null)
    {
        return new \Wilkques\Database\Queries\Expression($value, $bindValue);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function setSelectRaw(string $column = "*")
    {
        return $this->select($this->raw($column));
    }

    /**
     * @param string $where
     * 
     * @return static
     */
    public function setWhereRaw(string $where)
    {
        return $this->whereQuery($this->raw($where));
    }

    /**
     * @return static
     */
    public function get()
    {
        return $this->compilerSelect()
            ->prepare($this->getQuery())
            ->bindParams($this->getForSelectBindData())
            ->execute()
            ->fetchAllAssociative();
    }

    /**
     * @return static
     */
    public function first()
    {
        return $this->limit(1)
            ->compilerSelect()
            ->prepare($this->getQuery())
            ->bindParams($this->getForSelectBindData())
            ->execute()
            ->fetchFirst();
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindData(array $keys = null)
    {
        return \array_only($this->getBindData(), $keys);
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindDataField(array $keys)
    {
        return \array_field($this->getOnlyBindData($keys), $keys);
    }

    /**
     * @return array
     */
    public function getForSelectBindData()
    {
        return $this->getOnlyBindDataField(["where", "limit", "offset"]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return (int) $this->selectRaw("COUNT(*) as count")
            ->compilerSelect()
            ->prepare($this->getQuery())
            ->bindParams($this->getForSelectBindData())
            ->execute()
            ->fetchOne();
    }

    /**
     * @param array $bindData
     */
    public function withBindData($bindData)
    {
        $this->bindData = $bindData;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function setBindData(string $key, $value = null)
    {
        $bindData = $this->getBindData();

        array_set($bindData, $key, $value);

        $this->bindData = $bindData;

        return $this;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * 
     * @return string|array
     */
    public function getBindData(string $key = null, $default = null)
    {
        $bindData = $this->bindData;

        return array_get($bindData, $key, $default);
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

        $index = $this->nextArrayIndex($this->getBindData("where"));

        if (!$value) {
            $value = $condition;
            $condition = "=";
        }

        return $this->setBindData("where.{$index}", $value)
            ->whereQuery($key, $condition, $andOr);
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

        $query = implode(", ", array_fill(0, count($data), "?"));

        return $this->setBindData("where", $data)->whereRaw("`{$column}` IN ({$query})");
    }

    /**
     * @return static
     */
    public function getForPage()
    {
        $this->setLimit($this->getPrePage())->setOffset(((int) $this->getCurrentPage() - 1) * $this->getPrePage());

        $items = $this->get();

        $total = $this->count();

        return compact('total', 'items');
    }

    /**
     * @return array
     */
    public function getForUpdateBindData()
    {
        return $this->getOnlyBindDataField(["update", "where"]);
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function update($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        return $this->setBindData("update", array_values($data))
            ->setUpdate($data)
            ->compilerUpdate()
            ->prepare($this->getQuery())
            ->bindParams($this->getForUpdateBindData())
            ->execute()
            ->rowCount();
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

        return $this->update($data + [
            $column => $this->raw("`{$column}` = `{$column}` + ?", $value)
        ]);
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

        return $this->update($data + [
            $column => $this->raw("`{$column}` = `{$column}` - ?", $value)
        ]);
    }

    /**
     * @return array
     */
    public function getForWhereBindData()
    {
        return $this->getOnlyBindDataField(["where"]);
    }

    /**
     * @return static
     */
    public function delete()
    {
        return $this->compilerDelete()
            ->prepare($this->getQuery())
            ->bindParams($this->getForWhereBindData())
            ->execute()
            ->rowCount();
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

        return $this->update([
            $column => $value
        ]);
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

        return $this->update([
            $column => $value
        ]);
    }

    // TODO: 修正以下方法

    protected function insertReduce($carry, $item)
    {
        foreach ($item as $key => $value) {
            if (is_numeric($key)) {
                $index = $carry === null ? 1 : ((int) array_key_last($carry) + 1);

                $carry[$index] = $value;
            } else {
                $carry[$key] = $value;
            }
        }

        return $carry;
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function insert($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        var_dump(array_reduce(array_values($data), array($this, "insertReduce")));die;

        return $this->setBindData("insert", array_reduce(array_values($data), array($this, "insertReduce")))
            ->setInsert($data)
            ->compilerInsert()
            ->prepare($this->getQuery());
        // ->bindParams($this->getForUpdateBindData())
        // ->execute()
        // ->rowCount();
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
            "prePage", "process", "selectRaw", "raw", "whereRaw", "whereQuery"
        );

        if (in_array($method, $methods)) {
            $method = "set" . ucfirst($method);
        }

        return $method;
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

        if ($connection && method_exists($connection, $method)) {
            $connection = call_user_func_array(array($connection, $method), $arguments);

            // if (is_object($database)) return $this;

            return $connection;
        }

        $grammar = $this->getGrammar();

        if ($grammar && method_exists($grammar, $method)) {
            $grammar = call_user_func_array(array($grammar, $method), $arguments);

            if (is_object($grammar)) return $this;

            return $grammar;
        }

        $process = $this->getProcess();

        if ($process && method_exists($process, $method)) {
            $process = call_user_func_array(array($process, $method), $arguments);

            if (is_object($process)) return $this;

            return $process;
        }

        return call_user_func_array(array($this, $method), $arguments);
    }
}
