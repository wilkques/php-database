<?php

namespace Wilkques\Database;

use Wilkques\Database\Queries\Builder;

class Database 
{
    /** @var Builder */
    protected $builder;

    /**
     * @param GrammarInterface $grammar
     */
    public function __construct(Builder $builder = null)
    {
        $this->setBuilder($builder);
    }

    /**
     * @param Builder $builder
     * 
     * @return static
     */
    public function setBuilder(Builder $builder = null)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
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

        $builder = $this->getBuilder();

        $builder = call_user_func_array(array($builder, $method), $arguments);

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
