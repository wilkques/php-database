<?php

namespace Wilkques\Database\Queries;

class JoinClause extends Builder
{
    /** @var string */
    protected $type;

    /**
     * The class name of the parent query builder.
     *
     * @var string
     */
    protected $parentClass;

    /**
     * Create a new join clause instance.
     *
     * @param  Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        parent::__construct(
            $parentQuery->getConnection(),
            $parentQuery->getGrammar(),
            $parentQuery->getProcessor()
        );

        $this->setTable($table)->setType($type)->setParentClass(get_class($parentQuery));
    }

    /**
     * @param string $type
     * 
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $parentClass
     * 
     * @return static
     */
    public function setParentClass($parentClass)
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  Expression|string|null  $second
     * @param  string  $boolean
     * 
     * @return static
     */
    public function on($first, $operator = null, $second = null, $andOr = 'and')
    {
        if (is_callable($first) || $first instanceof \Closure) {
            return $this->nested($first, $andOr, 'joins');
        }

        if (is_null($second)) {
            $second = $operator;

            $operator = '=';
        }

        $andOr = strtoupper($andOr);

        $this->queryPush("{$andOr} {$this->contactBacktick($first)} {$operator} {$this->contactBacktick($second)}", 'joins');

        return $this;
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  Expression|string|null  $second
     * 
     * @return static
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return JoinClause
     */
    public function newQuery()
    {
        return new static($this->newParentQuery(), $this->getType(), $this->getTable());
    }

    /**
     * Create a new parent query instance.
     *
     * @return Builder
     */
    protected function newParentQuery()
    {
        $parentClass = $this->getParentClass();

        return new $parentClass($this->getConnection(), $this->getGrammar(), $this->getProcessor());
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return Builder
     */
    protected function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }
}
