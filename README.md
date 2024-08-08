# Database

[![Latest Stable Version](https://poser.pugx.org/wilkques/database/v/stable)](https://packagist.org/packages/wilkques/database)
[![License](https://poser.pugx.org/wilkques/database/license)](https://packagist.org/packages/wilkques/database)

## Notice

1. `MySQL` Only
1. Database operate

## ENV

1. php >= 5.3
1. mysql >= 5.6
1. PDO extension

## How to use

1. Via PHP require  
    [Download Database](https://github.com/wilkques/Database)  
    [Download EzLoader and See how to use](https://github.com/wilkques/EzLoader)
    ```php

    require_once "path/to/your/folder/wilkques/Ezloader/src/helpers.php";
    require_once "path/to/your/folder/wilkques/Database/src/helpers.php";

    loadPHP();
    ```

1. Via Composer
    `composer require wilkques/database`

    ```php

    require "vendor/autoload.php";
    ```

1. connect
    ```php
    $connection = new \Wilkques\Database\Connections\PDO\Drivers\MySql('<host>', '<username>', '<password>', '<database>', '<port>', '<character>');

    // or

    $connection = \Wilkques\Database\Connections\PDO\Drivers\MySql::connect('<host>', '<username>', '<password>', '<database>', '<port>', '<character>');

    // or

    $connection = (new \Wilkques\Database\Connections\Connectors\PDO\Connections)->connection([
        'driver'    => '<DB driver>',   // mysql
        'host'      => '<host>',        // default localhost
        'username'  => '<username>',
        'password'  => '<password>',
        'database'  => '<database>',
        'port'      => '<port>',        // default 3360
        'charset'   => '<character>',   // default utf8mb4
    ]);

    // or

    $connection = \Wilkques\Database\Connections\Connectors\PDO\Connections::connect([
        'driver'    => '<DB driver>',   // mysql
        'host'      => '<host>',        // default localhost
        'username'  => '<username>',
        'password'  => '<password>',
        'database'  => '<database>',
        'port'      => '<port>',        // default 3360
        'charset'   => '<character>',   // default utf8mb4
    ]);
    ```

1. Using
    ```php
    $db = new \Wilkques\Database\Queries\Builder(
        $connection,
        new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
        new \Wilkques\Database\Queries\Processors\Processor,
    );

    // or 
    
    $db = \Wilkques\Database\Queries\Builder::make(
        $connection,
        new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
        new \Wilkques\Database\Queries\Processors\Processor,
    );
    ```

1. connect other connection or database
    ```php
    $connection1 = \Wilkques\Database\Connections\Connectors\PDO\Connections::connect([
        'driver'    => '<DB driver>',   // mysql
        'host'      => '<host>',        // default localhost
        'username'  => '<username>',
        'password'  => '<password>',
        'database'  => '<database>',
        'port'      => '<port>',        // default 3360
        'charset'   => '<character>',   // default utf8mb4
    ]);

    $db->table('<table name>')->where(function ($query) use ($connection1) {
        $query->setConnection(
            $connection1
        )->table('<table name1>');

        // do something ...
    });

    // or

    $db1 = \Wilkques\Database\Queries\Builder::make(
        $connection1,
        new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
        new \Wilkques\Database\Queries\Processors\Processor,
    );

    $db->table('<table name>')->where($db1->table('<table name1>'));
    ```

## Methods

### table or from

1. `table` or `from` or `fromSub`
    `table` same `from`

    ```php

    $db->table('<table name>');

    // or

    $db->table('<table name>', '<as name>');

    // or

    $db->table(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<as name>'
    );

    // output: select ... from (select ... from <table name>) AS `<as name>`

    // same

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

    // output: select ... from (select ... from <table name>) AS `<as name>`

    // same

    $db->fromSub(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<as name>'
    );

    // output: select ... from (select ... from <table name>) AS `<as name>`

    // or

    $db->table([
        function ($query) {
            $query->table('<table name1>');
        },
        function ($query) {
            $query->table('<table name2>');
        },
    ]);

    // output: select ... from (select ... from <table name1>), (select ... from <table name2>)

    // or

    $db->table([
        '<as name1>' => function ($query) {
            $query->table('<table name1>');
        },
        '<as name2>' => function ($query) {
            $query->table('<table name2>');
        },
    ]);

    // output: select ... from (select ... from <table name1>) AS `<as name1>`, (select ... from <table name2>) AS `<as name2>`
    ```

### select

1. `select` or `selectSub`

    ```php

    $db->select(
        '<columnName1>', 
        '<columnName2>', 
        '<columnName3>',
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // output: select <columnName1>, <columnName2>, <columnName3>, (select ...)

    // or

    $db->select([
        '<as name1>' => '<columnName1>',
        '<as name2>' => '<columnName1>',
    ]);

    // output: select <columnName1> AS `<as name1>`, <columnName2> AS `<as name2>`

    // or

    $db->select([
        '<columnName1>', 
        '<columnName2>', 
        '<columnName3>',
        function ($query) {
            $query->table('<table name>');
            // do something
        },
        '<as name>' => function ($query) {
            $query->table('<table name>');
            // do something
        },
    ]);

    // output: select <columnName1>, <columnName2>, <columnName3>, (select ...), (select ...) AS `<as name>`

    // or

    $db->select("`<columnName1>`, `<columnName2>`, `<columnName3>`");

    // or

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // do something
        },
        '<as name>'
    );

    // output: select (select ...) AS `<as name>`
    ```

1. `selectSub`

    ```php

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // output: select (select ...)

    // or

    $db->selectSub(
        function ($query) {
            $query->table('<table name>');
            // do something
        },
        '<as name>'
    );

    // output: select (select ...) AS `<as name>`
    ```

### join

1. `join`

    ```php

    $db->from('<table name1>')->join(
        '<table name2>',
        '<table name1>.<column1>', 
        '<table name2>.<column1>'
    );

    // output: select ... join <table name> ON <table name1>.<column1> = <table name2>.<column1>

    // or

    $db->from('<table name1>')->join(
        '<table name2>',
        function ($join) {
            $join->on('<table name1>.<column1>', '<table name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<table name2>.<column2>');

            // do something
        }
    );

    // output: select ... join <table name> ON <table name1>.<column1> = <table name2>.<column1> OR <table name1>.<column2> = <table name2>.<column2>
    ```

1. `joinWhere`

    ```php

    $db->from('<table name1>')->joinWhere(
        '<table name2>',
        '<table name1>.<column1>', 
        '<table name2>.<column1>'
    );

    // output: select ... join <table name> WHERE <table name1>.<column1> = <table name2>.<column1>

    // or

    $db->from('<table name1>')->joinWhere(
        '<table name2>',
        function ($join) {
            $join->on('<table name1>.<column1>', '<table name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<table name2>.<column2>');

            // do something
        }
    );

    // output: select ... join <table name> WHERE <table name1>.<column1> = <table name2>.<column1> OR <table name1>.<column2> = <table name2>.<column2>
    ```

1. `joinSub`

    ```php

    $db->from('<table name1>')->joinSub(
        function ($query) {
            $query->table('<table name2>');

            // do something
        },
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // output: select ... join (select ...) as `<as name2>` ON <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>

    // or

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

    // output: select ... join (select ...) as `<as name2>` ON <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>
    ```

1. `joinSubWhere`

    ```php

    $db->from('<table name1>')->joinSubWhere(
        function ($builder) {
            $builder->table('<table name2>');

            // do something
        },
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // output: select ... join (select ...) as `<as name2>` WHERE <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>

    // or

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

    // output: select ... join (select ...) as `<as name2>` WHERE <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>
    ```

1. `leftJoin`

    same `join`

1. `leftJoinSub`

    same `joinSub`

1. `leftJoinWhere`

    same `join`

1. `leftJoinSubWhere`

    same `joinSub`

1. `rightJoin`

    same `join`

1. `rightJoinSub`

    same `joinSub`

1. `rightJoinWhere`

    same `join`

1. `rightJoinSubWhere`

    same `joinSub`

1. `crossJoin`

    same `join`

1. `crossJoinSub`

    same `joinSub`

1. `crossJoinWhere`

    same `join`

1. `crossJoinSubWhere`

    same `joinSub`

### where

1. `where`

    ```php

    $db->where([
        ['<columnName1>'],
        ['<columnName2>'],
        ['<columnName3>'],
    ]);

    // output: select ... where (<columnName1> IS NULL AND <columnName2> IS NULL AND <columnName3> IS NULL)

    // or

    $db->where('<columnName1>');

    // output: select ... where (<columnName1> IS NULL)

    // or

    $db->where([
        ['<columnName1>', '<value1>'],
        ['<columnName2>', '<value2>'],
        ['<columnName3>', '<value3>'],
    ]);

    // or

    $db->where([
        ['<columnName1>', '<operator1>', '<value1>'],
        ['<columnName2>', '<operator2>', '<value2>'],
        ['<columnName3>', '<operator3>', '<value3>'],
    ]);

    // or

    $db->where('<columnName1>', "<operator>", '<columnValue1>');

    // or

    $db->where('<columnName1>', '<value1>')
        ->where('<columnName2>', '<value2>')
        ->where('<columnName3>', '<value3>');

    // or

    $db->where('<columnName1>', "<operator>", '<value1>')
        ->where('<columnName2>', "<operator>", '<value2>')
        ->where('<columnName3>', "<operator>", '<value3>');

    // or

    $db->where(function ($query) {
        $query->where('<columnName1>', '<value1>')->where('<columnName2>', '<value2>');
    });

    // output: select ... where (<columnName1> = <value1> AND <columnName2> = <value2>)

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->where($dbTable);

    // same

    $db->whereExists($dbTable);

    // output: select ... where EXISTS (select ...)

    // or

    $db->where('<columnName>', $dbTable);

    // output: select ... where '<columnName>' = (select ...)

    // or

    $db->where('<columnName>', "<operator>", $dbTable);

    // output: select ... where '<columnName>' <operator> (select ...)

    // or

    $db->where('<columnName>', "<operator>", function ($query) {
        $query->table('<table name>')->where('<columnName1>', '<value1>')->where('<columnName2>', '<value2>');
    });

    // output: select ... where '<columnName>' <operator> (select ...)
    ```

1. `orWhere`

    same `where`

1. `whereNull`

    ```php

    $db->whereNull('<columnName1>');
    ```

1. `orWhereNull`

    same `whereNull`

1. `whereNotNull`

    same `whereNull`

1. `orWhereNotNull`

    same `whereNotNull`

1. `whereIn`

    ```php

    $db->whereIn('<columnName1>', ['<columnValue1>', '<columnValue2>']);

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->whereIn('<columnName1>', $dbTable);

    // or

    $db->whereIn('<columnName1>', function ($query) {
        $query->select('<columnName2>')->table('<table name1>');
    });
    ```

1. `orWhereIn`

    same `whereIn`

1. `whereNotIn`

    same `whereIn`

1. `orWhereNotIn`

    same `whereIn`

1. `whereBetween`

    ```php

    $db->whereBetween('<columnName1>', ['<columnValue1>', '<columnValue2>']);
    ```

1. `orWhereBetween`

    same `whereBetween`

1. `whereNotBetween`

    same `whereBetween`

1. `orWhereNotBetween`

    same `whereBetween`

1. `whereExists`

    ```php

    $db->whereExists(
        function ($query) {
            $query->table('<table name>');
            // do something
    });

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->whereExists($dbTable);

    // same

    $db->where($dbTable);
    ```

1. `whereNotExists`

    same `whereExists`

1. `orWhereExists`

    same `whereExists`

1. `orWhereNotExists`

    same `whereExists`

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

    $db->having(`<columnName1>`, `<columnValue1>`);

    // or

    $db->having(`<columnName1>`, "<operator>", `<columnValue1>`);

    // or

    $db->having(
        `<columnName1>`,
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $db->having(
        `<columnName1>`,
        "<operator>",
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->having(`<columnName1>`, $dbTable);

    // or 

    $db->having(`<columnName1>`, "<operator>", $dbTable);
    ```

1. `orHaving`

    ```php

    $db->orHaving(`<columnName1>`, `<columnValue1>`);

    // or

    $db->orHaving(`<columnName1>`, "<operator>", `<columnValue1>`);

    // or

    $db->orHaving(
        `<columnName1>`,
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $db->orHaving(
        `<columnName1>`,
        "<operator>",
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->orHaving(`<columnName1>`, $dbTable);

    // or 

    $db->orHaving(`<columnName1>`, "<operator>", $dbTable);
    ```

### limit or offset

1. `limit`

    ```php

    $db->limit(1); // set query LIMIT

    // or

    $db->limit(10, 1); // set query LIMIT
    ```

1. `offset`

    ```php

    $db->offset(1); // set query OFFSET
    ```

### group by

1. `groupBy`

    ```php

    $db->groupBy('<columnName1>', 'DESC'); // default ASC

    // or

    $db->groupBy([
        ['<columnName1>', 'DESC'],
        ['<columnName2>', 'ASC'],
    ]);

    // or

    $db->groupBy([
        [
            function ($query) {
                $query->table('<table name>');
                // do something
            }, 
            'DESC'
        ],
        ['<columnName2>', 'ASC'],
    ]);

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupBy($dbTable, 'DESC'); // default ASC

    // or

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

    // or

    $db->groupByDesc('<columnName1>', '<columnName2>');

    // or

    $db->groupByDesc(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    );

    // or

    $db->groupByDesc(['<columnName1>', '<columnName2>']);

    // or

    $db->groupByDesc([
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    ]);

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupByDesc($dbTable, '<columnName1>'); // default ASC

    // or

    $db->groupByDesc([
        $dbTable,
        '<columnName1>'
    ]);
    ```

1. `groupByAsc`

    ```php

    $db->groupByAsc('<columnName1>');

    // or

    $db->groupByAsc('<columnName1>', '<columnName2>');

    // or

    $db->groupByAsc(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    );

    // or

    $db->groupByAsc(['<columnName1>', '<columnName2>']);

    // or

    $db->groupByAsc([
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    ]);

    // or

    $dbTable = (
        new \Wilkques\Database\Queries\Builder(
            $connection,
            new \Wilkques\Database\Queries\Grammar\Drivers\MySql,
            new \Wilkques\Database\Queries\Processors\Processor,
        )
    )->table('<table name1>');

    $db->groupByAsc($dbTable, '<columnName1>'); // default ASC

    // or

    $db->groupByAsc([
        $dbTable,
        '<columnName1>'
    ]);
    ```

### order by

1. `orderBy`

    ```php

    $db->orderBy('<columnName1>', "DESC"); // default ASC

    // or

    $db->orderBy([
        ['<columnName1>', 'DESC'],
        ['<columnName2>', 'ASC'],
    ]);

    // or

    $db->orderBy([
        [
            function ($query) {
                $query->table('<table name>');
                // do something
            }, 
            'DESC'
        ],
        ['<columnName2>', 'ASC'],
    ]);
    ```

1. `orderByDesc`

    ```php

    $db->orderByDesc('<columnName1>');

    // or

    $db->orderByDesc('<columnName1>', '<columnName2>');

    // or

    $db->orderByDesc(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    );

    // or

    $db->orderByDesc(['<columnName1>', '<columnName2>']);

    // or

    $db->orderByDesc([
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    ]);
    ```

1. `orderByAsc`

    ```php

    $db->orderByAsc('<columnName1>');

    // or

    $db->orderByAsc('<columnName1>', '<columnName2>');

    // or

    $db->orderByAsc(
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    );

    // or

    $db->orderByAsc(['<columnName1>', '<columnName2>']);

    // or

    $db->orderByAsc([
        function ($query) {
            $query->table('<table name>');
            // do something
        }, 
        '<columnName2>'
    ]);
    ```

### union

1. `union`

    ```php

    $db->union(function ($query) {
        $query->table('<table name>');
        // do something
    });

    // or

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
    sam `union`

### Get Data

1. `get`

    ```php

    $db->get(); // get all data
    ```

1. `first`

    ```php

    $db->first(); // get first data
    ```

1. `find`

    ```php

    $db->find('<id>'); // get find data
    ```

### Update

1. `update`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->update([
            '<updateColumnName1>' => '<updateColumnValue1>'
        ]);

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->update([
        '<updateColumnName1>' => '<updateColumnValue1>'
    ]);

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->update([
        '<updateColumnName1>' => function ($query) {
            $query->table('<table name>')->select('<column name>');

            // do something
        }
    ]);
    ```

1. `increment`

    ```php

    $db->increment('<columnName>');

    // or

    $db->increment('<columnName>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

1. `decrement`

    ```php

    $db->decrement('<columnName>');

    // or

    $db->decrement('<columnName>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

### Insert

1. `insert`

    ```php

    $db->insert([
            '<ColumnName1>' => 'ColumnValue1>',
            '<ColumnName2>' => 'ColumnValue2>',
            ...
        ]);

    // or

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
        '<ColumnName1>'
        '<ColumnName2>'
        ...
    ], function ($query) {
        $query->from('<Sub table name>')->select(
            '<Sub ColumnName1>',
            '<Sub ColumnName2>',
            ...
        )->where('<Sub columnName3>', '<Sub value1>')->where('<Sub columnName4>', '<Sub value2>');
    });

    // output: Insert <table> (<ColumnName1>, <ColumnName2>) SELECT <Sub ColumnName1>, <Sub ColumnName2> FROM <Sub table name>
    // WHERE <Sub columnName3> = <Sub value1> AND <Sub columnName4> = <Sub value2>
    ```

### Delete

1. `delete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->delete([
            '<deleteColumnName1>' => '<deleteColumnValue1>'
        ]);

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->delete();
    ```

1. `softDelete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->softDelete('<deleteColumnName1>', '<date time format>'); // default deleted_at, "Y-m-d H:i:s"

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->softDelete('<deleteColumnName1>', '<date time format>'); // default deleted_at, "Y-m-d H:i:s"
    ```

1. `reStore` recovery (`delete` cannot recovery data)

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->reStore('<deleteColumnName1>'); // default deleted_at

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->reStore('<deleteColumnName1>'); // default deleted_at
    ```

### Raw

1. `raw`
    ```php

    // select

    $db->select($db->raw("<sql string in select column>"));
    
    // example

    $db->select($db->raw("COUNT(*)"));

    // update

    $db->update([
        $db->raw("<sql string in select column>"),
    ]);
    ```

### SQL Execute

1. `query` set SQL string

    ```php

    $db->query("<SQL String>")->fetch();

    // for example

    $db->query("SELECT * FROM `<your table name>`")->fetch();
    ```

1. `prepare` execute SQL string

    ```php

    $db->prepare("<SQL String>")->execute(['<value1>', '<value2>' ...])->fetch();
    ```

1. `bindParams` execute SQL string

    ```php

    $stat = $db->prepare("<SQL String>");
    
    $stat->bindParams(['<value1>', '<value2>' ...])->execute();
    
    $stat->fetch();
    ```

1. `execute` execute SQL string

### SQL Execute result

1. `fetchNumeric` get result key to numeric

1. `fetchAssociative` get result key value

1. `fetchFirstColumn` get result first column

1. `fetchAllNumeric` get all result key to numeric

1. `fetchAllAssociative` get all result key value

1. `fetchAllFirstColumn` get all result first column

1. `rowCount` get result

1. `free` PDO method `closeCursor` [PHP PDOStatement::closeCursor](https://www.php.net/manual/en/pdostatement.closecursor.php)

1. `fetch` [PDOStatement::fetch](https://www.php.net/manual/en/pdostatement.fetch.php)

1. `fetchAll` [PDOStatement::fetchAll](https://www.php.net/manual/en/pdostatement.fetchall.php)

### Query Log

1. `enableQueryLog` enable query logs
    ```php

    $db->enableQueryLog();
    ```

1. `getQueryLog` get all query string and bind data

    ```php

    $db->getQueryLog();
    ```

1. `getParseQueryLog` or `parseQueryLog` get paser query logs
    ```php

    $db->getParseQueryLog();
    ```

1. `getLastParseQuery` or `lastParseQuery` get paser query
    ```php

    $db->getLastParseQuery();
    ```

### Lock

1. `lockForUpdate`

    ```php
    
    $db->lockForUpdate();
    ```

1. `sharedLock`

    ```php
    
    $db->sharedLock();
    ```

### Page

1. `currentPage`

    ```php

    $db->currentPage(1); // now page
    ```

1. `prePage`

    ```php

    $db->prePage(15); // pre page
    ```

1. `getForPage`

    ```php

    $db->getForPage(); // get page data

    // or

    $db->getForPage('<prePage>', '<currentPage>'); // get page data
    ```

### Transaction

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

### Connect

1. `host`

    ```php

    $db->host('<DB host>');
    ```

1. `username`

    ```php

    $db->username('<DB username>');
    ```

1. `password`

    ```php

    $db->password('<DB password>');
    ```

1. `database`

    ```php

    $db->database('<DB name>');
    ```

1. `newConnection`

    ```php

    $db->newConnection();

    // or

    $db->newConnection("<sql server dns string>");
    ```

1. `reConnection`

    ```php

    $db->reConnection();

    // or

    $db->reConnection("<sql server dns string>");
    ```

1. `selectDatabase`

    ```php

    $db->selectDatabase('<database>');
    ```