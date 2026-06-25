<?php

namespace Wilkques\Database\Queries;

class CaseClause extends Builder implements CompilableClause
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
     * @param Builder     $parentBuilder
     * @param string|null $column  Simple CASE 的欄位；null = Searched CASE
     */
    public function __construct(Builder $parentBuilder, $column = null)
    {
        parent::__construct(
            $parentBuilder->getConnection(),
            $parentBuilder->getGrammar(),
            $parentBuilder->getProcessor()
        );

        $this->parentBuilder = $parentBuilder;
        $this->parentClass   = get_class($parentBuilder);

        if (!is_null($column)) {
            $this->setQuery('column', $column);
        }
    }

    /**
     * @param  mixed $condition
     * @param  mixed $value
     * @return static
     */
    public function when($condition, $value)
    {
        $conditions   = $this->getQuery('conditions', array());
        $conditions[] = array('when' => $condition, 'then' => $value);

        return $this->setQuery('conditions', $conditions);
    }

    /**
     * @param  mixed $value
     * @return static
     */
    public function otherwise($value)
    {
        $this->setQuery('has_else', true);

        return $this->setQuery('else_value', $value);
    }

    /**
     * Compile without alias — for update() and direct array usage.
     *
     * @return array  [$sql, $bindings]
     */
    public function compileSql()
    {
        return $this->getGrammar()->compileCase($this);
    }

    /**
     * @param  string|null $alias
     * @return CompiledClause
     */
    public function end($alias = null)
    {
        list($rawSql, $bindings) = $this->getGrammar()->compileCase($this);

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
