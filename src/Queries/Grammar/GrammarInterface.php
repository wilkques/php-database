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
}