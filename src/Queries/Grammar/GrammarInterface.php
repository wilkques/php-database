<?php

namespace Wilkques\Database\Queries\Grammar;

interface GrammarInterface
{
    /**
     * @return string
     */
    public function getQuery();

    /**
     * @param int|string $offset
     * 
     * @return static
     */
    public function setOffset($offset = "?");

    /**
     * @param int|string $limit
     * 
     * @return static
     */
    public function setLimit($limit = "?");

    /**
     * @param string $column
     * @param string $condition
     * @param string $operate
     * @param string $value
     * 
     * @return static
     */
    public function where($column, $condition = null, $operate = null, $value = "?");
}