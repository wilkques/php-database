<?php

namespace Wilkques\Database\Queries\Process;

use Wilkques\Database\Queries\Builder;

class Process implements ProcessInterface
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  Builder  $query
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $values, $sequence = null)
    {
        $query->insert($values);

        $id = $query->getLastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }
}