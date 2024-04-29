<?php

namespace Wilkques\Database\Queries;

class JoinClause extends Builder
{
    protected $type;

    /**
     * Create a new join clause instance.
     *
     * @param  \Wilkques\Database\Queries\Builder  $parentQuery
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

        $this->table($table)->type($type);
    }

    /**
     * @param string $type
     * 
     * @return static
     */
    public function type($type)
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
     * @param  \Illuminate\Database\Query\Expression|string|null  $second
     * @param  string  $boolean
     * 
     * @return static
     */
    public function on($first, $operator = null, $second = null, $andOr = 'and')
    {
        if (is_null($second)) {
            $second = $operator;

            $operator = '=';
        }

        if (is_callable($first)) {
            $first($this);
        }

        $andOr = strtoupper($andOr);

        $this->queries['joins']['queries'][] = "{$andOr} {$first} {$operator} {$second}";

        return $this;
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Database\Query\Expression|string|null  $second
     * 
     * @return static
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }
}
