<?php

namespace Wilkques\Database;

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
 * @method static static grammar(GrammarInterface $grammar) set sql server grammar
 * @method static static lockForUpdate() set for update lock
 * @method static static sharedLock() set shared lock
 * @method static static currentPage(int $currentPage) set now page
 * @method static static prePage(int $prePage) set prepage
 * @method static array toArray()
 * @method static string toJson()
 * @method static static first()
 * @method static static get()
 * @method static static update(array $data)
 * @method static static increment(string $column,int|string $value = 1, array $data = [])
 * @method static static decrement(string $column,int|string $value = 1, array $data = [])
 * @method static static insert(array $data)
 * @method static static delete()
 * @method static static softDelete()
 * @method static static reStore()
 */
class DB implements \JsonSerializable, \ArrayAccess
{
    /** @var array */
    protected static $queryLog = [];
    /** @var \Wilkques\Database\ConnectionInterface */
    protected $database;

    /**
     * @param \Wilkques\Database\Database $database
     */
    public function __construct(\Wilkques\Database\Database $database)
    {
        $this->setDatabase($database);
    }

    /**
     * @param \Wilkques\Database\Database $database
     * 
     * @return static
     */
    public function setDatabase(\Wilkques\Database\Database $database = null)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @return \Wilkques\Database\Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param \Wilkques\Database\Database $database
     * 
     * @return static
     */
    protected function bindQueryLog(\Wilkques\Database\Database $database)
    {
        self::$queryLog[] = array(
            'queryString'   => $database->getQuery(),
            'bindData'      => $database->compilerBindDataHandle()
        );

        return $this;
    }

    /**
     * @return array
     */
    public static function getQueryLog()
    {
        return self::$queryLog;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getDatabase()->toArray();
    }

    /**
     * @param string $offset
     * 
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getDatabase()->offsetExists($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->getDatabase()->offsetSet($offset, $value);
    }

    /**
     * @param string $offset
     * 
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getDatabase()->offsetGet($offset);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->getDatabase()->offsetUnset($offset);
    }

    /**
     * Get a data by key
     *
     * @param string The key data to retrieve
     * @access public
     */
    public function __get($key)
    {
        return $this->getDatabase()->__get($key);
    }

    /**
     * Assigns a value to the specified data
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     * @access public
     */
    public function __set($key, $value)
    {
        $this->getDatabase()->__set($key, $value);
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public function __call($method, $arguments)
    {
        $database = $this->getDatabase();

        if (!$database && in_array($method, ["database", "setDatabase"])) {
            return $this->setDatabase(...$arguments);
        }

        $returnDatabase = call_user_func_array(array($database, $method), $arguments);

        if (is_object($returnDatabase)) {
            $this->setDatabase($returnDatabase);

            if ($returnDatabase->getQuery()) {
                $this->bindQueryLog($returnDatabase);
            }

            return $this;
        }

        return $returnDatabase;
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
