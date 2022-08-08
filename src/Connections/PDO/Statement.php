<?php

namespace Wilkques\Database\Connections\PDO;

class Statement
{
    /** @var \PDOStatement */
    protected $statement;
    /** @var array */
    protected $params = [];
    /** @var bool|false */
    protected $debug = false;

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
     * @param bool|true $debug
     * 
     * @return static
     */
    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return bool|false
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param string|int $param
     * @param mixed $value
     * 
     * @return static
     */
    public function setParam($param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * @param string|int $param
     * 
     * @return mixed
     */
    public function getParam($param)
    {
        return $this->params[$param];
    }

    /**
     * @param array $params
     * 
     * @return static
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $value
     * 
     * @return int
     */
    protected function bindVarsType($value)
    {
        switch (true) {
            case is_bool($value):
                $varType = \PDO::PARAM_BOOL;
                break;
            case is_int($value):
                $varType = \PDO::PARAM_INT;
                break;
            case is_null($value):
                $varType = \PDO::PARAM_NULL;
                break;
            default:
                $varType = \PDO::PARAM_STR;

                $length = mb_detect_encoding($value) == "UTF-8" ? mb_strlen($value, "UTF-8") : strlen($value);

                $length >= 1000000 && $varType = \PDO::PARAM_LOB;
                break;
        }

        return $varType;
    }

    /**
     * @param string|int $param
     * @param mixed $value
     * @param int|null $varsType
     * 
     * @return static
     */
    public function bindParam($param, $value, $varType = null)
    {
        $this->setParam($param, $value)
            ->getStatement()
            ->bindParam(
                $param,
                $value,
                $varType ?: $this->bindVarsType($value)
            );

        return $this;
    }

    /**
     * @param string|int $param
     * @param mixed $value
     * @param int|null $varsType
     * 
     * @return static
     */
    public function bindValue($param, $value, $varType = null)
    {
        $this->setParam($param, $value)
            ->getStatement()
            ->bindValue(
                $param,
                $value,
                $varType ?: $this->bindVarsType($value)
            );

        return $this;
    }

    /**
     * @param array|null $params
     * 
     * @return static
     */
    public function bindParams(array $params = null)
    {
        return $this->binds($params, "bindParam");
    }

    /**
     * @param array|null $params
     * 
     * @return static
     */
    public function bindValues(array $params = null)
    {
        return $this->binds($params, "bindValue");
    }

    /**
     * @param array|null $params
     * @param string $bindMethod
     * 
     * @return static
     */
    protected function binds(array $params = null, string $bindMethod = null)
    {  
        $params = $params ?: ($this->getParams() ?: null);

        if (!$params) return $this;

        $params = array_reduce($params, [$this, "reduce"]);

        if (!in_array($bindMethod, ["bindParam", "bindValue"]))
            throw new \UnexpectedValueException("bindMethod must be bindParam or bindValue");

        array_map(function ($item, $index) use ($bindMethod) {
            $this->{$bindMethod}($index, $item);
        }, $params, array_keys($params));

        return $this;
    }

    /**
     * @param array|null $carry
     * @param array $item
     * 
     * @return array
     */
    protected function reduce($carry, $item)
    {
        if (is_array($item)){
            foreach ($item as $key => $value) {
                if (is_object($value) && $value instanceof \Wilkques\Database\Queries\Expression && $value->getBindValue() != null) {
                    $value = $value->getBindValue();
                }

                if (is_numeric($key)) {
                    $index = $carry === null ? 1 : ((int) array_key_last($carry) + 1);

                    $carry[$index] = $value;
                } else {
                    $carry[$key] = $value;
                }
            }
        }

        return $carry;
    }

    /**
     * @param array $params
     * 
     * @return Result
     */
    public function execute($params = null)
    {
        $statement = $this->getStatement();

        $statement->execute($params);

        $this->getDebug() && $statement->debugDumpParams();

        return new Result($statement);
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, ["debug"])) {
            $method = "set" . ucfirst($method);

            return $this->{$method}(...$arguments);
        }
    }
}
