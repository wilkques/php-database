<?php

namespace Wilkques\Database\Queries\Processors;

use Wilkques\Database\Queries\Builder;

class Processor implements ProcessorInterface
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

        $id = $query->getConnection()->getLastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }
}