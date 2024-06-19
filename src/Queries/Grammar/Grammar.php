<?php

namespace Wilkques\Database\Queries\Grammar;

use Wilkques\Database\Queries\Builder;
use Wilkques\Database\Queries\Expression;

abstract class Grammar implements GrammarInterface
{
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = array(
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    );

    /**
     * @param array $array
     * @param bool|true $force
     * @param callback|\Closure|null $callback
     * 
     * @return array
     */
    protected function arrayNested($array, $force = true, $callback = null)
    {
        return array_map(function ($value) use ($callback, $force) {
            if ($value instanceof Expression) {
                return $value;
            }

            if ($callback) {
                return call_user_func($callback, $value);
            }

            return $force ? $value : '?';
        }, $array);
    }

    /**
     * @param Builder $query
     * 
     * @return string
     */
    public function compilerColumns($query)
    {
        $columns = $query->getQuery('columns.queries', array('*'));

        return join(',', $this->arrayNested($columns));
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
    public function compilerFrom($query)
    {
        $from = $query->getQuery('from.queries', array());

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

        return "WHERE " . ltrim(ltrim($wheres, 'AND '), 'OR ');
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

        return "HAVING " . ltrim(ltrim($havings, 'AND '), 'OR ');
    }

    /**
     * @param Builder $query
     * 
     * @return string|false
     */
    public function compilerLimit($query)
    {
        $limit = $query->getQuery('limit.queries', array());

        if (empty($limit)) {
            return false;
        }

        return "LIMIT " . join(',', $this->arrayNested($limit));
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

        return $lock;
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

                $sql[$component] = call_user_func_array([$this, $method], [$query]);
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
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * @param Builder $query
     * @param array $columns
     * 
     * @return string
     */
    public function compilerUpdate($query, $columns)
    {
        $columns = $this->arrayNested($columns, false, function ($column) {
            return "`{$column}` = ?";
        });

        $columns = join(',', $columns);

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
        if (empty($data)) {
            return "INSERT INTO {$query->getFrom()} DEFAULT VALUES";
        }

        if (!is_array(current($data))) {
            $data = array(
                $data
            );
        }

        $columns = join(',', array_keys(current($data)));

        $from = join(', ', $query->getFrom());

        if (!$sql) {
            $values = array_map(function ($values) {
                return join(', ', $this->arrayNested($values, false));
            }, $data);

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
            return $this->compilerDeleteWithoutJoins($query);
        }

        return $this->compilerDeleteWithJoins($query);
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
        return "DELETE FROM {$query->getFrom()} {$this->compilerWheres($query)}";
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
        return "DELETE FROM {$query->getFrom()} {$this->compilerJoins($query)} {$this->compilerWheres($query)}";
    }

    /**
     * @return string
     */
    abstract function lockForUpdate();

    /**
     * @return string
     */
    abstract function sharedLock();
}
