<?php

namespace Wilkques\Database;

use Wilkques\Database\Queries\Builder;

/**
 * php >= 5.4
 * 
 * 簡易資料庫操作
 * 
 * @see [wilkques](https://github.com/wilkques/Database)
 * 
 * @author wilkques
 */
class Database 
{
    /** @var Builder */
    protected $query;

    /**
     * @param Builder $query
     */
    public function __construct(Builder $query = null)
    {
        $this->setBuilder($query);
    }

    /**
     * @param Builder $query
     * 
     * @return static
     */
    public function setBuilder(Builder $query = null)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->query;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        $methods = array(
            "builder"
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

        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        }

        $query = $this->getBuilder();

        $builder = call_user_func_array(array($query, $method), $arguments);

        return $builder;
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
