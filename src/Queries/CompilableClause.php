<?php

namespace Wilkques\Database\Queries;

interface CompilableClause
{
    /**
     * Compile the expression and return [sql_without_alias, bindings].
     *
     * @return array  [$sql, $bindings]
     */
    public function compileSql();
}
