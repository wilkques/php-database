<?php

namespace Wilkques\Database;

use Wilkques\Database\Queries\Builder;
// implements \JsonSerializable, \ArrayAccess
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

    // /**
    //  * @return array
    //  */
    // public function toArray()
    // {
    //     return $this->data;
    // }

    // /**
    //  * @return string
    //  */
    // public function toJson()
    // {
    //     return json_encode($this->toArray());
    // }

    // /**
    //  * @return array
    //  */
    // public function jsonSerialize()
    // {
    //     return $this->toArray();
    // }

    // /**
    //  * @param string $offset
    //  * 
    //  * @return bool
    //  */
    // public function offsetExists($offset)
    // {
    //     return isset($this->data[$offset]);
    // }

    // /**
    //  * @param string $offset
    //  * @param mixed $value
    //  */
    // public function offsetSet($offset, $value)
    // {
    //     $this->data[$offset] = $value;
    // }

    // /**
    //  * @param string $offset
    //  * 
    //  * @return mixed
    //  */
    // public function offsetGet($offset)
    // {
    //     return $this->data[$offset];
    // }

    // /**
    //  * @param string $offset
    //  */
    // public function offsetUnset($offset)
    // {
    //     if ($this->offsetExists($offset)) unset($this->data[$offset]);
    // }

    // /**
    //  * Get a data by key
    //  *
    //  * @param string The key data to retrieve
    //  * @access public
    //  */
    // public function __get($key)
    // {
    //     return $this->data[$key];
    // }

    // /**
    //  * Assigns a value to the specified data
    //  *
    //  * @param string The data key to assign the value to
    //  * @param mixed  The value to set
    //  * @access public
    //  */
    // public function __set($key, $value)
    // {
    //     $this->data[$key] = $value;
    // }

    // /**
    //  * Whether or not an data exists by key
    //  *
    //  * @param string An data key to check for
    //  * @access public
    //  * @return boolean
    //  * @abstracting ArrayAccess
    //  */
    // public function __isset($key)
    // {
    //     return isset($this->data[$key]);
    // }

    // /**
    //  * Unsets an data by key
    //  *
    //  * @param string The key to unset
    //  * @access public
    //  */
    // public function __unset($key)
    // {
    //     unset($this->data[$key]);
    // }

    // public function __destruct()
    // {
    //     // $this->getConnection()->setConnection();
    // }

    // /**
    //  * Convert the model to its string representation.
    //  *
    //  * @return string
    //  */
    // public function __toString()
    // {
    //     return $this->toJson();
    // }

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

        // if (is_object($builder)) return $this;

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
