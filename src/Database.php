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
 * @author wilkques
 * 
 * @method static static connection(ConnectionInterface $connection) set Connection
 * @method static static table(string $table) set table name
 * @method static static username(string $username) set db user name
 * @method static static password(string $password) set db password
 * @method static static dbname(string $dbname) set db name
 * @method static static host(string $host) set db host
 * @method static static newConnect(string $dns = null) new db connect
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
 * @method static array first() first result
 * @method static array get() all result
 * @method static int update(array $data)
 * @method static int increment(string $column, int|string $value = 1, array $data = [])
 * @method static int decrement(string $column, int|string $value = 1, array $data = [])
 * @method static int insert(array $data)
 * @method static int delete()
 * @method static int softDelete(string $column = 'deleted_at', string $dateTimeFormat = "Y-m-d H:i:s")
 * @method static int reStore($column = 'deleted_at')
 * @method static \Wilkques\Database\Queries\Expression raw(mixed $value, mixed $bindValue = null)
 * @method static static selectRaw(string $column = "*")
 * @method static static whereRaw(string $where, $value = null)
 * @method static static fromSub(\Closure|\Illuminate\Database\Query\Builder|string $query, string $as)
 * @method static static enableQueryLog()
 * @method static array getQueryLog()
 * @method static array getParseQueryLog() parser query log
 * @method static array parseQueryLog() parser query log
 * @method static string getLastParseQuery() parser query
 * @method static string lastParseQuery() parser query
 */
class Database 
{
    /** @var Builder */
    protected $query;

    /**
     * @param Builder $query
     */
    public function __construct(Builder $query = null)
    {
        $this->setBuilder($query);
    }

    /**
     * @param Builder $query
     * 
     * @return static
     */
    public function setBuilder(Builder $query = null)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->query;
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

        $query = $this->getBuilder();

        $builder = call_user_func_array(array($query, $method), $arguments);

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
