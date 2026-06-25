<?php

namespace Wilkques\Database\Queries;

class CompiledClause implements CompilableClause
{
    /** @var string  CASE/IF SQL without AS alias */
    public $rawSql;

    /** @var string|null  alias for select() Closure branch to reconstruct */
    public $alias;

    /** @var array */
    public $bindings;

    /** @var Builder */
    private $parentBuilder;

    public function __construct($rawSql, array $bindings, Builder $parentBuilder)
    {
        $this->rawSql        = $rawSql;
        $this->bindings      = $bindings;
        $this->parentBuilder = $parentBuilder;
    }

    /**
     * Always returns rawSql (no alias) — correct for update().
     *
     * @return array  [$sql, $bindings]
     */
    public function compileSql()
    {
        return array($this->rawSql, $this->bindings);
    }

    /**
     * Proxy all method calls to parentBuilder to preserve fluent chain API.
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->parentBuilder, $method), $args);
    }
}
