<?php

namespace Wilkques\Database\Grammar;

use Wilkques\Database\GrammarInterface;

abstract class Grammar implements GrammarInterface
{
    /** @var string */
    protected $conditionQuery = "";
    /** @var array */
    protected $conditionData = [];
    /** @var string */
    protected $lock = "";

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
     * 
     * @return static
     */
    public function setConditionQuery($key, $condition, $andOr = "AND")
    {
        if ($this->conditionQuery != "") $this->conditionQuery .= " {$andOr} ";

        $this->conditionQuery .= "`{$key}` {$condition} ?";

        return $this;
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
                // $this->where(...$item);

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
                // $this->orWhere(...$item);

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
    abstract function lockForUpdate();

    /**
     * @return static
     */
    abstract function sharedLock();
}
