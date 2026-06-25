<?php

namespace Wilkques\Database\Queries;

class IfClause extends Builder implements CompilableClause
{
    /**
     * @var string
     */
    protected $parentClass;

    /**
     * @var Builder
     */
    protected $parentBuilder;

    /**
     * @param Builder $parentBuilder
     * @param mixed   $condition
     */
    public function __construct(Builder $parentBuilder, $condition)
    {
        parent::__construct(
            $parentBuilder->getConnection(),
            $parentBuilder->getGrammar(),
            $parentBuilder->getProcessor()
        );

        $this->parentBuilder = $parentBuilder;
        $this->parentClass   = get_class($parentBuilder);

        $this->setQuery('condition', $condition);
    }

    /**
     * @param  mixed $value
     * @return static
     */
    public function then($value)
    {
        return $this->setQuery('true_value', $value);
    }

    /**
     * @param  mixed $value
     * @return static
     */
    public function otherwise($value)
    {
        return $this->setQuery('false_value', $value);
    }

    /**
     * Compile without alias — for update() and direct array usage.
     *
     * @return array  [$sql, $bindings]
     */
    public function compileSql()
    {
        return $this->getGrammar()->compileIf($this);
    }

    /**
     * @param  string|null $alias
     * @return CompiledClause
     */
    public function end($alias = null)
    {
        list($rawSql, $bindings) = $this->getGrammar()->compileIf($this);

        $compiled        = new CompiledClause($rawSql, $bindings, $this->parentBuilder);
        $compiled->alias = (!is_null($alias) && $alias !== '') ? $alias : null;

        $sqlForSelect = $rawSql;
        if ($compiled->alias !== null) {
            $sqlForSelect .= ' AS ' . $this->getGrammar()->contactBacktick($alias);
        }
        $this->parentBuilder->selectRaw($sqlForSelect, $bindings);

        return $compiled;
    }

    /**
     * @return Builder
     */
    public function newQuery()
    {
        return $this->newParentQuery()->newQuery();
    }

    /**
     * @return Builder
     */
    protected function newParentQuery()
    {
        $parentClass = $this->parentClass;

        return new $parentClass($this->getConnection(), $this->getGrammar(), $this->getProcessor());
    }

    /**
     * @return Builder
     */
    public function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }
}
