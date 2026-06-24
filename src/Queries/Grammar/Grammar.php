<?php

namespace Wilkques\Database\Queries\Grammar;

use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Expression;
use Wilkques\Helpers\Arrays;

class Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = array(
        'columns',
        'froms',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limits',
        'offset',
        'lock',
    );

    /**
     * @param array $array
     * @param callback|\Closure|null $forceValue
     * 
     * @return array
     */
    public function arrayNested($array, $forceValue = null)
    {
        return Arrays::map($array, function ($value) use ($forceValue) {
            if ($value instanceof Expression) {
                return (string) $value;
            }

            if (is_callable($forceValue) || $forceValue instanceof \Closure) {
                return call_user_func($forceValue, $value);
            }

            if ($forceValue) {
                return $forceValue;
            }

            return $value;
        });
    }

    /**
     * @param string|Expression|...string ...$value
     * 
     * @return string
     */
    public function contactBacktick($value)
    {
        if ($value instanceof Expression) {
            return (string) $value;
        }

        if (func_num_args() > 1) {
            $value = func_get_args();
        } else if (is_string($value)) {
            preg_match_all('/(\w+)/', $value, $matches);

            $value = array_pop($matches);
        }

        $value = Arrays::map($value, function ($value) {
            $value = trim($value, '`');

            return "`{$value}`";
        });

        return join(".", $value);
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerColumns($query)
    {
        $columns = $query->getQuery('columns.queries', array('*'));

        return join(', ', $this->arrayNested($columns));
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerSelect($query)
    {
        if (!$query->getQuery('columns.queries')) {
            $query->setQuery('columns.queries', array('*'));
        }

        $sql = $this->concatenate(
            $this->compilerComponent($query)
        );

        if ($query->getQuery('unions.queries')) {
            $sql .= ' ' . $this->compilerUnions($query);
        }

        return "SELECT {$sql}";
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerFroms($query)
    {
        $from = $query->getQuery('froms.queries', array());

        if (empty($from)) {
            return false;
        }

        $from = join(', ', $this->arrayNested($from));

        return "FROM {$from}";
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerWheres($query)
    {
        $wheres = $query->getQuery('wheres.queries', array());

        if (empty($wheres)) {
            return false;
        }

        $wheres = join(' ', $this->arrayNested($wheres));

        $wheres = $query->firstJoinReplace($wheres);

        return "WHERE {$wheres}";
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerHavings($query)
    {
        $havings = $query->getQuery('havings.queries', array());

        if (empty($havings)) {
            return false;
        }

        $havings = join(' ', $this->arrayNested($havings));

        $havings = $query->firstJoinReplace($havings);

        return "HAVING {$havings}";
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerLimits($query)
    {
        $limit = $query->getQuery('limits.queries', array());

        if (empty($limit)) {
            return false;
        }

        return "LIMIT " . join(', ', $this->arrayNested($limit));
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerGroups($query)
    {
        $groups = $query->getQuery('groups.queries', array());

        if (empty($groups)) {
            return false;
        }

        $groups = join(', ', $this->arrayNested($groups));

        return "GROUP BY " . ltrim($groups, ', ');
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerOrders($query)
    {
        $orders = $query->getQuery('orders.queries', array());

        if (empty($orders)) {
            return false;
        }

        $orders = join(', ', $this->arrayNested($orders));

        return "ORDER BY " . ltrim($orders, ', ');
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerOffset($query)
    {
        $offset = $query->getQuery('offset.queries', false);

        if (!$offset) {
            return false;
        }

        if ($offset instanceof Expression) {
            $offset = $offset->getValue();
        }

        return "OFFSET " . $offset;
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerLock($query)
    {
        $lock = $query->getQuery('lock', false);

        if (!$lock) {
            return false;
        }

        return call_user_func(array($this, $lock));
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerJoins($query)
    {
        $joins = $query->getQuery('joins.queries', array());

        if (empty($joins)) {
            return false;
        }

        return join(' ', $this->arrayNested($joins));
    }

    /**
     * @param Builder $query
     * 
     * @return array
     */
    protected function compilerComponent($query)
    {
        $sql = array();

        foreach ($this->selectComponents as $component) {
            if ($query->getQuery($component, false)) {
                $method = 'compiler' . ucfirst($component);

                $sql[$component] = call_user_func(array($this, $method), $query);
            }
        }

        return $sql;
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array  $segments
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments));
    }

    /**
     * @param Builder $query
     * @param array $columns
     * 
     * @return string
     */
    public function compilerUpdate($query, $columns)
    {
        $columns = $this->arrayNested($columns, function ($column) {
            return "{$column} = ?";
        });

        $columns = join(', ', $columns);

        $from = join(', ', $query->getFrom());

        if ($query->getQuery('joins')) {
            return $this->compilerUpdateWithJoins($query, $from, $columns);
        }

        return $this->compilerUpdateWithoutJoins($query, $from, $columns);
    }

    /**
     * Compile an update statement without joins into SQL.
     *
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $columns
     * 
     * @return string
     */
    protected function compilerUpdateWithoutJoins($query, $from, $columns)
    {
        return "UPDATE {$from} SET {$columns} {$this->compilerWheres($query)}";
    }

    /**
     * Compile an update statement with joins into SQL.
     *
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $columns
     * 
     * @return string
     */
    protected function compilerUpdateWithJoins($query, $from, $columns)
    {
        return "UPDATE {$from} {$this->compilerJoins($query)} SET {$columns} {$this->compilerWheres($query)}";
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerUnions($query)
    {
        $union = $query->getQuery('unions.queries', array());

        if (empty($union)) {
            return false;
        }

        return join(' ', $this->arrayNested($union));
    }

    /**
     * @param Builder $query
     * @param array|[] $columns
     * @param string|null $sql
     * 
     * @return string
     */
    public function compilerInsert($query, $data = array(), $sql = null)
    {
        $current = current($data);

        if (!is_array($current)) {
            $data = array(
                $data
            );
        }

        $from = join(', ', $query->getFrom());

        $current = current($data);

        if (empty($current)) {
            return "INSERT INTO {$from} DEFAULT VALUES";
        }

        $columns = Arrays::map(array_keys(current($data)), function ($column) use ($query) {
            return $query->contactBacktick($column);
        });

        $columns = join(', ', $columns);

        if (!$sql) {
            $values = Arrays::map($data, function ($values) use ($query) {
                return join(', ', $this->arrayNested($values, "?"));
            });

            $values = join('), (', $values);

            return $this->compilerInsertWithoutSubQuery($from, $columns, $values);
        }

        return $this->compilerInsertWithSubQuery($from, $columns, $sql);
    }

    /**
     * @param string $from
     * @param string $columns
     * @param string $values
     * 
     * @return string
     */
    protected function compilerInsertWithoutSubQuery($from, $columns, $values)
    {
        return "INSERT INTO {$from} ({$columns}) VALUES ({$values})";
    }

    /**
     * @param string $from
     * @param string $columns
     * @param string $sql
     * 
     * @return string
     */
    protected function compilerInsertWithSubQuery($from, $columns, $sql)
    {
        return "INSERT INTO {$from} ({$columns}) {$sql}";
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerDelete($query)
    {
        if ($query->getQuery('joins')) {
            return $this->compilerDeleteWithJoins($query);
        }

        return $this->compilerDeleteWithoutJoins($query);
    }

    /**
     * Compile an update statement without joins into SQL.
     *
     * @param  Builder  $query
     * 
     * @return string
     */
    protected function compilerDeleteWithoutJoins($query)
    {
        return "DELETE {$this->compilerFroms($query)} {$this->compilerWheres($query)}";
    }

    /**
     * Compile an update statement with joins into SQL.
     *
     * @param  Builder  $query
     * 
     * @return string
     */
    protected function compilerDeleteWithJoins($query)
    {
        return "DELETE {$this->compilerFroms($query)} {$this->compilerJoins($query)} {$this->compilerWheres($query)}";
    }

    /**
     * Compile an count statement with joins into SQL.
     *
     * @param  Builder  $query
     * 
     * @return string
     */
    public function compilerCount($query)
    {
        $sql = $this->compilerSelect($query);

        return "SELECT COUNT(*) AS `aggregate` FROM ({$sql}) AS `aggregate_table`";
    }

    /**
     * Determine if the grammar supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * Compile a CASE expression. Returns [sql, bindings].
     *
     * Simple CASE (column set):   CASE `col` WHEN ? THEN ? ... END
     * Searched CASE (no column):  CASE WHEN cond THEN ? ... END
     *
     * @param  \Wilkques\Database\Queries\CaseClause $clause
     * @return array
     */
    public function compileCase($clause)
    {
        $conditions = $clause->getQuery('conditions', array());

        if (empty($conditions)) {
            throw new \InvalidArgumentException('CaseClause requires at least one when() call.');
        }

        $sql       = 'CASE';
        $bindings  = array();
        $column    = $clause->getQuery('column');
        $hasElse   = $clause->getQuery('has_else', false);
        $elseValue = $clause->getQuery('else_value');
        $isSimple  = !is_null($column);

        if ($isSimple) {
            if ($column instanceof Expression) {
                $sql .= ' ' . (string) $column;
            } else {
                $sql .= ' ' . $this->contactBacktick($column);
            }
        }

        foreach ($conditions as $condition) {
            $when = $condition['when'];
            $then = $condition['then'];

            if ($when instanceof \Closure) {
                if ($isSimple) {
                    throw new \InvalidArgumentException(
                        'Simple CASE (with column) does not support Closure WHEN conditions. ' .
                        'Use caseWhen() without a column for Searched CASE with Closure conditions.'
                    );
                }
                $subQuery = $clause->forSubQuery();
                call_user_func($when, $subQuery);
                list($condSql, $condBindings) = $this->extractCaseCondition($subQuery);
                $sql      .= ' WHEN ' . $condSql;
                $bindings  = array_merge($bindings, $condBindings);

            } else if ($when instanceof \Wilkques\Database\Queries\IfClause) {
                list($condSql, $condBindings) = $this->compileIf($when);
                $sql      .= ' WHEN ' . $condSql;
                $bindings  = array_merge($bindings, $condBindings);

            } else if ($when instanceof \Wilkques\Database\Queries\CaseClause) {
                throw new \InvalidArgumentException(
                    'CaseClause is not supported as a WHEN condition. ' .
                    'Use an Expression or IfClause for complex WHEN conditions.'
                );

            } else if ($when instanceof Expression) {
                $sql .= ' WHEN ' . (string) $when;

            } else if ($isSimple) {
                $sql      .= ' WHEN ?';
                $bindings[] = $when;

            } else if (is_string($when) || is_int($when) || is_float($when) || is_bool($when)) {
                $sql .= ' WHEN ' . $when;

            } else {
                throw new \InvalidArgumentException(
                    'Unsupported WHEN condition type "' . gettype($when) . '". ' .
                    'Searched CASE WHEN accepts: string, int, float, bool, Expression, Closure, or IfClause.'
                );
            }

            $sql .= ' THEN';
            $sql  = $this->appendClauseValue($sql, $bindings, $then);
        }

        if ($hasElse) {
            $sql .= ' ELSE';
            $sql  = $this->appendClauseValue($sql, $bindings, $elseValue);
        }

        $sql .= ' END';

        return array($sql, $bindings);
    }

    /**
     * Compile an IF() expression. Returns [sql, bindings].
     *
     * NOTE: IF() is MySQL-specific. Override in dialect subclasses or use
     *       CASE WHEN cond THEN t ELSE f END as a portable equivalent.
     *
     * @param  \Wilkques\Database\Queries\IfClause $clause
     * @return array
     */
    public function compileIf($clause)
    {
        $sql        = 'IF(';
        $bindings   = array();
        $condition  = $clause->getQuery('condition');
        $trueValue  = $clause->getQuery('true_value');
        $falseValue = $clause->getQuery('false_value');

        if ($condition instanceof \Closure) {
            $subQuery = $clause->forSubQuery();
            call_user_func($condition, $subQuery);
            $froms = $subQuery->getQuery('froms.queries');
            if (!empty($froms)) {
                $sql .= 'EXISTS(' . $subQuery->toSql() . ')';
            } else {
                $whereClause = $this->compilerWheres($subQuery);
                $sql .= $whereClause
                    ? '(' . preg_replace('/^WHERE\s+/i', '', $whereClause) . ')'
                    : 'TRUE';
            }
            $bindings = array_merge($bindings, $subQuery->getBindings());

        } else if ($condition instanceof \Wilkques\Database\Queries\IfClause) {
            list($condSql, $condBindings) = $this->compileIf($condition);
            $sql     .= $condSql;
            $bindings = array_merge($bindings, $condBindings);

        } else if (is_string($condition) || is_int($condition) || is_float($condition) || is_bool($condition)) {
            $sql .= (string) $condition;

        } else {
            throw new \InvalidArgumentException(
                'IF condition must be a string, scalar, Closure, or IfClause. Got "' . gettype($condition) . '".'
            );
        }

        $sql .= ',';
        $sql  = $this->appendClauseValue($sql, $bindings, $trueValue);
        $sql .= ',';
        $sql  = $this->appendClauseValue($sql, $bindings, $falseValue);
        $sql .= ')';

        return array($sql, $bindings);
    }

    /**
     * Extract CASE WHEN condition from a sub-query builder.
     * With FROM  → EXISTS(SELECT ...) to match compileIf() semantics.
     * Without FROM → WHERE-only string wrapped in parentheses.
     *
     * @param  \Wilkques\Database\Queries\Builder $subQuery
     * @return array
     */
    protected function extractCaseCondition($subQuery)
    {
        $froms = $subQuery->getQuery('froms.queries');

        if (!empty($froms)) {
            return array(
                'EXISTS(' . $subQuery->toSql() . ')',
                $subQuery->getBindings()
            );
        }

        $whereClause = $this->compilerWheres($subQuery);

        if ($whereClause) {
            $condSql = '(' . preg_replace('/^WHERE\s+/i', '', $whereClause) . ')';

            return array($condSql, $subQuery->getBindings());
        }

        return array('TRUE', array());
    }

    /**
     * Append a THEN/ELSE value to the SQL string.
     * IfClause / CaseClause → recursive compile.
     * Expression            → embed raw.
     * Other                 → bind as ?.
     *
     * @param  string $sql
     * @param  array  $bindings  by reference
     * @param  mixed  $value
     * @return string
     */
    protected function appendClauseValue($sql, &$bindings, $value)
    {
        if ($value instanceof \Wilkques\Database\Queries\IfClause) {
            list($v, $b) = $this->compileIf($value);
            $sql      .= ' ' . $v;
            $bindings  = array_merge($bindings, $b);
        } else if ($value instanceof \Wilkques\Database\Queries\CaseClause) {
            list($v, $b) = $this->compileCase($value);
            $sql      .= ' ' . $v;
            $bindings  = array_merge($bindings, $b);
        } else if ($value instanceof Expression) {
            $sql .= ' ' . (string) $value;
        } else {
            $sql      .= ' ?';
            $bindings[] = $value;
        }

        return $sql;
    }
}
