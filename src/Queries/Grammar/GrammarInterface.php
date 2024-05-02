<?php

namespace Wilkques\Database\Queries\Grammar;

use Wilkques\Database\Queries\Builder;

interface GrammarInterface
{
    /**
     * @param Builder $builder
     * 
     * @return string
     */
    public function compilerSelect($builder);
    
    /**
     * @param Builder $query
     * @param array|[] $columns
     * @param string|null $sql
     * 
     * @return string
     */
    public function compilerInsert($query, $data = [], $sql = null);

    /**
     * @param Builder $query
     * @param array $columns
     * 
     * @return string
     */
    public function compilerUpdate($query, $columns);

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerDelete($query);

    /**
     * @return string
     */
    public function lockForUpdate();

    /**
     * @return string
     */
    public function sharedLock();
}