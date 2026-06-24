<?php

namespace Wilkques\Database\Queries;

class CaseClause extends Builder
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
     * @param  string|null $alias
     * @return Builder
     */
    public function end($alias = null)
    {
        list($sql, $bindings) = $this->getGrammar()->compileCase($this);

        if (!is_null($alias) && $alias !== '') {
            $sql .= ' AS ' . $this->getGrammar()->contactBacktick($alias);
        }

        return $this->parentBuilder->selectRaw($sql, $bindings);
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
