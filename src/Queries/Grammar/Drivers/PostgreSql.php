<?php

namespace Wilkques\Database\Queries\Grammar\Drivers;

use Wilkques\Database\Queries\Expression;
use Wilkques\Database\Queries\Grammar\Grammar;
use Wilkques\Database\Queries\Grammar\GrammarInterface;
use Wilkques\Helpers\Arrays;

class PostgreSql extends Grammar implements GrammarInterface
{
    /**
     * @return string
     */
    public function lockForUpdate()
    {
        return "FOR UPDATE";
    }

    /**
     * @return string
     */
    public function sharedLock()
    {
        return "FOR SHARE";
    }

    /**
     * PostgreSQL quotes identifiers with double quotes, not backticks.
     *
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
            // "table AS alias"：比照 Laravel 的 wrap()，只有明確的 as 關鍵字才視為別名，
            // 不能拿任意空白當 "." 的替代分隔符，否則像 "loginlog lg1" 這種
            // "表名 別名"（沒有 as）會被誤判成兩層 schema.table -> "loginlog"."lg1"
            if (preg_match('/^(.*?)\s+as\s+(.*)$/i', $value, $matches)) {
                return $this->contactBacktick(trim($matches[1]))
                    . ' AS '
                    . $this->contactBacktick(trim($matches[2]));
            }

            $value = explode('.', $value);
        }

        $value = Arrays::map($value, function ($value) {
            $value = trim(trim($value), '"');

            return "\"{$value}\"";
        });

        return join(".", $value);
    }

    /**
     * @param \Wilkques\Database\Queries\Builder $query
     *
     * @return string
     */
    public function compilerCount($query)
    {
        $sql = $this->compilerSelect($query);

        return "SELECT COUNT(*) AS \"aggregate\" FROM ({$sql}) AS \"aggregate_table\"";
    }

    /**
     * PostgreSQL has no `LIMIT offset, count` comma syntax, translate to
     * `LIMIT count OFFSET offset`.
     *
     * @param \Wilkques\Database\Queries\Builder $query
     *
     * @return string|false
     */
    public function compilerLimits($query)
    {
        $limit = $query->getQuery('limits.queries', array());

        if (empty($limit)) {
            return false;
        }

        $limit = $this->arrayNested($limit);

        if (count($limit) > 1) {
            return "LIMIT {$limit[0]} OFFSET {$limit[1]}";
        }

        return "LIMIT {$limit[0]}";
    }
}
