<?php

namespace Wilkques\Database\Queries\Process;

use Wilkques\Database\Queries\Builder;

interface ProcessInterface
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Wilkques\Database\Queries\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null);
}