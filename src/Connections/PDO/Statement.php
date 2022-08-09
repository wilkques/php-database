<?php

namespace Wilkques\Database\Connections\PDO;

class Statement
{
    /** @var \PDOStatement */
    protected $statement;
    /** @var array */
    protected $params = array();
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
     * @param array|[] $params
     * 
     * @return static
     */
    public function bindParams(array $params = array())
    {
        return $this->binding("bindParam", $params, function ($params) {
            $newParams = array();

            foreach ($params as $item) {
                array_push($newParams, ...array_values($item));
            }

            return $newParams;
        });
    }

    /**
     * @param array|[] $params
     * 
     * @return static
     */
    public function bindValues(array $params = array())
    {
        return $this->binding("bindValue", $params, function ($params) {
            $newParams = array();

            foreach ($params as $item) {
                $newParams = array_merge($newParams, $item);
            }

            return $newParams;
        });
    }

    /**
     * @param string $bindMethod
     * @param array $params
     * @param \Closure|null $callback
     * 
     * @return static
     */
    public function binding(string $bindMethod, array $params = array(), \Closure $callback = null)
    {
        $params = $params ?: $this->getParams();

        $datas = $callback ? $callback($params) : $params;

        array_map(function ($item, $index) use ($bindMethod) {
            is_numeric($index) && ++$index;

            call_user_func_array(array($this, $bindMethod), array($index, $item));
        }, $datas, array_keys($datas));

        return $this;
    }

    /**
     * @param array|null $params
     * 
     * @return Result
     */
    public function execute($params = null)
    {
        $statement = $this->getStatement();

        $this->getDebug() && $statement->debugDumpParams();

        $statement->execute($params);

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
        if (in_array($method, array("debug", "params"))) {
            $method = "set" . ucfirst($method);

            return call_user_func_array(array($this, $method), $arguments);
        }
    }
}
