<?php

namespace Wilkques\Database\Queries;

class Expression
{
    /**
     * The value of the expression.
     *
     * @var mixed
     */
    protected $value;

    protected $bindValue;

    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value, $bindValue = null)
    {
        $this->value = $value;

        $this->bindValue = $bindValue;
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getBindValue()
    {
        return $this->bindValue;
    }

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
