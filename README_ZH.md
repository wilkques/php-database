# Database

[![Latest Stable Version](https://poser.pugx.org/wilkques/database/v/stable)](https://packagist.org/packages/wilkques/database)
[![License](https://poser.pugx.org/wilkques/database/license)](https://packagist.org/packages/wilkques/database)

[English](README.md) | 繁體中文

## 注意事項

1. 僅支援 `MySQL`
1. 資料庫操作

## 環境需求

1. php >= 5.3
1. mysql >= 5.6
1. PDO 擴充套件

## 如何使用

1. 透過 PHP require  
    [下載 Database](https://github.com/wilkques/Database)  
    [下載 EzLoader 並查看使用方式](https://github.com/wilkques/EzLoader)
    ```php

    require_once "path/to/your/folder/wilkques/Ezloader/src/helpers.php";
    require_once "path/to/your/folder/wilkques/Database/src/helpers.php";

    loadPHP();
    ```

1. 透過 Composer
    `composer require wilkques/database`

    ```php

    require "vendor/autoload.php";
    ```

1. 開始
    ```php
    $connection = \Wilkques\Database\Database::connect('<DB driver>', '<host>', '<username>', '<password>', '<database>', '<port>', '<character>');

    // 或

    $connection = \Wilkques\Database\Database::connect([
        'driver'    => '<DB driver>',   // mysql
        'host'      => '<host>',        // 預設 localhost
        'username'  => '<username>',
        'password'  => '<password>',
        'database'  => '<database>',
        'port'      => '<port>',        // 預設 3360
        'charset'   => '<character>',   // 預設 utf8mb4
    ]);
    ```

## 方法

### table 或 from

1. `table` 或 `from` 或 `fromSub`
    `table` 與 `from` 相同

    ```php

    $db->table('<table name>');

    // 或

    $db->table('<table name>', '<as name>');

    // 或

    $db->table(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<as name>'
    );

    // 輸出: select ... from (select ... from <table name>) AS `<as name>`

    // 相同

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->fromSub(
        $dbTable, 
        '<as name>'
    );

    // 輸出: select ... from (select ... from <table name>) AS `<as name>`

    // 相同

    $db->fromSub(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<as name>'
    );

    // 輸出: select ... from (select ... from <table name>) AS `<as name>`

    // 或

    $db->table([
        function ($query) {
            $query->table('<table name1>');
        },
        function ($query) {
            $query->table('<table name2>');
        },
    ]);

    // 輸出: select ... from (select ... from <table name1>), (select ... from <table name2>)

    // 或

    $db->table([
        '<as name1>' => function ($query) {
            $query->table('<table name1>');
        },
        '<as name2>' => function ($query) {
            $query->table('<table name2>');
        },
    ]);

    // 輸出: select ... from (select ... from <table name1>) AS `<as name1>`, (select ... from <table name2>) AS `<as name2>`
    ```

### select

1. `select` 或 `selectSub`

    ```php

    $db->select(
        '<columnName1>', 
        '<columnName2>', 
        '<columnName3>',
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 輸出: select <columnName1>, <columnName2>, <columnName3>, (select ...)

    // 或

    $db->select([
        '<as name1>' => '<columnName1>',
        '<as name2>' => '<columnName1>',
    ]);

    // 輸出: select <columnName1> AS `<as name1>`, <columnName2> AS `<as name2>`

    // 或

    $db->select([
        '<columnName1>', 
        '<columnName2>', 
        '<columnName3>',
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        },
        '<as name>' => function ($query) {
            $query->table('<table name>');
            // 做些什麼
        },
    ]);

    // 輸出: select <columnName1>, <columnName2>, <columnName3>, (select ...), (select ...) AS `<as name>`

    // 或

    $db->select("`<columnName1>`, `<columnName2>`, `<columnName3>`");

    // 或

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        },
        '<as name>'
    );

    // 輸出: select (select ...) AS `<as name>`
    ```

1. `selectSub`

    ```php

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 輸出: select (select ...)

    // 或

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        },
        '<as name>'
    );

    // 輸出: select (select ...) AS `<as name>`
    ```

### join

1. `join`

    ```php

    $db->from('<table name1>')->join(
        '<table name2>',
        '<table name1>.<column1>', 
        '<table name2>.<column1>'
    );

    // 輸出: select ... join <table name> ON <table name1>.<column1> = <table name2>.<column1>

    // 或

    $db->from('<table name1>')->join(
        '<table name2>',
        function ($join) {
            $join->on('<table name1>.<column1>', '<table name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<table name2>.<column2>');

            // 做些什麼
        }
    );

    // 輸出: select ... join <table name> ON <table name1>.<column1> = <table name2>.<column1> OR <table name1>.<column2> = <table name2>.<column2>
    ```

1. `joinWhere`

    ```php

    $db->from('<table name1>')->joinWhere(
        '<table name2>',
        '<table name1>.<column1>', 
        '<table name2>.<column1>'
    );

    // 輸出: select ... join <table name> WHERE <table name1>.<column1> = <table name2>.<column1>

    // 或

    $db->from('<table name1>')->joinWhere(
        '<table name2>',
        function ($join) {
            $join->on('<table name1>.<column1>', '<table name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<table name2>.<column2>');

            // 做些什麼
        }
    );

    // 輸出: select ... join <table name> WHERE <table name1>.<column1> = <table name2>.<column1> OR <table name1>.<column2> = <table name2>.<column2>
    ```

1. `joinSub`

    ```php

    $db->from('<table name1>')->joinSub(
        function ($query) {
            $query->table('<table name2>');

            // 做些什麼
        },
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // 輸出: select ... join (select ...) as `<as name2>` ON <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->from('<table name1>')->joinSub(
        $dbTable,
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // 輸出: select ... join (select ...) as `<as name2>` ON <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>
    ```

1. `joinSubWhere`

    ```php

    $db->from('<table name1>')->joinSubWhere(
        function ($builder) {
            $builder->table('<table name2>');

            // 做些什麼
        },
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // 輸出: select ... join (select ...) as `<as name2>` WHERE <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->from('<table name1>')->joinSubWhere(
        $dbTable,
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // 輸出: select ... join (select ...) as `<as name2>` WHERE <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>
    ```

1. `leftJoin`

    與 `join` 相同

1. `leftJoinSub`

    與 `joinSub` 相同

1. `leftJoinWhere`

    與 `join` 相同

1. `leftJoinSubWhere`

    與 `joinSub` 相同

1. `rightJoin`

    與 `join` 相同

1. `rightJoinSub`

    與 `joinSub` 相同

1. `rightJoinWhere`

    與 `join` 相同

1. `rightJoinSubWhere`

    與 `joinSub` 相同

1. `crossJoin`

    與 `join` 相同

1. `crossJoinSub`

    與 `joinSub` 相同

1. `crossJoinWhere`

    與 `join` 相同

1. `crossJoinSubWhere`

    與 `joinSub` 相同

### where

1. `where`

    ```php

    $db->where([
        ['<columnName1>'],
        ['<columnName2>'],
        ['<columnName3>'],
    ]);

    // 輸出: select ... where (<columnName1> IS NULL AND <columnName2> IS NULL AND <columnName3> IS NULL)

    // 或

    $db->where('<columnName1>');

    // 輸出: select ... where (<columnName1> IS NULL)

    // 或

    $db->where([
        ['<columnName1>', '<value1>'],
        ['<columnName2>', '<value2>'],
        ['<columnName3>', '<value3>'],
    ]);

    // 或

    $db->where([
        ['<columnName1>', '<operator1>', '<value1>'],
        ['<columnName2>', '<operator2>', '<value2>'],
        ['<columnName3>', '<operator3>', '<value3>'],
    ]);

    // 或

    $db->where('<columnName1>', "<operator>", '<columnValue1>');

    // 或

    $db->where('<columnName1>', '<value1>')
        ->where('<columnName2>', '<value2>')
        ->where('<columnName3>', '<value3>');

    // 或

    $db->where('<columnName1>', "<operator>", '<value1>')
        ->where('<columnName2>', "<operator>", '<value2>')
        ->where('<columnName3>', "<operator>", '<value3>');

    // 或

    $db->where(function ($query) {
        $query->where('<columnName1>', '<value1>')->where('<columnName2>', '<value2>');
    });

    // 輸出: select ... where (<columnName1> = <value1> AND <columnName2> = <value2>)

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->where($dbTable);

    // 相同

    $db->whereExists($dbTable);

    // 輸出: select ... where EXISTS (select ...)

    // 或

    $db->where('<columnName>', $dbTable);

    // 輸出: select ... where '<columnName>' = (select ...)

    // 或

    $db->where('<columnName>', "<operator>", $dbTable);

    // 輸出: select ... where '<columnName>' <operator> (select ...)

    // 或

    $db->where('<columnName>', "<operator>", function ($query) {
        $query->table('<table name>')->where('<columnName1>', '<value1>')->where('<columnName2>', '<value2>');
    });

    // 輸出: select ... where '<columnName>' <operator> (select ...)
    ```

1. `orWhere`

    與 `where` 相同

1. `whereNull`

    ```php

    $db->whereNull('<columnName1>');
    ```

1. `orWhereNull`

    與 `whereNull` 相同

1. `whereNotNull`

    與 `whereNull` 相同

1. `orWhereNotNull`

    與 `whereNotNull` 相同

1. `whereIn`

    ```php

    $db->whereIn('<columnName1>', ['<columnValue1>', '<columnValue2>']);

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->whereIn('<columnName1>', $dbTable);

    // 或

    $db->whereIn('<columnName1>', function ($query) {
        $query->select('<columnName2>')->table('<table name1>');
    });
    ```

1. `orWhereIn`

    與 `whereIn` 相同

1. `whereNotIn`

    與 `whereIn` 相同

1. `orWhereNotIn`

    與 `whereIn` 相同

1. `whereBetween`

    ```php

    $db->whereBetween('<columnName1>', ['<columnValue1>', '<columnValue2>']);
    ```

1. `orWhereBetween`

    與 `whereBetween` 相同

1. `whereNotBetween`

    與 `whereBetween` 相同

1. `orWhereNotBetween`

    與 `whereBetween` 相同

1. `whereExists`

    ```php

    $db->whereExists(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
    });

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->whereExists($dbTable);

    // 相同

    $db->where($dbTable);
    ```

1. `whereNotExists`

    與 `whereExists` 相同

1. `orWhereExists`

    與 `whereExists` 相同

1. `orWhereNotExists`

    與 `whereExists` 相同

1. `whereLike`

    ```php

    $db->whereLike('<columnName1>', '<columnValue2>');
    ```

1. `orWhereLike`

    ```php

    $db->orWhereLike('<columnName1>', '<columnValue2>');
    ```

### having

1. `having`

    ```php

    $db->having('<columnName1>', '<columnValue1>');

    // 或

    $db->having('<columnName1>', "<operator>", '<columnValue1>');

    // 或

    $db->having(
        '<columnName1>',
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 或

    $db->having(
        '<columnName1>',
        "<operator>",
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->having('<columnName1>', $dbTable);

    // 或 

    $db->having('<columnName1>', "<operator>", $dbTable);
    ```

1. `orHaving`

    ```php

    $db->orHaving('<columnName1>', '<columnValue1>');

    // 或

    $db->orHaving('<columnName1>', "<operator>", '<columnValue1>');

    // 或

    $db->orHaving(
        '<columnName1>',
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 或

    $db->orHaving(
        '<columnName1>',
        "<operator>",
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }
    );

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->orHaving('<columnName1>', $dbTable);

    // 或 

    $db->orHaving('<columnName1>', "<operator>", $dbTable);
    ```

### limit 或 offset

1. `limit`

    ```php

    $db->limit(1); // 設定查詢的 LIMIT

    // 或

    $db->limit(10, 1); // 設定查詢的 LIMIT
    ```

1. `offset`

    ```php

    $db->offset(1); // 設定查詢的 OFFSET
    ```

### group by

1. `groupBy`

    ```php

    $db->groupBy('<columnName1>', 'DESC'); // 預設 ASC

    // 或

    $db->groupBy([
        ['<columnName1>', 'DESC'],
        ['<columnName2>', 'ASC'],
    ]);

    // 或

    $db->groupBy([
        [
            function ($query) {
                $query->table('<table name>');
                // 做些什麼
            }, 
            'DESC'
        ],
        ['<columnName2>', 'ASC'],
    ]);

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupBy($dbTable, 'DESC'); // 預設 ASC

    // 或

    $db->groupBy([
        [
            $dbTable, 
            'DESC'
        ],
        ['<columnName2>', 'ASC'],
    ]);
    ```

1. `groupByDesc`

    ```php

    $db->groupByDesc('<columnName1>');

    // 或

    $db->groupByDesc('<columnName1>', '<columnName2>');

    // 或

    $db->groupByDesc(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    );

    // 或

    $db->groupByDesc(['<columnName1>', '<columnName2>']);

    // 或

    $db->groupByDesc([
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    ]);

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupByDesc($dbTable, '<columnName1>'); // 預設 ASC

    // 或

    $db->groupByDesc([
        $dbTable,
        '<columnName1>'
    ]);
    ```

1. `groupByAsc`

    ```php

    $db->groupByAsc('<columnName1>');

    // 或

    $db->groupByAsc('<columnName1>', '<columnName2>');

    // 或

    $db->groupByAsc(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    );

    // 或

    $db->groupByAsc(['<columnName1>', '<columnName2>']);

    // 或

    $db->groupByAsc([
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    ]);

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupByAsc($dbTable, '<columnName1>'); // 預設 ASC

    // 或

    $db->groupByAsc([
        $dbTable,
        '<columnName1>'
    ]);
    ```

### order by

1. `orderBy`

    ```php

    $db->orderBy('<columnName1>', "DESC"); // 預設 ASC

    // 或

    $db->orderBy([
        ['<columnName1>', 'DESC'],
        ['<columnName2>', 'ASC'],
    ]);

    // 或

    $db->orderBy([
        [
            function ($query) {
                $query->table('<table name>');
                // 做些什麼
            }, 
            'DESC'
        ],
        ['<columnName2>', 'ASC'],
    ]);
    ```

1. `orderByDesc`

    ```php

    $db->orderByDesc('<columnName1>');

    // 或

    $db->orderByDesc('<columnName1>', '<columnName2>');

    // 或

    $db->orderByDesc(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    );

    // 或

    $db->orderByDesc(['<columnName1>', '<columnName2>']);

    // 或

    $db->orderByDesc([
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    ]);
    ```

1. `orderByAsc`

    ```php

    $db->orderByAsc('<columnName1>');

    // 或

    $db->orderByAsc('<columnName1>', '<columnName2>');

    // 或

    $db->orderByAsc(
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    );

    // 或

    $db->orderByAsc(['<columnName1>', '<columnName2>']);

    // 或

    $db->orderByAsc([
        function ($query) {
            $query->table('<table name>');
            // 做些什麼
        }, 
        '<columnName2>'
    ]);
    ```

### union

1. `union`

    ```php

    $db->union(function ($query) {
        $query->table('<table name>');
        // 做些什麼
    });

    // 或

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->union($dbTable);

    ```

1. `unionAll`

    與 `union` 相同

### 取得資料

1. `get`

    ```php

    $db->get(); // 取得所有資料
    ```

1. `first`

    ```php

    $db->first(); // 取得第一筆資料
    ```

1. `find`

    ```php

    $db->find('<id>'); // 取得指定資料
    ```

### 更新

1. `update`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->update([
            '<updateColumnName1>' => '<updateColumnValue1>'
        ]);

    // 或

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->update([
        '<updateColumnName1>' => '<updateColumnValue1>'
    ]);

    // 或

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->update([
        '<updateColumnName1>' => function ($query) {
            $query->table('<table name>')->select('<column name>');

            // 做些什麼
        }
    ]);
    ```

1. `increment`

    ```php

    $db->increment('<columnName>');

    // 或

    $db->increment('<columnName>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

1. `decrement`

    ```php

    $db->decrement('<columnName>');

    // 或

    $db->decrement('<columnName>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

### 新增

1. `insert`

    ```php

    $db->insert([
            '<ColumnName1>' => 'ColumnValue1>',
            '<ColumnName2>' => 'ColumnValue2>',
            ...
        ]);

    // 或

    $db->insert([
        [
            '<ColumnName1>' => 'ColumnValue1>',
            '<ColumnName2>' => 'ColumnValue2>',
            ...
        ],
        [
            '<ColumnName3>' => 'ColumnValue3>',
            '<ColumnName4>' => 'ColumnValue4>',
            ...
        ]
    ]);
    ```

1. `insertSub`

    ```php

    $db->insertSub([
        '<ColumnName1>',
        '<ColumnName2>',
        ...
    ], function ($query) {
        $query->from('<Sub table name>')->select(
            '<Sub ColumnName1>',
            '<Sub ColumnName2>',
            ...
        )->where('<Sub columnName3>', '<Sub value1>')->where('<Sub columnName4>', '<Sub value2>');
    });

    // 輸出: Insert <table> (<ColumnName1>, <ColumnName2>) SELECT <Sub ColumnName1>, <Sub ColumnName2> FROM <Sub table name>
    // WHERE <Sub columnName3> = <Sub value1> AND <Sub columnName4> = <Sub value2>
    ```

### 刪除

1. `delete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->delete([
            '<deleteColumnName1>' => '<deleteColumnValue1>'
        ]);

    // 或

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->delete();
    ```

1. `softDelete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->softDelete('<deleteColumnName1>', '<date time format>'); // 預設 deleted_at, "Y-m-d H:i:s"

    // 或

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->softDelete('<deleteColumnName1>', '<date time format>'); // 預設 deleted_at, "Y-m-d H:i:s"
    ```

1. `reStore` 復原（`delete` 無法復原資料）

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->reStore('<deleteColumnName1>'); // 預設 deleted_at

    // 或

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->reStore('<deleteColumnName1>'); // 預設 deleted_at
    ```

### Raw（原生 SQL）

1. `raw`
    ```php

    // select

    $db->select($db->raw("<sql string in select column>"));
    
    // 範例

    $db->select($db->raw("COUNT(*)"));

    // update

    $db->update([
        $db->raw("<sql string in select column>"),
    ]);
    ```

### CASE WHEN

1. `caseWhen` - 簡單 CASE（帶欄位）

    ```php

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value1>', '<result1>')
        ->when('<value2>', '<result2>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // 輸出: select CASE `<columnName>` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - 搜尋式 CASE（不帶欄位，字串條件）

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when('<condition expression>', '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // 範例

    $db->from('users')
        ->caseWhen()
        ->when('age > 18', 'Adult')
        ->otherwise('Minor')
        ->end('age_group');

    // 輸出: select CASE WHEN age > 18 THEN ? ELSE ? END AS `age_group` from `users`
    ```

1. `caseWhen` - 搜尋式 CASE（Closure WHERE 條件）

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when(function ($query) {
            $query->where('<columnName>', '<operator>', '<value>');
        }, '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // 輸出: select CASE WHEN (<columnName> <operator> ?) THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - 搜尋式 CASE（Closure 搭配 `from()` → EXISTS 子查詢）

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when(function ($query) {
            $query->from('<sub table name>')->select('<columnName>');
        }, '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // 輸出: select CASE WHEN EXISTS(SELECT `<columnName>` FROM `<sub table name>`) THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - 巢狀 CASE 作為 THEN 值

    ```php

    $innerCase = $db->caseWhen('<columnName1>')
        ->when('<value1>', '<inner result1>')
        ->otherwise('<inner default>');

    $db->from('<table name>')
        ->caseWhen('<columnName2>')
        ->when('<value2>', $innerCase)
        ->otherwise('<outer default>')
        ->end('<alias>');

    // 輸出: select CASE `<columnName2>` WHEN ? THEN CASE `<columnName1>` WHEN ? THEN ? ELSE ? END ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - 使用 `Expression` 作為 THEN / ELSE 值（嵌入原生 SQL，不做綁定）

    ```php

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value1>', new \Wilkques\Database\Queries\Expression('<raw SQL>'))
        ->otherwise(new \Wilkques\Database\Queries\Expression('NULL'))
        ->end('<alias>');

    // 範例

    $db->from('orders')
        ->caseWhen('status')
        ->when('shipped', new \Wilkques\Database\Queries\Expression('NOW()'))
        ->otherwise(new \Wilkques\Database\Queries\Expression('NULL'))
        ->end('shipped_at');

    // 輸出: select CASE `status` WHEN ? THEN NOW() ELSE NULL END AS `shipped_at` from `orders`
    ```

1. `caseWhen` - 使用 `Expression` 作為簡單 CASE 的欄位（原生 SQL，不加反引號包裹）

    ```php

    $db->from('<table name>')
        ->caseWhen(new \Wilkques\Database\Queries\Expression('<raw column expression>'))
        ->when('<value1>', '<result1>')
        ->otherwise('<default>')
        ->end('<alias>');

    // 範例

    $db->from('orders')
        ->caseWhen(new \Wilkques\Database\Queries\Expression('YEAR(created_at)'))
        ->when(2024, 'This Year')
        ->otherwise('Other')
        ->end('year_label');

    // 輸出: select CASE YEAR(created_at) WHEN ? THEN ? ELSE ? END AS `year_label` from `orders`
    ```

1. `end` - 編譯並加入 SELECT，回傳 `CompiledClause`（代理回父層 Builder）

    ```php

    // 帶別名 — 回傳 CompiledClause，透過代理繼續串接
    $db->from('<table name>')->caseWhen('<columnName>')->when(...)->end('<alias>')->select('name')->get();

    // 不帶別名
    $db->from('<table name>')->caseWhen('<columnName>')->when(...)->end();
    ```

    > `end()` 現在回傳 `CompiledClause` 物件，而不是父層 `Builder`。  
    > `CompiledClause` 上的所有方法呼叫都會透明地代理給父層 `Builder`，  
    > 所以既有的鏈式呼叫不受影響，可以照常使用。

### IF 表達式

> ⚠️ `IF()` 是 MySQL 專屬語法。若使用其他資料庫請改用 `caseWhen()`。

1. `ifExpr` - 簡單純量條件

    ```php

    $db->from('<table name>')
        ->ifExpr('<condition expression>')
        ->then('<true result>')
        ->otherwise('<false result>')
        ->end('<alias>');

    // 範例

    $db->from('users')
        ->ifExpr('age >= 18')
        ->then('Adult')
        ->otherwise('Minor')
        ->end('age_group');

    // 輸出: select IF(age >= 18, ?, ?) AS `age_group` from `users`
    ```

1. `ifExpr` - 巢狀 IF（將 IfClause 實例作為值傳入）

    ```php

    $inner = $db->ifExpr('<condition1>')->then('<result1>')->otherwise('<result2>');

    $db->from('<table name>')
        ->ifExpr('<condition2>')
        ->then('<result3>')
        ->otherwise($inner)
        ->end('<alias>');

    // 輸出: select IF(<condition2>, ?, IF(<condition1>, ?, ?)) AS `<alias>` from `<table name>`
    ```

1. `ifExpr` - Closure 搭配 `from()` → EXISTS 子查詢

    ```php

    $db->from('<table name>')
        ->ifExpr(function ($query) {
            $query->from('<sub table name>')->select('<columnName>')->where('<columnName2>', '<value>');
        })
        ->then('<true result>')
        ->otherwise('<false result>')
        ->end('<alias>');

    // 輸出: select IF(EXISTS(SELECT `<columnName>` FROM `<sub table name>` WHERE `<columnName2>` = ?), ?, ?) AS `<alias>` from `<table name>`
    ```

1. `ifExpr` 作為 CASE WHEN THEN 值

    ```php

    $ifExpr = $db->ifExpr('<condition>')->then('<result1>')->otherwise('<result2>');

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value>', $ifExpr)
        ->otherwise('<default>')
        ->end('<alias>');

    // 輸出: select CASE `<columnName>` WHEN ? THEN IF(<condition>, ?, ?) ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` 作為 `ifExpr` 的 THEN / ELSE 值

    ```php

    $caseExpr = $db->caseWhen('<columnName>')
        ->when('<value1>', '<result1>')
        ->otherwise('<default>');

    $db->from('<table name>')
        ->ifExpr('<condition>')
        ->then($caseExpr)
        ->otherwise('<fallback>')
        ->end('<alias>');

    // 輸出: select IF(<condition>, CASE `<columnName>` WHEN ? THEN ? ELSE ? END, ?) AS `<alias>` from `<table name>`
    ```

### 在 `select()` 與 `update()` 陣列形式中使用 CASE WHEN / IF

> 可以直接把 `CaseClause` / `IfClause` / `CompiledClause` 放進 `select([...])` 或 `update([...])` 的陣列裡。

#### `select([...])` — 三種形式

1. **直接形式**（建議用來保證順序）：別名透過陣列鍵指定，不需要呼叫 `end()`

    ```php

    $db->from('users')->select([
        'name',
        'status_label' => $db->caseWhen('status')
            ->when('active', 'Active User')
            ->otherwise('Unknown'),
        'age_group' => $db->ifExpr('age >= 18')
            ->then('Adult')
            ->otherwise('Minor'),
    ])->get();

    // 輸出: SELECT `name`,
    //         CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label`,
    //         IF(age >= 18, ?, ?) AS `age_group`
    //         FROM `users`
    ```

1. **`end()` 形式**：別名由 `end()` 提供，陣列鍵會被忽略

    ```php

    $db->from('users')->select([
        $db->caseWhen('status')
            ->when('active', 'Active User')
            ->otherwise('Unknown')
            ->end('status_label'),
    ])->get();

    // 輸出: SELECT CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label` FROM `users`
    ```

    > ⚠️ **欄位順序**：`end()` 在被 PHP 求值時會觸發 `selectRaw` 的副作用，  
    > 所以 `end()` 形式的欄位一定會出現在 SELECT 清單中**其他欄位之前**，  
    > 不論它在陣列裡的位置為何。若要保證順序請改用直接形式。

1. **Closure 形式**：Closure 必須 `return` 該子句；沒有 return 就會退回純量子查詢模式

    ```php

    // ✅ 有 return — CASE 表達式直接被插入
    $db->from('users')->select([
        function ($q) {
            return $q->caseWhen('status')
                ->when('active', 'Active User')
                ->otherwise('Unknown')
                ->end('status_label');
        },
    ])->get();

    // 輸出: SELECT CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label` FROM `users`

    // ⚠️ 沒有 return — 退回子查詢（把該 SELECT 包成純量子查詢）
    $db->from('users')->select([
        function ($q) {
            $q->caseWhen('status')->when('active', 'Active User')->end('status_label');
            // 沒有 return → $q 的 SELECT 裡有 CASE，但會變成 (SELECT CASE ...)
        },
    ])->get();

    // 輸出: SELECT (SELECT CASE `status` WHEN ? THEN ? END AS `status_label`) FROM `users`
    ```

#### `update([...])` — 四種形式

1. **不帶 `end()` 的直接形式**（建議）：欄位名稱來自陣列鍵，沒有別名副作用

    ```php

    $db->from('users')->where('id', 1)->update([
        'status_label' => $db->caseWhen('status')
            ->when('active', 'Active User')
            ->otherwise('Unknown'),
        'age_group' => $db->ifExpr('age >= 18')
            ->then('Adult')
            ->otherwise('Minor'),
    ]);

    // 輸出: UPDATE `users`
    //         SET `status_label` = CASE `status` WHEN ? THEN ? ELSE ? END,
    //             `age_group` = IF(age >= 18, ?, ?)
    //         WHERE `id` = ?
    ```

1. **帶 `end()` 的直接形式**：`end()` 的別名在 UPDATE 中會被忽略；欄位名稱來自陣列鍵

    ```php

    $db->from('users')->where('id', 1)->update([
        'status' => $db->caseWhen('status')
            ->when('active', 'Active User')
            ->when('inactive', 'Inactive User')
            ->otherwise('Unknown')
            ->end('status_label'),  // 'status_label' 會被忽略；欄位是陣列鍵的 'status'
        'status2' => $db->ifExpr('age >= 18')
            ->then('Adult')
            ->otherwise('Minor')
            ->end('age_group'),     // 'age_group' 會被忽略；欄位是陣列鍵的 'status2'
    ]);

    // 輸出: UPDATE `users`
    //         SET `status` = CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END,
    //             `status2` = IF(age >= 18, ?, ?)
    //         WHERE `id` = ?
    ```

    > ⚠️ `end()` 仍然會在父層 builder 上觸發 `selectRaw` 的副作用。  
    > 若想要乾淨、沒有副作用的 UPDATE，建議使用不帶 `end()` 的形式。

1. **帶 `return` 的 Closure 形式**：Closure 必須 `return` 該子句

    ```php

    $db->from('users')->where('id', 1)->update([
        'status_label' => function ($q) {
            return $q->caseWhen('status')
                ->when('active', 'Active User')
                ->otherwise('Unknown');  // 直接 return CaseClause（不呼叫 end()）
        },
    ]);
    ```

1. **不帶 `return` 的 Closure 形式**：Closure 內呼叫了 `end()` 但沒有 return → 退回純量子查詢

    ```php

    $db->from('users')->where('id', 1)->update([
        'status2' => function ($q) {
            $q->caseWhen('status')
                ->when('active', 'Active User')
                ->when('inactive', 'Inactive User')
                ->otherwise('Unknown')
                ->end('status_label2');  // 沒有 return — 會變成純量子查詢
        },
    ]);

    // 輸出: UPDATE `users`
    //         SET `status2` = (SELECT CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `status_label2`)
    //         WHERE `id` = ?
    ```

    > ⚠️ 沒有 `return` 的話，Closure 會退回子查詢模式 — CASE 會變成純量子查詢，  
    > 而不是直接的 SET 表達式。請使用 `return` 形式或直接形式以避免這個狀況。

1. **混合形式**（與上面 REPORT 範例相同 — 可行，但要留意上述注意事項）

    ```php

    $db->from('users')->where('id', 1)->update([
        'status' => $db->caseWhen('status')        // end() 形式：別名被忽略，欄位來自陣列鍵
            ->when('active', 'Active User')
            ->when('inactive', 'Inactive User')
            ->otherwise('Unknown')
            ->end('status_label'),
        'status2' => function ($q) {               // 不帶 return 的 Closure：純量子查詢
            $q->caseWhen('status')
                ->when('active', 'Active User')
                ->when('inactive', 'Inactive User')
                ->otherwise('Unknown')
                ->end('status_label2');
        },
    ]);

    // 輸出: UPDATE `users`
    //         SET `status`  = CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END,
    //             `status2` = (SELECT CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `status_label2`)
    //         WHERE `id` = ?
    ```

#### 備註

- 在 `update()` 中，**陣列鍵一律決定欄位名稱**。`end()` 的別名絕不會用在 SET 子句中。
- `compileSql()` 永遠回傳**不含**別名的 SQL — 對 `UPDATE SET` 表達式來說是安全的。
- 沒有 `return` 的 Closure → 純量子查詢（把該 SELECT 包起來）；有 `return` 的 Closure → 直接表達式。
- `CaseClause` 與 `IfClause` 都實作了 `CompilableClause` 介面，所以未來任何實作了 `compileSql()` 的表達式型別，都能自動在 `select()` / `update()` 中使用。

### SQL 執行

1. `query` 設定 SQL 字串

    ```php

    $db->query("<SQL String>")->fetch();

    // 舉例來說

    $db->query("SELECT * FROM `<your table name>`")->fetch();
    ```

1. `prepare` 執行 SQL 字串

    ```php

    $db->prepare("<SQL String>")->execute(['<value1>', '<value2>' ...])->fetch();
    ```

1. `bindParams` 執行 SQL 字串

    ```php

    $stat = $db->prepare("<SQL String>");

    // execute() 回傳的是一個新的 Result——fetch() 要呼叫在這個回傳值上,
    // 不是 $stat 本身(Statement 本身沒有 fetch() 方法)。
    $result = $stat->bindParams(['<value1>', '<value2>' ...])->execute();

    $result->fetch();
    ```

1. `execute` 執行 SQL 字串

### SQL 執行結果

1. `fetchNumeric` 取得結果，鍵為數字索引

1. `fetchAssociative` 取得結果，鍵為欄位名稱

1. `fetchFirstColumn` 取得結果的第一個欄位

1. `fetchAllNumeric` 取得所有結果，鍵為數字索引

1. `fetchAllAssociative` 取得所有結果，鍵為欄位名稱

1. `fetchAllFirstColumn` 取得所有結果的第一個欄位

1. `rowCount` 取得結果筆數

1. `free` PDO 方法 `closeCursor` [PHP PDOStatement::closeCursor](https://www.php.net/manual/en/pdostatement.closecursor.php)

1. `fetch` [PDOStatement::fetch](https://www.php.net/manual/en/pdostatement.fetch.php)

1. `fetchAll` [PDOStatement::fetchAll](https://www.php.net/manual/en/pdostatement.fetchall.php)

### 查詢紀錄 (Query Log)

1. `enableQueryLog` 啟用查詢紀錄
    ```php

    $db->enableQueryLog();
    ```

1. `getQueryLog` 取得所有查詢字串與綁定資料

    ```php

    $db->getQueryLog();
    ```

1. `getParseQueryLog` 或 `parseQueryLog` 取得已解析的查詢紀錄
    ```php

    $db->getParseQueryLog();
    ```

1. `getLastParseQuery` 或 `lastParseQuery` 取得最後一次解析的查詢
    ```php

    $db->getLastParseQuery();
    ```

### 鎖定 (Lock)

1. `lockForUpdate`

    ```php
    
    $db->lockForUpdate();
    ```

1. `sharedLock`

    ```php
    
    $db->sharedLock();
    ```

### 分頁

1. `currentPage`

    ```php

    $db->currentPage(1); // 目前頁數
    ```

1. `prePage`

    ```php

    $db->prePage(15); // 每頁筆數
    ```

1. `getForPage`

    ```php

    $db->getForPage(); // 取得分頁資料

    // 或

    $db->getForPage('<prePage>', '<currentPage>'); // 取得分頁資料
    ```

### 交易

1. `beginTransaction`

    ```php
    
    $db->beginTransaction();
    ```

1. `commit`

    ```php
    
    $db->commit();
    ```

1. `rollback`

    ```php
    
    $db->rollback();
    ```

### 連線

1. `setHost` / `getHost`

    ```php

    $db->setHost('<DB host>');

    $db->getHost();
    ```

1. `setUsername` / `getUsername`

    ```php

    $db->setUsername('<DB username>');

    $db->getUsername();
    ```

1. `setPassword` / `getPassword`

    ```php

    $db->setPassword('<DB password>');

    $db->getPassword();
    ```

1. `setDatabase` / `getDatabase`

    ```php

    $db->setDatabase('<DB name>');

    $db->getDatabase();
    ```

1. `newConnection`

    ```php

    $db->newConnection();

    // 或

    $db->newConnection("<sql server dns string>");
    ```

1. `reConnection`

    ```php

    $db->reConnection();

    // 或

    $db->reConnection("<sql server dns string>");
    ```

1. `selectDatabase`

    ```php

    $db->selectDatabase('<database>');
    ```
