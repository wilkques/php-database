<?php

namespace Wilkques\Database\Queries;

use Closure;
use InvalidArgumentException;
use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Queries\Grammar\GrammarInterface;
use Wilkques\Database\Queries\Processors\ProcessorInterface;
use Wilkques\Helpers\Arrays;

class Builder
{
    /** @var array */
    protected $resolvers = array();

    /** @var array */
    protected $queries = array();

    /** @var array */
    protected $methods = array();

    /**
     * All of the available clause operators.
     *
     * @var string[]
     */
    public $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
        'is not', 'is', 'not in', 'in', 'exists',
        'not exists', 'between', 'not between',
    );

    /** @var array */
    protected $bindingComponents = array(
        'columns', 'from', 'joins', 'insert', 'update',
        'wheres', 'groups', 'havings', 'orders', 'limit',
        'offset', 'unions',
    );

    /**
     * @param ConnectionInterface $connection
     * @param GrammarInterface $grammar
     * @param ProcessorInterface $processor
     */
    public function __construct(
        ConnectionInterface $connection,
        GrammarInterface $grammar = null,
        ProcessorInterface $processor = null
    ) {
        $this->resolverRegister(static::class, $this)
            ->setConnection($connection)
            ->setGrammar($grammar)
            ->setProcessor($processor);
    }

    /**
     * @param ConnectionInterface $connection
     * @param GrammarInterface $grammar
     * @param ProcessorInterface $processor
     * 
     * @return static
     */
    public static function make(
        ConnectionInterface $connection,
        GrammarInterface $grammar = null,
        ProcessorInterface $processor = null
    ) {
        return new static($connection, $grammar, $processor);
    }

    /**
     * @param string|object $abstract
     * @param object|null $class
     * 
     * @return static
     */
    public function resolverRegister($abstract, $class = null)
    {
        if (is_object($abstract)) {
            $class = $abstract;

            $abstract = get_class($abstract);
        }

        data_set($this->resolvers, $abstract, $class);

        return $this;
    }

    /**
     * @return array
     */
    public function getResolvers()
    {
        return $this->resolvers;
    }

    /**
     * @param string|object $abstract
     * @param mixed|null $default
     * 
     * @return mixed
     */
    public function getResolver($abstract, $default = null)
    {
        if (is_object($abstract)) {
            $abstract = array_search($abstract, $this->getResolvers());
        }

        if (interface_exists($abstract)) {
            $abstract = array_filter($this->getResolvers(), function ($resolver) use ($abstract) {
                return in_array($abstract, class_implements($resolver));
            });

            return current($abstract);
        }

        return data_get($this->getResolvers(), $abstract, $default);
    }

    /**
     * @param ConnectionInterface $connection
     * 
     * @return static
     */
    public function setConnection(ConnectionInterface $connection)
    {
        return $this->resolverRegister($connection);
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->getResolver(ConnectionInterface::class);
    }

    /**
     * @param GrammarInterface $grammar
     * 
     * @return static
     */
    public function setGrammar(GrammarInterface $grammar = null)
    {
        return $this->resolverRegister($grammar);
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar()
    {
        return $this->getResolver(GrammarInterface::class);
    }

    /**
     * @param ProcessorInterface $processor
     * 
     * @return static
     */
    public function setProcessor(ProcessorInterface $processor = null)
    {
        return $this->resolverRegister($processor);

        return $this;
    }

    /**
     * @return ProcessorInterface
     */
    public function getProcessor()
    {
        return $this->getResolver(ProcessorInterface::class);
    }

    /**
     * @param array $queries
     * 
     * @return static
     */
    public function withQueries(array $queries)
    {
        $this->queries = $queries;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function setQuery(string $key, $value = null)
    {
        array_set($this->queries, $key, $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed|null
     */
    public function getQuery($key, $default = null)
    {
        return array_get($this->getQueries(), $key, $default);
    }

    /**
     * @return static
     */
    public function newQuery()
    {
        return new static($this->getConnection(), $this->getGrammar(), $this->getProcessor());
    }

    /**
     * @param callback|static|Closure $callback
     * 
     * @return array
     * 
     * @throws InvalidArgumentException
     */
    protected function createSub($callback)
    {
        if ($callback instanceof Closure) {
            call_user_func($callback, $callback = $this->forSubQuery());
        }

        return $this->parseSub($callback);
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  mixed  $query
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSub($query)
    {
        if ($query instanceof self) {
            $query = $this->prependDatabaseNameIfCrossDatabaseQuery($query);

            return array($query->toSql(), $query->getBindings());
        }

        if (is_string($query) || is_numeric($query) || $query instanceof Expression) {
            return array($query, array());
        }

        throw new InvalidArgumentException(
            'A subquery must be a query builder instance, a Closure, or a string.'
        );
    }

    /**
     * Prepend the database name if the given query is on another database.
     *
     * @param  mixed  $query
     * @return mixed
     */
    protected function prependDatabaseNameIfCrossDatabaseQuery($query)
    {
        if ($query->getConnection()->getDatabase() !== $this->getConnection()->getDatabase()) {
            Arrays::map($query->getFrom(), function ($from, $index) use ($query) {
                $database = $query->getConnection()->getDatabase();

                if (strpos($from, $database) !== 0 && strpos($from, '.') === false) {
                    $query->setFrom($index, $database . '.' . $from);
                }
            });
        }

        return $query;
    }

    /**
     * @return string
     */
    protected function toSql()
    {
        return $this->getGrammar()->compilerSelect($this);
    }

    /**
     * @param array|[] $except
     * 
     * @return array
     */
    protected function getBindings($except = array())
    {
        $components = $this->bindingComponents;

        $components = array_filter($components, function ($component) use ($except) {
            return !in_array($component, $except);
        });

        $bindings = array();

        foreach ($components as $component) {
            $binding = $this->getQuery("{$component}.bindings");

            if (is_array($binding)) {
                $bindings = array_merge($bindings, $binding);
            } else if (!is_null($binding)) {
                $bindings[] = $binding;
            }
        }

        return $bindings;
    }

    /**
     * @param  mixed  $query
     * @param  mixed  $binding
     * @param  string  $type
     * 
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    protected function queriesPush($query, $binding, $type = 'wheres')
    {
        return $this->queryPush($query, $type)->bindingPush($binding, $type);
    }

    /**
     * @param  mixed  $value
     * @param  string  $type
     * 
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    protected function queryPush($values, $type = 'wheres')
    {
        $this->queries[$type]['queries'][] = $values;

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    protected function bindingPush($values, $type = 'wheres')
    {
        if (is_array($values)) {
            if (!isset($this->queries[$type]['bindings'])) {
                $this->queries[$type]['bindings'] = array();
            }

            $this->queries[$type]['bindings'] = array_merge($this->queries[$type]['bindings'], $values);

            return $this;
        }

        $this->queries[$type]['bindings'][] = $values;

        return $this;
    }

    /**
     * @param string|int $value
     * 
     * @return Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * @param int|string $index
     * @param string $from
     * 
     * @return static
     */
    public function setFrom($index, $from)
    {
        $this->queries['from']['queries'][$index] = $from;

        return $this;
    }

    /**
     * @param string|callback $froms
     * @param string|null $as
     * 
     * @return static
     */
    public function from($froms, $as = null)
    {
        if (!is_array($froms)) {
            if ($as) {
                $froms = array(
                    $as => $froms
                );
            } else {
                $froms = array(
                    $froms
                );
            }
        }

        foreach ($froms as $as => $from) {
            if ($from instanceof Closure || $from instanceof self) {
                $this->fromSub($from, (is_string($as) ? $as : null));
            } else {
                $from = is_string($as) ? "{$from} AS `{$as}`" : $from;

                $this->queryPush($from, 'from');
            }
        }

        return $this;
    }

    /**
     * @param string|callback $from
     * @param string|null $as
     * 
     * @return static
     */
    public function fromSub($from, $as = null)
    {
        if (is_array($from)) {
            return $this->from($from);
        }

        list($query, $bindings) = $this->createSub($from);

        $query = "({$query})";

        $query = $as ? "{$query} AS `{$as}`" : $query;

        if (!empty($bindings)) {
            $this->bindingPush($bindings, 'from');
        }

        $this->queryPush($query, 'from');

        return $this;
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        return $this->getQuery("from.queries");
    }

    /**
     * @param string|callback $table
     * @param string|null $as
     * 
     * @return static
     */
    public function setTable($table, $as = null)
    {
        return $this->from($table, $as);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->getFrom();
    }

    /**
     * @param array|string <$column|...$column>
     * 
     * @return static
     */
    public function select($columns = array('*'))
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        foreach ($columns as $as => $column) {
            if ($column instanceof Closure || $column instanceof self) {
                $this->selectSub($column, (is_string($as) ? $as : null));
            } else {
                $this->queryPush($column, 'columns');
            }
        }

        return $this;
    }

    /**
     * @param string $column
     * @param string|null $as
     * 
     * @return static
     */
    public function selectSub($column, $as = null)
    {
        if (is_array($column)) {
            return $this->select($column);
        }

        list($queries, $bindings) = $this->createSub($column);

        $queries = "({$queries})";

        $queries = $as ? "{$queries} AS `{$as}`" : $queries;

        $this->queryPush($queries, 'columns');

        if (!empty($bindings)) {
            $this->bindingPush($bindings, 'columns');
        }

        return $this;
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return array($operator, '=');
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return array($value, $operator);
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            !in_array($operator, array('=', '<>', '!='));
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return !is_string($operator) || (!in_array(strtolower($operator), $this->operators, true));
    }

    /**
     * Add an array of where clauses to the query.
     *
     * @param  array  $column
     * @param  string  $join
     * @param  string  $method
     * @param  string  $type
     * 
     * @return static
     */
    protected function arrayNested($column, $join, $method = 'where')
    {
        return call_user_func(function ($column, $method, $join) {
            $nestedMethod = "{$method}Nested";

            return call_user_func(array($this, $nestedMethod), function ($query) use ($column, $method, $join) {
                foreach ($column as $key => $value) {
                    $nestedMethod = 'array' . ucfirst($method) . 'Nested';

                    call_user_func(array($query, $nestedMethod), $query, $key, $value, $join);
                }
            }, $join);
        }, $column, $method, $join);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure|callback  $callback
     * @param  string  $join
     * @param  string  $type
     * 
     * @return static
     */
    public function nested($callback, $join = 'and', $type = 'wheres')
    {
        call_user_func($callback, $query = $this->forNested());

        return $this->addNestedQuery($query, $join, $type);
    }

    /**
     * Create a new query instance for nested condition.
     *
     * @return Builder
     */
    public function forNested()
    {
        return $this->newQuery()->from($this->getQuery('from.queries'));
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  Builder  $query
     * @param  string  $join
     * @param  string  $type
     * 
     * @return static
     */
    public function addNestedQuery($query, $join = 'and', $type = 'wheres')
    {
        $queries = $query->getQuery("{$type}.queries");

        if (in_array($type, array('groups', 'orders'))) {
            $sql = join(', ', $queries);

            $this->queryPush($sql, $type);
        } else {
            $sql = join(' ', $queries);

            $sql = ltrim(ltrim($sql, 'AND '), 'OR ');

            $join = strtoupper($join);

            $this->queryPush("{$join} ({$sql})", $type);
        }

        $bindings = $query->getQuery("{$type}.bindings");

        if (!empty($bindings)) {
            $this->bindingPush($bindings, $type);
        }

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    protected function whereNested($callback, $join = 'and')
    {
        return $this->nested($callback, $join, 'wheres');
    }

    /**
     * @param static $query
     * @param string|int $key
     * @param mixed $value
     * @param string $join
     * 
     * @return void
     */
    protected function arrayWhereNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(array($query, 'where'), array_values($value));
        } else {
            call_user_func_array(array($query, 'where'), array($key, '=', $value, $join));
        }
    }

    /**
     * @param string|array|callback $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $andOr
     * 
     * @return static
     */
    public function where($column, $operator = null, $value = null, $andOr = 'and')
    {
        // 參數若為: array(array($column, $operator, $value, $andOr), array($column, $operator, $value, $andOr))
        if (is_array($column)) {
            return $this->arrayNested($column, $andOr);
        }

        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column, $andOr);
        }

        if ($column instanceof self && !is_null($operator)) {
            list($sub, $bindings) = $this->createSub($column);

            if (!empty($bindings)) {
                $this->bindingPush($bindings);
            }

            return $this->where($this->raw('(' . $sub . ')'), $operator, $value, $andOr);
        }

        if ($this->invalidOperator($operator)) {
            list($value, $operator) = array($operator, '=');
        }

        if (is_null($value) && $column instanceof self) {
            if ('and' == $andOr) {
                return $this->whereExists($column);
            }

            return $this->orWhereExists($column);
        }

        if ($value instanceof self || $value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $andOr);
        }

        if ($value instanceof Expression) {
            $andOr = strtoupper($andOr);

            $operator = strtoupper($operator);

            return $this->queryPush("{$andOr} {$column} {$operator} {$value}");
        }

        if (is_null($value)) {
            if ('and' == $andOr) {
                return $this->whereNull($column);
            }

            return $this->orWhereNull($column);
        }

        $varValue = '?';

        $andOr = strtoupper($andOr);

        if (is_array($value)) {
            if (in_array($operator, ['between', 'not between'])) {
                $varValue = join(" {$andOr} ", array_fill(0, count($value), "?"));
            } else {
                $varValue = "(" . join(',', array_fill(0, count($value), "?")) . ")";
            }
        }

        $operator = strtoupper($operator);

        return $this->queriesPush("{$andOr} {$column} {$operator} {$varValue}", $value);
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  string|array  $column
     * @param  string  $operator
     * @param  \Closure  $callback
     * @param  string  $andOr
     * 
     * @return static
     */
    public function whereSub($column, $operator, $callback, $andOr = 'and')
    {
        $operator = strtolower($operator);

        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($callback, $operator) = $this->prepareValueAndOperator(
            $callback,
            $operator,
            func_num_args() === 2
        );

        $andOr = strtoupper($andOr);

        $operator = strtoupper($operator);

        list($sql, $bindings) = $this->createSub($callback);

        $this->queryPush("{$andOr} {$column} {$operator} ({$sql})");

        if (!empty($bindings)) {
            $this->bindingPush($bindings);
        }

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \Closure  $callback
     * 
     * @return static
     */
    public function orWhereSub($column, $operator, $callback)
    {
        return $this->whereSub($column, $operator, $callback, 'or');
    }

    /**
     * @param string|array $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * @param string $column
     * @param string $andOr
     * @param bool|false $not
     * 
     * @return static
     */
    public function whereNull($column, $andOr = 'and', $not = false)
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, $andOr);
        }

        $andOr = strtoupper($andOr);

        $type = $not ? 'NOT NULL' : 'NULL';

        $this->queryPush("{$andOr} {$column} IS {$type}");

        return $this;
    }

    /**
     * @param string $column
     * @param bool|false $not
     * 
     * @return static
     */
    public function orWhereNull($column, $not = false)
    {
        return $this->whereNull($column, 'or', $not);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function whereNotNull($column)
    {
        return $this->whereNull($column, 'and', true);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function orWhereNotNull($column)
    {
        return $this->orWhereNull($column, true);
    }

    /**
     * @param string $column
     * @param array|callback $in
     * 
     * @return static
     */
    public function whereIn($column, $in)
    {
        if ($in instanceof Closure || $in instanceof self) {
            return $this->whereSub($column, 'in', $in);
        }

        return $this->where($column, 'in', $in);
    }

    /**
     * @param string $column
     * @param array $in
     * 
     * @return static
     */
    public function orWhereIn($column, $in)
    {
        if ($in instanceof Closure || $in instanceof self) {
            return $this->orWhereSub($column, 'in', $in);
        }

        return $this->orWhere($column, 'in', $in);
    }

    /**
     * @param string $column
     * @param array $in
     * 
     * @return static
     */
    public function whereNotIn($column, $in)
    {
        return $this->where($column, 'not in', $in);
    }

    /**
     * @param string $column
     * @param array $in
     * 
     * @return static
     */
    public function orWhereNotIn($column, $in)
    {
        return $this->orWhere($column, 'not in', $in);
    }

    /**
     * @param string|array $column
     * @param string $value
     * 
     * @return static
     */
    public function whereLike($column, $value)
    {
        return $this->where($column, 'like', $value);
    }

    /**
     * @param string|array $column
     * @param string $value
     * 
     * @return static
     */
    public function orWhereLike($column, $value)
    {
        return $this->orWhere($column, 'like', $value);
    }

    /**
     * @param callback|static $callback
     * @param string $andOr
     * @param bool|false $not
     * 
     * @return static
     */
    public function whereExists($callback, $andOr = 'and', $not = false)
    {
        list($sql, $bindings) = $this->createSub($callback);

        $andOr = strtoupper($andOr);

        $type = $not ? 'NOT ' : '';

        $this->queryPush(sprintf("%s %sEXISTS (%s)", $andOr, $type, $sql));

        if (!empty($bindings)) {
            $this->bindingPush($bindings);
        }

        return $this;
    }

    /**
     * @param callback|static $callback
     * 
     * @return static
     */
    public function whereNotExists($callback)
    {
        return $this->whereExists($callback, 'and', true);
    }

    /**
     * @param callback|static $callback
     * @param bool|false $not
     * 
     * @return static
     */
    public function orWhereExists($callback, $not = false)
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * @param callback|static $callback
     * 
     * @return static
     */
    public function orWhereNotExists($callback)
    {
        return $this->orWhereExists($callback, true);
    }

    /**
     * @param string $column
     * @param array $values
     * @param bool|false $not
     * 
     * @return static
     */
    public function whereBetween($column, $values, $not = false)
    {
        return $this->where($column, ($not ? 'not between' : 'between'), $values);
    }

    /**
     * @param string $column
     * @param array $values
     * @param bool|false $not
     * 
     * @return static
     */
    public function orWhereBetween($column, $values, $not = false)
    {
        return $this->orWhere($column, ($not ? 'not between' : 'between'), $values);
    }

    /**
     * @param string $column
     * @param array $values
     * 
     * @return static
     */
    public function whereNotBetween($column, $values)
    {
        return $this->whereBetween($column, $values, true);
    }

    /**
     * @param string $column
     * @param array $values
     * 
     * @return static
     */
    public function orWhereNotBetween($column, $values)
    {
        return $this->orWhereBetween($column, $values, true);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    protected function groupByNested($callback, $join)
    {
        return $this->nested($callback, $join, 'groups');
    }

    /**
     * @param static $query
     * @param string|int $key
     * @param mixed $value
     * @param string $join
     * 
     * @return void
     */
    protected function arrayGroupByNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(array($query, 'groupBy'), array_values($value));
        } else {
            call_user_func_array(array($query, 'groupBy'), array($value));
        }
    }

    /**
     * @param string|array $column
     * @param string $sort
     * 
     * @return static
     */
    public function groupBy($column, $sort = 'ASC')
    {
        // 參數若為: array(array($column, $sort), array($column, $sort))
        if (is_array($column)) {
            return $this->arrayNested($column, '', 'groupBy');
        }

        if ($column instanceof Closure || $column instanceof self) {
            return $this->groupBySub($column, $sort);
        }

        return $this->queryPush("{$column} {$sort}", 'groups');
    }

    /**
     * @param string|array|callback|static $column
     * @param string $sort
     * 
     * @return static
     */
    public function groupBySub($column, $sort = 'ASC')
    {
        list($sub, $bindings) = $this->createSub($column);

        if (!empty($bindings)) {
            $this->bindingPush($bindings, 'groups');
        }

        return $this->queryPush("({$sub}) {$sort}", 'groups');
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function groupByAsc($column)
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        return $this->groupBy(
            array_map(function ($column) {
                return array($column, 'ASC');
            }, $column)
        );
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function groupByDesc($column)
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        return $this->groupBy(
            array_map(function ($column) {
                return array($column, 'DESC');
            }, $column)
        );
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    protected function havingNested($callback, $join)
    {
        return $this->nested($callback, $join, 'havings');
    }

    /**
     * @param static $query
     * @param string|int $key
     * @param mixed $value
     * @param string $join
     * 
     * @return void
     */
    protected function arrayHavingNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(array($query, 'having'), array_values($value));
        } else {
            call_user_func_array(array($query, 'having'), array($key, '=', $value, $join));
        }
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @param  string  $andOr
     * 
     * @return static
     */
    public function having($column, $operator = null, $value = null, $andOr = 'and')
    {
        // 參數若為: array(array($column, $operator, $value, $andOr), array($column, $operator, $value, $andOr))
        if (is_array($column)) {
            return $this->arrayNested($column, $andOr, 'having');
        }

        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        if ($column instanceof Closure && is_null($operator)) {
            return $this->havingNested($column, $andOr);
        }

        if ($column instanceof self && !is_null($operator)) {
            list($sub, $bindings) = $this->createSub($column);

            if (!empty($bindings)) {
                $this->bindingPush($bindings, 'havings');
            }

            return $this->having($this->raw('(' . $sub . ')'), $operator, $value, $andOr);
        }

        if ($this->invalidOperator($operator)) {
            list($value, $operator) = array($operator, '=');
        }

        if ($value instanceof self || $value instanceof Closure) {
            list($sub, $bindings) = $this->createSub($value);

            if (!empty($bindings)) {
                $this->bindingPush($bindings, 'havings');
            }

            return $this->having($column, $operator, $this->raw('(' . $sub . ')'), $andOr);
        }

        if ($value instanceof Expression) {
            $andOr = strtoupper($andOr);

            $operator = strtoupper($operator);

            return $this->queryPush("{$andOr} {$column} {$operator} {$value}", 'havings');
        }

        if (is_null($value)) {
            $value = 'NULL';
        }

        $varValue = '?';

        if (is_array($value)) {
            $varValue = "(" . join(',', array_fill(0, count($value), "?")) . ")";
        }

        $operator = strtoupper($operator);

        $andOr = strtoupper($andOr);

        $this->queriesPush("{$andOr} {$column} {$operator} {$varValue}", $value, 'havings');

        return $this;
    }

    /**
     * @param string|array $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    protected function orderByNested($callback, $join)
    {
        return $this->nested($callback, $join, 'orders');
    }

    /**
     * @param static $query
     * @param string|int $key
     * @param mixed $value
     * @param string $join
     * 
     * @return void
     */
    protected function arrayOrderByNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(array($query, 'orderBy'), array_values($value));
        } else {
            call_user_func_array(array($query, 'orderBy'), array($value));
        }
    }

    /**
     * @param string|array $column
     * @param string $sort
     * 
     * @return static
     */
    public function orderBy($column, $sort = 'ASC')
    {
        // 參數若為: array(array($column, $sort), array($column, $sort))
        if (is_array($column)) {
            return $this->arrayNested($column, '', 'orderBy');
        }

        if ($column instanceof Closure || $column instanceof self) {
            return $this->orderBySub($column, $sort);
        }

        return $this->queryPush("{$column} {$sort}", 'orders');
    }

    /**
     * @param string|array $column
     * @param string $sort
     * 
     * @return static
     */
    public function orderBySub($column, $sort = 'ASC')
    {
        list($sub, $bindings) = $this->createSub($column);

        if (!empty($bindings)) {
            $this->bindingPush($bindings, 'orders');
        }

        return $this->queryPush("({$sub}) {$sort}", 'orders');
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function orderByAsc($column)
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        return $this->orderBy(
            array_map(function ($column) {
                return array($column, 'ASC');
            }, $column)
        );
    }

    /**
     * @param string|array $column
     * 
     * @return static
     */
    public function orderByDesc($column)
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        return $this->orderBy(
            array_map(function ($column) {
                return array($column, 'DESC');
            }, $column)
        );
    }

    /**
     * @return static
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * @param array $columns
     * 
     * @return array
     */
    public function first($columns = array('*'))
    {
        if (!$this->getQuery('columns.queries') || array('*') != $columns) {
            $this->select($columns);
        }

        return $this->getConnection()->exec(
            $this->toSql() . " LIMIT 1",
            $this->getBindings(array('insert', 'update'))
        )->fetch();
    }

    /**
     * @param int|string $target
     * @param string $column
     * @param array $columns
     * 
     * @return array
     */
    public function find($target, $column = 'id', $columns = array('*'))
    {
        return $this->where($column, $target)->first($columns);
    }

    /**
     * @param array $columns
     * 
     * @return array
     */
    public function get($columns = array('*'))
    {
        if (!$this->getQuery('columns.queries') || array('*') != $columns) {
            $this->select($columns);
        }

        return $this->getConnection()->exec(
            $this->toSql(),
            $this->getBindings(array('insert', 'update'))
        )->fetchAll();
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function update($data)
    {
        $values = array();

        $columns = array();

        foreach ($data as $column => $value) {
            if ($value instanceof Closure || $value instanceof self) {
                list($sql, $bindings) = $this->createSub($value);

                $columns[] = $this->raw("{$column} = ({$sql})");

                $values = array_merge($values, $bindings);
            } else if ($value instanceof Expression) {
                $columns[] = $value;
            } else {
                $columns[] = $column;

                $values[] = $value;
            }
        }

        return $this->bindingPush($values, 'update')->getConnection()->exec(
            $this->getGrammar()->compilerUpdate($this, $columns),
            $this->getBindings()
        )->rowCount();
    }

    /**
     * @param string $column
     * @param int|string|float $amount
     * @param array $data
     * 
     * @return static
     * 
     * @throws \InvalidArgumentException
     */
    public function increment($column, $amount = 1, $data = array())
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException(" second Arguments must be numeric");
        }

        $values = $data;

        array_push($values, $amount);

        return $this->bindingPush($values, 'update')->update(array_merge($data, [
            $this->raw("`{$column}` = `{$column}` + ?")
        ]));
    }

    /**
     * @param string $column
     * @param int|string|float $amount
     * @param array $data
     * 
     * @return static
     * 
     * @throws \InvalidArgumentException
     */
    public function decrement($column, $amount = 1, $data = array())
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException(" second Arguments must be numeric");
        }

        $values = $data;

        array_push($values, $amount);

        return $this->bindingPush($values, 'update')->update(array_merge($data, [
            $this->raw("`{$column}` = `{$column}` - ?")
        ]));
    }

    /**
     * @param array|[] $data
     * 
     * @return mixed
     */
    public function insert($data = array())
    {
        $first = current($data);

        if (!is_array($first)) {
            $data = array(
                $data
            );
        }

        $bindings = array_reduce($data, function ($carry, $values) {
            if (!$carry) {
                $carry = array();
            }

            $values = array_values($values);

            return array_merge($carry, $values);
        });

        return $this->bindingPush($bindings, 'insert')->getConnection()->exec(
            $this->getGrammar()->compilerInsert($this, $data),
            $this->getBindings()
        )->rowCount();
    }

    /**
     * @param array $columns
     * @param callback|static $query
     * 
     * @return mixed
     */
    public function insertSub($columns, $query)
    {
        list($sql, $bindings) = $this->createSub($query);

        return $this->bindingPush($bindings, 'insert')->getConnection()->exec(
            $this->getGrammar()->compilerInsert($this, array_flip($columns), $sql),
            $this->getBindings()
        )->rowCount();
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->getConnection()->exec(
            $this->getGrammar()->compilerDelete($this),
            $this->getBindings()
        )->rowCount();
    }

    /**
     * @param string $column
     * @param string $dateTimeFormat
     * 
     * @return static
     * 
     * @throws \InvalidArgumentException
     */
    public function softDelete(string $column = 'deleted_at', string $dateTimeFormat = "Y-m-d H:i:s")
    {
        if (!is_string($column)) {
            throw new \InvalidArgumentException(" first Arguments must be string");
        }

        $value = date($dateTimeFormat);

        return $this->update([
            $column => $value
        ]);
    }

    /**
     * @param string $column
     * 
     * @return static
     * 
     * @throws \InvalidArgumentException
     */
    public function reStore(string $column = 'deleted_at')
    {
        if (!is_string($column)) {
            throw new \InvalidArgumentException(" first Arguments must be string");
        }

        $value = null;

        return $this->update([
            $column => $value
        ]);
    }

    /**
     * Get a new join clause.
     *
     * @param  static  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * 
     * @return JoinClause
     */
    public function newJoinClause($parentQuery, $type, $table)
    {
        return new JoinClause($parentQuery, $type, $table);
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool|false  $isWhere
     * 
     * @return static
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $isWhere = false)
    {
        $join = $this->newJoinClause($this, $type, $table);

        $method = $isWhere ? 'WHERE' : 'ON';

        if ($first instanceof Closure) {
            call_user_func($first, $join);

            list('queries'  => $queries, 'bindings' => $bindings) = array_replace([
                'bindings'  => array(),
                'queries'   => array(),
            ], $join->getQuery('joins'));

            if (!empty($bindings)) {
                $this->bindingPush($bindings, 'joins');
            }

            $sql = join(' ', $queries);

            $sql = ltrim(ltrim($sql, 'AND '), 'OR ');

            $type = strtoupper($type);

            return $this->queryPush("{$type} JOIN {$table} {$method} {$sql}", 'joins');
        }

        // 如果帶入參數只有兩個，則 $second = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($second, $operator) = $this->prepareValueAndOperator(
            $second,
            $operator,
            func_num_args() === 3
        );

        if ($this->invalidOperator($operator)) {
            list($second, $operator) = array($operator, '=');
        }

        $type = strtoupper($type);

        return $this->queryPush("{$type} JOIN {$table} {$method} {$first} {$operator} {$second}", 'joins');
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function joinWhere($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        return $this->join($table, $first, $operator, $second, $type, true);
    }

    /**
     * Add a subquery join clause to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool|false  $isWhere
     * 
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function joinSub($table, $as, $first, $operator = null, $second = null, $type = 'inner', $isWhere = false)
    {
        list($query, $bindings) = $this->createSub($table);

        if (!empty($bindings)) {
            $this->bindingPush($bindings, 'joins');
        }

        $table = "({$query}) AS `{$as}`";

        return $this->join($table, $first, $operator, $second, $type, $isWhere);
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function joinSubWhere($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        return $this->joinSub($table, $first, $operator, $second, $type, true);
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function leftJoinSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'left');
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function leftJoinWhere($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left', true);
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function leftJoinSubWhere($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'left', true);
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function rightJoinSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'right');
    }

    /**
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function rightJoinWhere($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right', true);
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function rightJoinSubWhere($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'right', true);
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return $this
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'cross');
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function crossJoinSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'cross');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return $this
     */
    public function crossJoinWhere($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'cross', true);
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function crossJoinSubWhere($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'cross', true);
    }

    /**
     * @param int|string $limit
     * @param int|string|null $offset
     * 
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $bindings = array($limit);

        $queries = array("?");

        if ($offset) {
            $bindings[] = $offset;

            $queries[] = "?";
        }

        return $this->setQuery('limit', compact('queries', 'bindings'));
    }

    /**
     * @param int|string $offset
     * 
     * @return static
     */
    public function offset($offset)
    {
        return $this->setQuery('offset.queries', "?")->setQuery('offset.bindings', $offset);
    }

    /**
     * @param int|string $page
     * 
     * @return static
     */
    public function currentPage($page)
    {
        return $this->offset($page);
    }

    /**
     * @param int|string $page
     * 
     * @return static
     */
    public function prePage($page)
    {
        return $this->limit($page);
    }

    /**
     * @param int|string|null $prePage
     * @param int|string|null $currentPage
     * 
     * @return array
     */
    public function getForPage($prePage = 10, $currentPage = 1)
    {
        $prePage = $this->getQuery('limit.bindings', $prePage);

        $currentPage = $this->getQuery('offset.bindings', $currentPage);

        return $this->prePage($prePage)->currentPage(((int) $currentPage - 1) * $prePage)->get();
    }

    /**
     * Add a union statement to the query.
     *
     * @param  static|callback|\Closure  $query
     * @param  bool  $all
     * @return static
     */
    public function union($query, $all = false)
    {
        list($queries, $bindings) = $this->createSub($query);

        $type = $all ? 'UNION ALL' : 'UNION';

        return $this->queriesPush("{$type} {$queries}", $bindings, 'unions');
    }

    /**
     * Add a union all statement to the query.
     *
     * @param  static|callback|\Closure  $query
     * @return static
     */
    public function unionAll($query)
    {
        return $this->union($query, true);
    }

    /**
     * @return static
     */
    public function lockForUpdate()
    {
        return $this->setQuery('lock', $this->getGrammar()->lockForUpdate());
    }

    /**
     * @return static
     */
    public function sharedLock()
    {
        return $this->setQuery('lock', $this->getGrammar()->sharedLock());
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        if (empty($this->methods)) {
            $methods = array(
                'set'       => array(
                    'table', 'username', 'password', 'database', 'host',
                    'raw', 'from',
                ),
                'process'   => array(
                    'insertGetId',
                ),
                'get'       => array(
                    'parseQueryLog', 'lastParseQuery', 'lastInsertId',
                )
            );

            $this->methods = $methods;
        }

        $getMethods = array_map(function ($bindMethods, $bindMethod) use ($method) {
            if (in_array($method, $bindMethods)) {
                return $bindMethod . ucfirst($method);
            }

            return false;
        }, $this->methods, array_keys($this->methods));

        $getMethods = array_filter($getMethods);

        return current($getMethods) ?: $method;
    }

    /**
     * @param string $method
     * @param mixed $abstract
     * 
     * @return string
     */
    public function callForce($method, $abstract)
    {
        $forceMethods = array(
            'getQueryLog', 'getParseQueryLog', 'getLastParseQuery',
        );

        if (in_array($method, $forceMethods)) {
            return $abstract;
        }

        return $abstract ?: $this;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public function __call($method, $arguments)
    {
        $method = $this->method($method);

        $abstract = null;

        foreach ($this->getResolvers() as $resolver) {
            if (!method_exists($resolver, $method)) {
                continue;
            }

            if ($resolver instanceof ProcessorInterface) {
                array_unshift($arguments, $this);
            }

            $abstract = call_user_func_array(array($resolver, $method), $arguments);

            is_object($abstract) && $this->resolverRegister($abstract);

            if ($abstract instanceof GrammarInterface) {
                $abstract = $this;
            }
        }

        return $this->callForce($method, $abstract);
    }
}
