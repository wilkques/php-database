<?php

namespace Wilkques\Database\Queries\Process;

use Wilkques\Database\Queries\Builder;

class Process implements ProcessInterface
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }
}