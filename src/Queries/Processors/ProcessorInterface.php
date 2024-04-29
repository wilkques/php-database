<?php

namespace Wilkques\Database\Queries\Processors;

use Wilkques\Database\Queries\Builder;

interface ProcessorInterface
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Wilkques\Database\Queries\Builder  $query
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $values, $sequence = null);
}