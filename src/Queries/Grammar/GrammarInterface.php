<?php

namespace Wilkques\Database\Queries\Grammar;

interface GrammarInterface
{
    /**
     * @param \Wilkques\Database\Queries\Builder $builder
     * 
     * @return string
     */
    public function compilerSelect($builder);
    
    /**
     * @param \Wilkques\Database\Queries\Builder $query
     * @param array|[] $columns
     * @param string|null $sql
     * 
     * @return string
     */
    public function compilerInsert($query, $data = array(), $sql = null);

    /**
     * @param \Wilkques\Database\Queries\Builder $query
     * @param array $columns
     * 
     * @return string
     */
    public function compilerUpdate($query, $columns);

    /**
     * @param \Wilkques\Database\Queries\Builder $query
     * 
     * @return string
     */
    public function compilerDelete($query);

    /**
     * @param \Wilkques\Database\Queries\Builder $query
     * 
     * @return string
     */
    public function compilerCount($query);

    /**
     * @return string
     */
    public function lockForUpdate();

    /**
     * @return string
     */
    public function sharedLock();
}