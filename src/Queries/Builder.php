<?php

namespace Wilkques\Database\Queries;

use Closure;
use InvalidArgumentException;
use Wilkques\Database\Connections\Connections;
use Wilkques\Database\Queries\Grammar\Grammar;
use Wilkques\Database\Queries\Processors\ProcessorInterface;
use Wilkques\Helpers\Arrays;

class Builder
{
    /** @var array */
    protected $resolvers = array();

    /** @var array */
    protected $queries = array();

    /** 
     * @var array
     */
    protected $methods = array(
        'set'       => array(
            'table',
            'username',
            'password',
            'database',
            'host',
            'raw',
            'from',
        ),
        'process'   => array(
            'insertGetId',
        ),
        'get'       => array(
            'parseQueryLog',
            'lastParseQuery',
            'lastInsertId',
            'queryLog',
            'lastQueryLog',
        )
    );

    /**
     * All of the available clause operators.
     *
     * @var string[]
     */
    protected $operators = array(
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'rlike',
        'not rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
        'is not',
        'is',
        'not in',
        'in',
        'exists',
        'not exists',
        'between',
        'not between',
    );

    /** @var array */
    protected $bindingComponents = array(
        'columns',
        'froms',
        'joins',
        'insert',
        'update',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limits',
        'offset',
        'unions',
    );

    /**
     * @param Connections $connection
     * @param Grammar|null $grammar
     * @param ProcessorInterface|null $processor
     */
    public function __construct(
        Connections $connection,
        Grammar $grammar = null,
        ProcessorInterface $processor = null
    ) {
        $this->setConnection($connection)
            ->setGrammar($grammar)
            ->setProcessor($processor);
    }

    /**
     * @param Connections $connection
     * @param Grammar|null $grammar
     * @param ProcessorInterface|null $processor
     * 
     * @return static
     */
    public static function make(
        Connections $connection,
        Grammar $grammar = null,
        ProcessorInterface $processor = null
    ) {
        return new static($connection, $grammar, $processor);
    }

    /**
     * @param string|object $abstract
     * @param object|null $resolver
     * 
     * @return static
     */
    protected function resolverRegister($abstract, $resolver = null)
    {
        if (is_null($abstract)) {
            return $this;
        }

        if (is_object($abstract)) {
            $resolver = $abstract;

            $abstract = get_class($abstract);
        }

        Arrays::set($this->resolvers, $abstract, $resolver);

        return $this;
    }

    /**
     * @return array
     */
    protected function getResolvers()
    {
        return $this->resolvers;
    }

    /**
     * @param string $abstract
     * 
     * @return mixed
     */
    protected function getResolver($abstract)
    {
        $abstract = Arrays::filter($this->getResolvers(), function ($resolver) use ($abstract) {
            $resolvers = Arrays::mergeDistinctRecursive(class_parents($resolver), class_implements($resolver));

            return in_array($abstract, $resolvers);
        });

        return Arrays::first($abstract);
    }

    /**
     * @param Connections $connection
     * 
     * @return static
     */
    public function setConnection(Connections $connection)
    {
        return $this->resolverRegister($connection);
    }

    /**
     * @return Connections
     */
    public function getConnection()
    {
        return $this->getResolver('Wilkques\Database\Connections\Connections');
    }

    /**
     * @param Grammar $grammar
     * 
     * @return static
     */
    public function setGrammar(Grammar $grammar = null)
    {
        return $this->resolverRegister($grammar);
    }

    /**
     * @return Grammar
     */
    public function getGrammar()
    {
        return $this->getResolver('Wilkques\Database\Queries\Grammar\Grammar');
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
        return $this->getResolver('Wilkques\Database\Queries\Processors\ProcessorInterface');
    }

    /**
     * @param array $queries
     * 
     * @return static
     */
    public function setQueries($queries)
    {
        $this->queries = $queries;

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
     * @param mixed $value
     * 
     * @return static
     */
    public function setQuery($key, $value = null)
    {
        Arrays::set($this->queries, $key, $value);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed|null
     */
    public function getQuery($key, $default = null)
    {
        return Arrays::get($this->getQueries(), $key, $default);
    }

    /**
     * @return static
     */
    public function newQuery()
    {
        return new static($this->getConnection(), $this->getGrammar(), $this->getProcessor());
    }

    /**
     * Prepend the database name if the given query is on another database.
     *
     * @param  static  $query
     * @return static
     */
    protected function prependDatabaseNameIfCrossDatabaseQuery($query)
    {
        if ($query->getConnection()->getDatabase() !== $this->getConnection()->getDatabase()) {
            Arrays::map($query->getFrom(), function ($from, $index) use ($query) {
                $database = $query->getConnection()->getDatabase();

                if (strpos($from, $database) !== 0 && strpos($from, '.') === false) {
                    $query->setFrom($query->contactBacktick($database, $from), $index);
                }
            });
        }

        return $query;
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  static|string|Expression  $query
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
     * @param string|callback|static|Closure $callback
     * 
     * @return array
     * 
     * @throws InvalidArgumentException
     */
    protected function createSub($callback)
    {
        if ($callback instanceof Closure || is_callable($callback)) {
            call_user_func($callback, $callback = $this->forSubQuery());
        }

        return $this->parseSub($callback);
    }

    /**
     * @return string
     */
    public function toSql()
    {
        return $this->getGrammar()->compilerSelect($this);
    }

    /**
     * @param array $bindings
     * 
     * @return array
     */
    protected function bindingsNested($bindings)
    {
        $callback = function ($value) {
            if (!$value instanceof Expression) {
                return $value;
            }
        };

        $newArray = array();

        foreach ($bindings as $key => $value) {
            $result = $callback($value, $key);

            if ($result || is_numeric($result)) {
                $newArray[$key] = $value;
            }
        }

        return array_values($bindings);
    }

    /**
     * @param array|[] $except
     * 
     * @return array
     */
    protected function getBindings($except = array())
    {
        $components = Arrays::filter($this->bindingComponents, function ($component) use ($except) {
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
        $this->queryPush($query, $type);

        if (!empty($binding)) {
            $this->bindingPush($binding, $type);
        }

        return $this;
    }

    /**
     * @param  mixed  $query
     * @param  string  $type
     * 
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    protected function queryPush($query, $type = 'wheres')
    {
        $this->queries[$type]['queries'][] = $query;

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * 
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

            $this->queries[$type]['bindings'] = array_merge($this->queries[$type]['bindings'], $this->bindingsNested($values));

            return $this;
        }

        if (!$values instanceof Expression) {
            $this->queries[$type]['bindings'][] = $values;
        }

        return $this;
    }

    /**
     * @param string $query
     * @param string|Expression|null $as
     * 
     * @return string
     */
    protected function subQueryAsContactBacktick($query, $as = null)
    {
        $query = "({$query})";

        if (is_string($as) || $as instanceof Expression) {
            $query = "{$query} AS {$this->contactBacktick($as)}";
        }

        return $query;
    }

    /**
     * @param string $query
     * @param string|Expression|null $as
     * 
     * @return string
     */
    protected function queryAsContactBacktick($query, $as = null)
    {
        $query = $this->contactBacktick($query);

        if (is_string($as) || $as instanceof Expression) {
            $query = "{$query} AS {$this->contactBacktick($as)}";
        }

        return $query;
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
     * Add a new "raw" select expression to the query.
     *
     * @param  string  $expression
     * @param  array  $bindings
     * 
     * @return static
     */
    public function fromRaw($expression, $bindings = array())
    {
        return $this->queriesPush($this->raw($expression), $bindings, 'froms');
    }

    /**
     * @param string $from
     * @param int|string $index
     * 
     * @return static
     */
    public function setFrom($from, $index = 0)
    {
        $this->queries['froms']['queries'][$index] = $from;

        return $this;
    }

    /**
     * @param string|callback|Closure|static|array $froms
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
                $this->fromRaw($this->queryAsContactBacktick($from, $as));
            }
        }

        return $this;
    }

    /**
     * @param callback|Closure|static|array $from
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

        return $this->fromRaw($this->subQueryAsContactBacktick($query, $as), $bindings);
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        return $this->getQuery("froms.queries");
    }

    /**
     * @param string|callback|Closure $table
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
     * Add a new "raw" select expression to the query.
     *
     * @param  string  $expression
     * @param  array  $bindings
     * 
     * @return static
     */
    public function selectRaw($expression, $bindings = array())
    {
        return $this->queriesPush($this->raw($expression), $bindings, 'columns');
    }

    /**
     * @param array<string|Closure|static>|string
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
                $column = $column == '*' ? $column : $this->queryAsContactBacktick($column, $as);

                $this->queryPush($column, 'columns');
            }
        }

        return $this;
    }

    /**
     * @param string|callback|Closure|static $column
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

        return $this->selectRaw($this->subQueryAsContactBacktick($queries, $as), $bindings);
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
            !in_array($operator, array('=', '<>', '!=', 'is not', 'is'));
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * 
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return array($operator, '=');
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return array($value, $operator);
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
        return call_user_func(function ($column, $method, $join, $query) {
            $nestedMethod = "{$method}Nested";

            return call_user_func(array($query, $nestedMethod), function ($query) use ($column, $method, $join) {
                foreach ($column as $key => $value) {
                    $nestedMethod = 'array' . ucfirst($method) . 'Nested';

                    call_user_func(array($query, $nestedMethod), $query, $key, $value, $join);
                }
            }, $join, $query);
        }, $column, $method, $join, $this);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  Closure|callback  $callback
     * @param  string  $join
     * @param  string  $type
     * 
     * @return static
     */
    protected function nested($callback, $join = 'and', $type = 'wheres')
    {
        call_user_func($callback, $query = $this->forNested());

        return $this->addNestedQuery($query, $join, $type);
    }

    /**
     * Create a new query instance for nested condition.
     *
     * @return Builder
     */
    protected function forNested()
    {
        return $this->newQuery()->from($this->getQuery('froms.queries'));
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
    protected function addNestedQuery($query, $join = 'and', $type = 'wheres')
    {
        $queries = $query->getQuery("{$type}.queries");

        if (in_array($type, array('groups', 'orders'))) {
            $sql = join(', ', $queries);

            $this->queryPush($sql, $type);
        } else {
            $sql = join(' ', $queries);

            $sql = $this->firstJoinReplace($sql);

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
     * @param string $query
     * 
     * @return string
     */
    public function firstJoinReplace($query)
    {
        return preg_replace('/^(AND |OR )/i', '', $query);
    }

    /**
     * @param array $value
     * @param string $join
     * 
     * @return array
     */
    protected function nestedArrayArguments($value, $join)
    {
        return array_replace(
            array(
                null,
                null,
                null,
                $join
            ),
            $value
        );
    }

    /**
     * @param array $columns
     * @param string $operator
     * 
     * @return array
     */
    protected function columnArguments($columns, $operator)
    {
        return Arrays::map($columns, function ($column) use ($operator) {
            if (count($column) == 1) {
                return array($column[0], $operator, null);
            }

            if (count($column) == 2) {
                return array($column[0], $operator, $column[1]);
            }

            return array($column[0], $operator, $column[2]);
        });
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    public function whereNested($callback, $join = 'and')
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
    public function arrayWhereNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(
                array($query, 'where'),
                array_values($this->nestedArrayArguments($value, $join))
            );
        } else {
            call_user_func_array(array($query, 'where'), array($key, '=', $value, $join));
        }
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @param  string  $andOr
     * 
     * @return static
     */
    public function whereRaw($sql, $bindings = array(), $andOr = 'and')
    {
        return $this->queriesPush($this->raw("{$andOr} {$sql}"), $bindings);
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * 
     * @return static
     */
    public function orWhereRaw($sql, $bindings = array())
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * @param string|array|callback|Closure|static $column
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
            return $this->whereExists($column, $andOr);
        }

        if ($value instanceof self || $value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $andOr);
        }

        if ($value instanceof Expression) {
            $andOr = strtoupper($andOr);

            $operator = strtoupper($operator);

            return $this->whereRaw("{$this->contactBacktick($column)} {$operator} {$value}", array(), $andOr);
        }

        if (is_null($value)) {
            return $this->whereNull($column, $operator, $andOr, $operator === 'is not');
        }

        $varValue = '?';

        $andOr = strtoupper($andOr);

        if (is_array($value)) {
            if (in_array($operator, array('between', 'not between'))) {
                $varValue = join(" AND ", array_fill(0, count($value), "?"));
            } else {
                $varValue = "(" . join(', ', array_fill(0, count($value), "?")) . ")";
            }
        }

        $operator = strtoupper($operator);

        return $this->whereRaw("{$this->contactBacktick($column)} {$operator} {$varValue}", $value, $andOr);
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
     * Add a full sub-select to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  Closure|callback|null  $callback
     * @param  string  $andOr
     * 
     * @return static
     */
    public function whereSub($column, $operator, $callback = null, $andOr = 'and')
    {
        if (is_string($operator)) {
            $operator = strtolower($operator);
        }

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

        return $this->whereRaw("{$this->contactBacktick($column)} {$operator} ({$sql})", $bindings, $andOr);
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  Closure|callback|null  $callback
     * 
     * @return static
     */
    public function orWhereSub($column, $operator, $callback = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($callback, $operator) = $this->prepareValueAndOperator(
            $callback,
            $operator,
            func_num_args() === 2
        );

        return $this->whereSub($column, $operator, $callback, 'or');
    }

    /**
     * @param string|array $column
     * @param string $operator
     * @param string $andOr
     * @param bool|false $not
     * 
     * @return static
     */
    public function whereNull($column, $operator = 'is', $andOr = 'and', $not = false)
    {
        if (is_array($column)) {
            $column = $this->columnArguments($column, $operator);

            return $this->where($column, null, null, $andOr);
        }

        if ($column instanceof Closure) {
            return $this->whereNested($column, $andOr);
        }

        $andOr = strtoupper($andOr);

        $type = $not ? 'NOT NULL' : 'NULL';

        return $this->whereRaw("{$this->contactBacktick($column)} IS {$type}", array(), $andOr);
    }

    /**
     * @param string $column
     * @param string $operator
     * @param bool|false $not
     * 
     * @return static
     */
    public function orWhereNull($column, $operator = 'is', $not = false)
    {
        return $this->whereNull($column, $operator, 'or', $not);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function whereNotNull($column)
    {
        return $this->whereNull($column, 'is not', 'and', true);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function orWhereNotNull($column)
    {
        return $this->orWhereNull($column, 'is not', true);
    }

    /**
     * @param string|array $column
     * @param array|callback|Closure $in
     * @param string $operator
     * 
     * @return static
     */
    public function whereIn($column, $in = null, $operator = 'in')
    {
        if (is_array($column)) {
            $column = $this->columnArguments($column, $operator);

            return $this->where($column);
        }

        if ($in instanceof Closure || $in instanceof self) {
            return $this->whereSub($column, $operator, $in);
        }

        return $this->where($column, $operator, $in);
    }

    /**
     * @param string|array $column
     * @param array $in
     * @param string $operator
     * 
     * @return static
     */
    public function orWhereIn($column, $in = null, $operator = 'in')
    {
        if (is_array($column)) {
            $column = $this->columnArguments($column, $operator);

            return $this->orWhere($column);
        }

        if ($in instanceof Closure || $in instanceof self) {
            return $this->orWhereSub($column, $operator, $in);
        }

        return $this->orWhere($column, $operator, $in);
    }

    /**
     * @param string|array $column
     * @param array $in
     * 
     * @return static
     */
    public function whereNotIn($column, $in = null)
    {
        return $this->whereIn($column, $in, 'not in');
    }

    /**
     * @param string|array $column
     * @param array $in
     * 
     * @return static
     */
    public function orWhereNotIn($column, $in = null)
    {
        return $this->orWhereIn($column, $in, 'not in');
    }

    /**
     * @param string|array $column
     * @param string $value
     * @param string $andOr
     * 
     * @return static
     */
    public function whereLike($column, $value = null, $andOr = 'and')
    {
        if (is_array($column)) {
            $column = $this->columnArguments($column, 'like');

            return $this->where($column, null, null, $andOr);
        }

        if ($column instanceof Closure) {
            list($query, $bindings) = $this->createSub($column);

            array_push($bindings, $value);

            $andOr = strtoupper($andOr);

            return $this->whereRaw("({$query}) LIKE ?", $bindings, $andOr);
        }

        return $this->where($column, 'like', $value, $andOr);
    }

    /**
     * @param string|array $column
     * @param string $value
     * 
     * @return static
     */
    public function orWhereLike($column, $value = null)
    {
        return $this->whereLike($column, $value, 'or');
    }

    /**
     * @param callback|Closure|static $callback
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

        return $this->whereRaw(sprintf("%sEXISTS (%s)", $type, $sql), $bindings, $andOr);
    }

    /**
     * @param callback|Closure|static $callback
     * 
     * @return static
     */
    public function whereNotExists($callback)
    {
        return $this->whereExists($callback, 'and', true);
    }

    /**
     * @param callback|Closure|static $callback
     * @param bool|false $not
     * 
     * @return static
     */
    public function orWhereExists($callback, $not = false)
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * @param callback|Closure|static $callback
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
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param callback|Closure $value
     * 
     * @return static
     */
    public function whereAny($column, $operator = '=', $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->whereSub($column, "{$operator} ANY", $value);
    }

    /**
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhereAny($column, $operator = null, $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->orWhereSub($column, "{$operator} ANY", $value);
    }

    /**
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function whereAll($column, $operator = null, $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->whereSub($column, "{$operator} ALL", $value);
    }

    /**
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhereAll($column, $operator = null, $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->orWhereSub($column, "{$operator} ALL", $value);
    }

    /**
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function whereSome($column, $operator = null, $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->whereSub($column, "{$operator} SOME", $value);
    }

    /**
     * @param string|callback|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhereSome($column, $operator = null, $value = null)
    {
        // 如果帶入參數只有兩個，則 $value = $operator and $operator = '='
        // 若否判斷 $operator 在 $this->operators 裡面有無符合的
        // 有，返回 $operator 無，則返回例外
        list($value, $operator) = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->orWhereSub($column, "{$operator} SOME", $value);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    public function groupByNested($callback, $join)
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
    public function arrayGroupByNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(
                array($query, 'groupBy'),
                array_values($this->nestedArrayArguments($value, $join))
            );
        } else {
            call_user_func(array($query, 'groupBy'), $value);
        }
    }

    /**
     * Add a raw groupBy clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * 
     * @return static
     */
    public function groupByRaw($sql, $bindings = array())
    {
        return $this->queriesPush($this->raw($sql), $bindings, 'groups');
    }

    /**
     * @param string|array|callback|Closure|static $column
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

        $sort = strtoupper($sort);

        if ($column instanceof Closure || $column instanceof self) {
            return $this->groupBySub($column, $sort);
        }

        return $this->groupByRaw("{$this->contactBacktick($column)} {$sort}");
    }

    /**
     * @param string|callback|Closure|static $column
     * @param string $sort
     * 
     * @return static
     */
    public function groupBySub($column, $sort = 'ASC')
    {
        list($sub, $bindings) = $this->createSub($column);

        return $this->groupByRaw("({$sub}) {$sort}", $bindings);
    }

    /**
     * @param array $columns
     * 
     * @return array
     */
    protected function sortByAsc($columns)
    {
        return Arrays::map($columns, function ($column) {
            if (is_array($column)) {
                array_push($column, 'ASC');

                return $column;
            }

            return array($column, 'ASC');
        });
    }

    /**
     * @param array $columns
     * 
     * @return array
     */
    protected function sortByDesc($columns)
    {
        return Arrays::map($columns, function ($column) {
            if (is_array($column)) {
                array_push($column, 'DESC');

                return $column;
            }

            return array($column, 'DESC');
        });
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
            $this->sortByAsc($column)
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
            $this->sortByDesc($column)
        );
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    public function havingNested($callback, $join)
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
    public function arrayHavingNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(
                array($query, 'having'),
                array_values($this->nestedArrayArguments($value, $join))
            );
        } else {
            call_user_func_array(array($query, 'having'), array($key, '=', $value, $join));
        }
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @param  string  $andOr
     * @return static
     */
    public function havingRaw($sql, $bindings = array(), $andOr = 'and')
    {
        return $this->queriesPush($this->raw("{$andOr} {$sql}"), $bindings, 'havings');
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @return static
     */
    public function orHavingRaw($sql, $bindings = array())
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param  string|array|closure|static  $column
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

            return $this->havingRaw("{$this->contactBacktick($column)} {$operator} {$value}", array(), $andOr);
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

        return $this->havingRaw("{$this->contactBacktick($column)} {$operator} {$varValue}", $value, $andOr);
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
     * @param  Closure|callback  $callback
     * @param  string  $join
     * 
     * @return static
     */
    public function orderByNested($callback, $join)
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
    public function arrayOrderByNested($query, $key, $value, $join)
    {
        if (is_numeric($key) && is_array($value)) {
            call_user_func_array(
                array($query, 'orderBy'),
                array_values($this->nestedArrayArguments($value, $join))
            );
        } else {
            call_user_func(array($query, 'orderBy'), $value);
        }
    }

    /**
     * Add a raw orderBy clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * 
     * @return static
     */
    public function orderByRaw($sql, $bindings = array())
    {
        return $this->queriesPush($this->raw($sql), $bindings, 'orders');
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

        $sort = strtoupper($sort);

        if ($column instanceof Closure || $column instanceof self) {
            return $this->orderBySub($column, $sort);
        }

        return $this->orderByRaw("{$this->contactBacktick($column)} {$sort}");
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

        return $this->orderByRaw("({$sub}) {$sort}", $bindings);
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
            $this->sortByAsc($column)
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
            $this->sortByDesc($column)
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

        $this->limit($this->raw(1));

        return $this->getConnection()->exec(
            $this->toSql(),
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
     * @return int
     */
    public function update($data)
    {
        $values = array();

        $columns = array();

        foreach ($data as $column => $value) {
            if ($value instanceof Closure || $value instanceof self) {
                list($sql, $bindings) = $this->createSub($value);

                $columns[] = $this->raw("{$this->contactBacktick($column)} = ({$sql})");

                $values = array_merge($values, $bindings);
            } else if ($value instanceof Expression) {
                if (is_numeric($column)) {
                    $columns[] = $value;
                } else {
                    $columns[] = $this->raw("{$this->contactBacktick($column)} = ({$value})");
                }
            } else {
                $columns[] = $this->contactBacktick($column);

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
     * @return int
     * 
     * @throws \InvalidArgumentException
     */
    public function increment($column, $amount = 1, $data = array(), $isIncrement = true)
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException("second Arguments must be numeric");
        }

        $formula = $isIncrement ? '+' : '-';

        return $this->bindingPush(array($amount), 'update')
            ->update(
                array_merge(
                    $data,
                    array(
                        $this->raw("{$this->contactBacktick($column)} = {$this->contactBacktick($column)} {$formula} ?")
                    )
                )
            );
    }

    /**
     * @param string $column
     * @param int|string|float $amount
     * @param array $data
     * 
     * @return int
     * 
     * @throws \InvalidArgumentException
     */
    public function decrement($column, $amount = 1, $data = array())
    {
        return $this->increment($column, $amount, $data, false);
    }

    /**
     * @param array|[] $data
     * 
     * @return int
     */
    public function insert($data = array())
    {
        if (!is_array(current($data))) {
            $data = array(
                $data
            );
        }

        $self = $this;

        $bindings = array_reduce($data, function ($carry, $values) use ($self) {
            if (!$carry) {
                $carry = array();
            }

            $values = array_values(
                array_filter($self->bindingsNested($values))
            );

            return array_merge($carry, $values);
        });

        return $this->bindingPush($bindings, 'insert')->getConnection()->exec(
            $this->getGrammar()->compilerInsert($this, $data),
            $this->getBindings()
        )->rowCount();
    }

    /**
     * @param array $columns
     * @param callback|Closure|static $query
     * 
     * @return int
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
     * @return int
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
     * @param null|mixed $value
     * 
     * @return int
     * 
     * @throws \InvalidArgumentException
     */
    public function reStore($column = 'deleted_at', $value = null)
    {
        if (!is_string($column)) {
            throw new \InvalidArgumentException(" first Arguments must be string");
        }

        if (is_null($value)) {
            return $this->update(array(
                $this->raw("{$this->contactBacktick($column)} = NULL")
            ));
        }

        return $this->update(array(
            $column => $value
        ));
    }

    /**
     * @param string $column
     * @param string $dateTimeFormat
     * 
     * @return int
     * 
     * @throws \InvalidArgumentException
     */
    public function softDelete($column = 'deleted_at', $dateTimeFormat = "Y-m-d H:i:s")
    {
        $value = date($dateTimeFormat);

        return $this->reStore($column, $value);
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
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool|false  $isWhere
     * 
     * @return static
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $isWhere = false)
    {
        $method = $isWhere ? 'WHERE' : 'ON';

        if ($first instanceof Closure || is_callable($first)) {
            $join = $this->newJoinClause($this, $type, $table);

            call_user_func($first, $join);

            $queriesArguments = array_replace(array(
                'queries'   => array(),
                'bindings'  => array(),
            ), $join->getQuery('joins'));

            $bindings = Arrays::get($queriesArguments, 'bindings');

            $queries = Arrays::get($queriesArguments, 'queries');

            if (!empty($bindings)) {
                $this->bindingPush($bindings, 'joins');
            }

            $sql = join(' ', $queries);

            $sql = $this->firstJoinReplace($sql);

            $type = strtoupper($type);

            return $this->queryPush($this->raw("{$type} JOIN {$table} {$method} {$sql}"), 'joins');
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

        return $this->queryPush($this->raw("{$type} JOIN {$table} {$method} {$first} {$operator} {$second}"), 'joins');
    }

    /**
     * @param  string  $table
     * @param  Closure|string  $first
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
     * @param  Closure|static|string  $table
     * @param  string  $as
     * @param  Closure|string  $first
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

        return $this->join($this->raw("({$query}) AS {$this->contactBacktick($as)}"), $first, $operator, $second, $type, $isWhere);
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function joinWhereSub($table, $as, $first, $operator = null, $second = null, $type = 'inner')
    {
        return $this->joinSub($table, $as, $first, $operator, $second, $type, true);
    }

    /**
     * @param  string  $table
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function leftJoinWhereSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'left', true);
    }

    /**
     * @param  string  $table
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
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
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function rightJoinWhereSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'right', true);
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  Closure|string|null  $first
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
     * @param  Closure|string  $first
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
     * @param  Closure|string|null  $first
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
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function crossJoinWhereSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'cross', true);
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * 
     * @return static
     */
    public function fullOuterJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'full outer');
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function fullOuterJoinSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'full outer');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * 
     * @return static
     */
    public function fullOuterJoinWhere($table, $first = null, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'full outer', true);
    }

    /**
     * @param  string  $table
     * @param  string  $as
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * 
     * @return static
     */
    public function fullOuterJoinWhereSub($table, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($table, $as, $first, $operator, $second, 'full outer', true);
    }

    /**
     * @param int|string $limit
     * @param int|string|null $offset
     * 
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $queries = array();

        $bindings = array();

        if ($offset) {
            if (!$offset instanceof Expression) {
                $bindings[] = $offset;
            }

            $queries[] = $offset instanceof Expression ? $offset : '?';
        }

        if ($limit instanceof Expression) {
            array_unshift($queries, $limit);

            return $this->setQuery('limits', compact('queries', 'bindings'));
        }

        array_unshift($bindings, $limit);

        array_unshift($queries, '?');

        return $this->setQuery('limits', compact('queries', 'bindings'));
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
        $prePage = $this->getQuery('limits.bindings', $prePage);

        if (is_array($prePage)) {
            $prePage = array_shift($prePage);
        }

        $currentPage = $this->getQuery('offset.bindings', $currentPage);

        return $this->prePage($prePage)->currentPage(((int) $currentPage - 1) * $prePage)->get();
    }

    /**
     * @return int
     */
    public function count()
    {
        $result = $this->getConnection()->exec(
            $this->getGrammar()->compilerCount($this),
            $this->getBindings(array('insert', 'update'))
        )->fetch();

        return Arrays::get($result, 'aggregate');
    }

    /**
     * Add a union statement to the query.
     *
     * @param  static|callback|Closure  $query
     * @param  bool  $all
     * @return static
     */
    public function union($query, $all = false)
    {
        list($queries, $bindings) = $this->createSub($query);

        $type = $all ? 'UNION ALL' : 'UNION';

        return $this->queriesPush($this->raw("{$type} {$queries}"), $bindings, 'unions');
    }

    /**
     * Add a union all statement to the query.
     *
     * @param  static|callback|Closure  $query
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
        return $this->setQuery('lock', 'lockForUpdate');
    }

    /**
     * @return static
     */
    public function sharedLock()
    {
        return $this->setQuery('lock', 'sharedLock');
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        foreach ($this->methods as $bindMethod => $bindMethods) {
            if (in_array($method, $bindMethods)) {
                return $bindMethod . ucfirst($method);
            }
        }

        return $method;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static|mixed
     */
    public function __call($method, $arguments)
    {
        $method = $this->method($method);

        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        }

        $abstract = null;

        $pass = false;

        foreach ($this->getResolvers() as $resolver) {
            if (!method_exists($resolver, $method)) {
                continue;
            }

            $pass = true;

            if ($resolver instanceof ProcessorInterface) {
                array_unshift($arguments, $this);
            }

            $abstract = call_user_func_array(array($resolver, $method), $arguments);

            is_object($abstract) && $this->resolverRegister($abstract);

            if ($abstract instanceof Grammar) {
                $abstract = $this;
            }
        }

        if (!$pass) {
            throw new \BadMethodCallException("Method: `{$method}` Not Exists");
        }

        return $abstract;
    }
}
