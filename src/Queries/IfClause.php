<?php

namespace Wilkques\Database\Queries;

class IfClause extends Builder
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
     * @param  string|null $alias
     * @return Builder
     */
    public function end($alias = null)
    {
        list($sql, $bindings) = $this->getGrammar()->compileIf($this);

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
