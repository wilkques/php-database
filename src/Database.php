<?php

namespace Wilkques\Database;

use Wilkques\Database\Queries\Builder;

/**
 * php >= 5.4
 * 
 * 簡易資料庫操作
 * 
 * @see [wilkques](https://github.com/wilkques/Database)
 * 
 * create by: wilkques
 * 
 * @method static static connection(ConnectionInterface $connection) set Connection
 * @method static static table(string $table) set table name
 * @method static static username(string $username) set db user name
 * @method static static password(string $password) set db password
 * @method static static dbname(string $dbname) set db name
 * @method static static host(string $host) set db host
 * @method static static newConnect() new db connect
 * @method static static query(string $query) set sql query
 * @method static static bindData(array $data) set bind data
 * @method static static orderBy(string $column, string $sort = "ASC") set order by
 * @method static static groupBy(string $column) set group by
 * @method static static limit(int $limit) set limit
 * @method static static offset(int $offset) set offset
 * @method static static select(array|string $column) set column with select
 * @method static static where(array|string $key, $condition = null, $value = null) set where
 * @method static static orWhere(array|string $key, $condition = null, $value = null)
 * @method static static whereIn(string $column, array $data)
 * @method static static whereNull(string|array $column)
 * @method static static whereOrNull(string|array $column)
 * @method static static whereNotNull(string|array $column)
 * @method static static whereOrNotNull(string|array $column)
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static bool rollBack()
 * @method static static grammar(GrammarInterface $grammar) set sql grammar
 * @method static static lockForUpdate() set for update lock
 * @method static static sharedLock() set shared lock
 * @method static static currentPage(int $currentPage) set now page
 * @method static static prePage(int $prePage) set prepage
 * @method static array first()
 * @method static array get()
 * @method static int update(array $data)
 * @method static int increment(string $column,int|string $value = 1, array $data = [])
 * @method static int decrement(string $column,int|string $value = 1, array $data = [])
 * @method static int insert(array $data)
 * @method static int delete()
 * @method static int softDelete()
 * @method static int reStore()
 * @method static \Wilkques\Database\Queries\Expression raw(mixed $value, mixed $bindValue = null)
 * @method static static selectRaw(string $column = "*")
 * @method static static whereRaw(string $where, $value = null)
 */
class Database 
{
    /** @var Builder */
    protected $builder;

    /**
     * @param GrammarInterface $grammar
     */
    public function __construct(Builder $builder = null)
    {
        $this->setBuilder($builder);
    }

    /**
     * @param Builder $builder
     * 
     * @return static
     */
    public function setBuilder(Builder $builder = null)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        $methods = array(
            "builder"
        );

        if (in_array($method, $methods)) {
            $method = "set" . ucfirst($method);
        }

        return $method;
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

        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        }

        $builder = $this->getBuilder();

        $builder = call_user_func_array(array($builder, $method), $arguments);

        return $builder;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = new static;

        return call_user_func_array(array($instance, $method), $arguments);
    }
}
