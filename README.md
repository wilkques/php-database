# Database

[![Latest Stable Version](https://poser.pugx.org/wilkques/database/v/stable)](https://packagist.org/packages/wilkques/database)
[![License](https://poser.pugx.org/wilkques/database/license)](https://packagist.org/packages/wilkques/database)

English | [繁體中文](README_ZH.md)

## Notice

1. `MySQL`, `PostgreSQL` Support
1. Database operate

## ENV

1. php >= 5.3
1. mysql >= 5.6 or postgresql >= 9.4
1. PDO extension (`pdo_mysql` and/or `pdo_pgsql`)

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

1. start
    ```php
    $connection = \Wilkques\Database\Database::connect('<DB driver>', '<host>', '<username>', '<password>', '<database>', '<port>', '<character>');

    // or

    $connection = \Wilkques\Database\Database::connect([
        'driver'    => '<DB driver>',   // mysql, pgsql
        'host'      => '<host>',        // default localhost
        'username'  => '<username>',
        'password'  => '<password>',
        'database'  => '<database>',
        'port'      => '<port>',        // default 3306 (mysql) / 5432 (pgsql)
        'charset'   => '<character>',   // default utf8mb4 (mysql) / UTF8 (pgsql)
    ]);
    ```

### PostgreSQL notes

1. Identifiers (tables, columns) are automatically quoted with double quotes (`"..."`) instead of MySQL's backticks — no code changes needed on your end.
1. `selectDatabase()` reconnects with the new database in the connection string, since PostgreSQL has no `USE <db>` statement.
1. `IF()` / `ifExpr()` remains MySQL-only (see the [IF Expression](#if-expression) section) — use `caseWhen()` instead on PostgreSQL.
1. `UPDATE` / `DELETE` with `JOIN` is not translated to PostgreSQL's `UPDATE ... FROM` / `DELETE ... USING` syntax; avoid join-style updates/deletes on PostgreSQL.
1. `insertGetId()` / `getLastInsertId()` should always be called with an explicit sequence name on PostgreSQL (e.g. `<table>_<column>_seq`). Without one, the result depends on the installed `pdo_pgsql` version — older versions return `false`, newer ones may resolve the session's last-used sequence — so don't rely on the no-argument form.

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

    // example

    $db->table('users');

    // output: SELECT * FROM `users`

    $db->table('users', 'u');

    // output: SELECT * FROM `users` AS `u`

    $db->table(function ($query) {
        $query->table('users');
    }, 'u');

    // output: SELECT * FROM (SELECT * FROM `users`) AS `u`

    $db->table([
        function ($query) { $query->table('users'); },
        function ($query) { $query->table('posts'); },
    ]);

    // output: SELECT * FROM (SELECT * FROM `users`), (SELECT * FROM `posts`)

    $db->table([
        'u' => function ($query) { $query->table('users'); },
        'p' => function ($query) { $query->table('posts'); },
    ]);

    // output: SELECT * FROM (SELECT * FROM `users`) AS `u`, (SELECT * FROM `posts`) AS `p`
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

    // example

    $db->table('orders')->select('id', 'status', function ($query) {
        $query->table('users');
    });

    // output: SELECT `id`, `status`, (SELECT * FROM `users`) FROM `orders`

    $db->table('orders')->select([
        'order_id'     => 'id',
        'order_status' => 'status',
    ]);

    // output: SELECT `id` AS `order_id`, `status` AS `order_status` FROM `orders`

    $db->table('orders')->select([
        'id',
        'status',
        function ($query) { $query->table('users'); },
        'user_count' => function ($query) { $query->table('users'); },
    ]);

    // output: SELECT `id`, `status`, (SELECT * FROM `users`), (SELECT * FROM `users`) AS `user_count` FROM `orders`
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

    // example

    $db->selectSub(function ($query) {
        $query->table('users');
    });

    // output: SELECT (SELECT * FROM `users`)

    $db->selectSub(function ($query) {
        $query->table('users');
    }, 'user_count');

    // output: SELECT (SELECT * FROM `users`) AS `user_count`
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

    // example

    $db->from('orders')->join('users', 'orders.user_id', 'users.id');

    // output: SELECT * FROM `orders` INNER JOIN `users` ON `orders`.`user_id` = `users`.`id`

    $db->from('orders')->join('users', function ($join) {
        $join->on('orders.user_id', 'users.id')
            ->orOn('orders.backup_user_id', 'users.id');
    });

    // output: SELECT * FROM `orders` INNER JOIN `users` ON `orders`.`user_id` = `users`.`id` OR `orders`.`backup_user_id` = `users`.`id`
    ```

    `join` has no separate `$as` parameter (unlike `table`/`from`), so to alias a joined table, put `as <alias>` directly in the `$table` string. Only a literal ` as ` keyword (case-insensitive) is treated as an alias — plain whitespace is not, so it can't be confused with a schema-qualified `schema.table` reference.

    ```php

    $db->from('orders')->join('order_items as oi', 'orders.id', 'oi.order_id');

    // output: SELECT * FROM `orders` INNER JOIN `order_items` AS `oi` ON `orders`.`id` = `oi`.`order_id`
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

    // example

    $db->from('orders')->joinWhere('users', 'orders.user_id', 'users.id');

    // output: SELECT * FROM `orders` INNER JOIN `users` WHERE `orders`.`user_id` = `users`.`id`

    $db->from('orders')->joinWhere('users', function ($join) {
        $join->on('orders.user_id', 'users.id')
            ->orOn('orders.backup_user_id', 'users.id');
    });

    // output: SELECT * FROM `orders` INNER JOIN `users` WHERE `orders`.`user_id` = `users`.`id` OR `orders`.`backup_user_id` = `users`.`id`
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

    // example

    $db->from('orders')->joinSub(function ($query) {
        $query->table('users');
    }, 'u', function ($join) {
        $join->on('orders.user_id', 'u.id')
            ->orOn('orders.backup_user_id', 'u.id');
    });

    // output: SELECT * FROM `orders` INNER JOIN (SELECT * FROM `users`) AS `u` ON `orders`.`user_id` = `u`.`id` OR `orders`.`backup_user_id` = `u`.`id`
    ```

1. `joinWhereSub`

    ```php

    $db->from('<table name1>')->joinWhereSub(
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

    $db->from('<table name1>')->joinWhereSub(
        $dbTable,
        '<as name2>',
        function (\Wilkques\Database\Queries\JoinClause $join) {
            $join->on('<table name1>.<column1>', '<as name2>.<column1>')
            ->orOn('<table name1>.<column2>', '<as name2>.<column2>');
        }
    );

    // output: select ... join (select ...) as `<as name2>` WHERE <table name1>.<column1> = <as name2>.<column1> OR <table name1>.<column2> = <as name2>.<column2>

    // example

    $db->from('orders')->joinWhereSub(function ($query) {
        $query->table('users');
    }, 'u', function ($join) {
        $join->on('orders.user_id', 'u.id')
            ->orOn('orders.backup_user_id', 'u.id');
    });

    // output: SELECT * FROM `orders` INNER JOIN (SELECT * FROM `users`) AS `u` WHERE `orders`.`user_id` = `u`.`id` OR `orders`.`backup_user_id` = `u`.`id`
    ```

1. `leftJoin`

    same `join`

1. `leftJoinSub`

    same `joinSub`

1. `leftJoinWhere`

    same `join`

1. `leftJoinWhereSub`

    same `joinSub`

1. `rightJoin`

    same `join`

1. `rightJoinSub`

    same `joinSub`

1. `rightJoinWhere`

    same `join`

1. `rightJoinWhereSub`

    same `joinSub`

1. `crossJoin`

    same `join`

1. `crossJoinSub`

    same `joinSub`

1. `crossJoinWhere`

    same `join`

1. `crossJoinWhereSub`

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

    // example

    $db->table('orders')->where([
        ['status'],
        ['type'],
    ]);

    // output: SELECT * FROM `orders` WHERE (`status` IS NULL AND `type` IS NULL)

    $db->table('orders')->where('status');

    // output: SELECT * FROM `orders` WHERE `status` IS NULL

    $db->table('orders')->where([
        ['status', 'shipped'],
        ['type', 'online'],
    ]);

    // output: SELECT * FROM `orders` WHERE (`status` = ? AND `type` = ?)

    $db->table('orders')->where([
        ['status', '!=', 'cancelled'],
        ['amount', '>', 100],
    ]);

    // output: SELECT * FROM `orders` WHERE (`status` != ? AND `amount` > ?)

    $db->table('orders')->where('status', '=', 'shipped');

    // output: SELECT * FROM `orders` WHERE `status` = ?

    $db->table('orders')->where('status', 'shipped')->where('type', 'online');

    // output: SELECT * FROM `orders` WHERE `status` = ? AND `type` = ?

    $db->table('orders')->where('status', '!=', 'cancelled')->where('amount', '>', 100);

    // output: SELECT * FROM `orders` WHERE `status` != ? AND `amount` > ?

    $db->table('orders')->where(function ($query) {
        $query->where('status', 'shipped')->where('type', 'online');
    });

    // output: SELECT * FROM `orders` WHERE (`status` = ? AND `type` = ?)

    $dbTable = $connection->newQuery()->table('users');

    $db->table('orders')->where($dbTable);

    // output: SELECT * FROM `orders` WHERE EXISTS (SELECT * FROM `users`)

    $db->table('orders')->where('user_id', $connection->newQuery()->select('id')->table('users'));

    // output: SELECT * FROM `orders` WHERE `user_id` = (SELECT `id` FROM `users`)

    $db->table('orders')->where('user_id', 'in', $connection->newQuery()->select('id')->table('users'));

    // output: SELECT * FROM `orders` WHERE `user_id` IN (SELECT `id` FROM `users`)

    $db->table('orders')->where('amount', '>', function ($query) {
        $query->table('orders')->select('amount')->where('status', 'shipped');
    });

    // output: SELECT * FROM `orders` WHERE `amount` > (SELECT `amount` FROM `orders` WHERE `status` = ?)
    ```

1. `orWhere`

    same `where`

1. `whereNull`

    ```php

    $db->whereNull('<columnName1>');

    // example

    $db->table('orders')->whereNull('shipped_at');

    // output: SELECT * FROM `orders` WHERE `shipped_at` IS NULL
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

    // example

    $db->table('orders')->whereIn('status', ['shipped', 'delivered']);

    // output: SELECT * FROM `orders` WHERE `status` IN (?, ?)

    $dbTable = $connection->newQuery()->select('id')->table('users');

    $db->table('orders')->whereIn('user_id', $dbTable);

    // output: SELECT * FROM `orders` WHERE `user_id` IN (SELECT `id` FROM `users`)

    $db->table('orders')->whereIn('user_id', function ($query) {
        $query->select('id')->table('users');
    });

    // output: SELECT * FROM `orders` WHERE `user_id` IN (SELECT `id` FROM `users`)
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

    // example

    $db->table('orders')->whereBetween('amount', [100, 500]);

    // output: SELECT * FROM `orders` WHERE `amount` BETWEEN ? AND ?
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

    // example

    $db->table('orders')->whereExists(function ($query) {
        $query->table('users');
    });

    // output: SELECT * FROM `orders` WHERE EXISTS (SELECT * FROM `users`)
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

    // example

    $db->table('orders')->whereLike('status', '%ship%');

    // output: SELECT * FROM `orders` WHERE `status` LIKE ?
    ```

1. `orWhereLike`

    ```php

    $db->orWhereLike('<columnName1>', '<columnValue2>');

    // example

    $db->table('orders')->where('id', 1)->orWhereLike('status', '%ship%');

    // output: SELECT * FROM `orders` WHERE `id` = ? OR `status` LIKE ?
    ```

### having

1. `having`

    ```php

    $db->having('<columnName1>', '<columnValue1>');

    // or

    $db->having('<columnName1>', "<operator>", '<columnValue1>');

    // or

    $db->having(
        '<columnName1>',
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $db->having(
        '<columnName1>',
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

    $db->having('<columnName1>', $dbTable);

    // or 

    $db->having('<columnName1>', "<operator>", $dbTable);

    // example

    $db->table('orders')->groupBy('status')->having('status', 'shipped');

    // output: SELECT * FROM `orders` GROUP BY `status` ASC HAVING `status` = ?

    $db->table('orders')->groupBy('status')->having('total', '>', 100);

    // output: SELECT * FROM `orders` GROUP BY `status` ASC HAVING `total` > ?

    $db->table('orders')->groupBy('status')->having('status', function ($query) {
        $query->select('status')->table('orders')->where('id', 1);
    });

    // output: SELECT * FROM `orders` GROUP BY `status` ASC HAVING `status` = (SELECT `status` FROM `orders` WHERE `id` = ?)

    $dbTable = $connection->newQuery()->table('users');

    $db->table('orders')->groupBy('status')->having('status', $dbTable);

    // output: SELECT * FROM `orders` GROUP BY `status` ASC HAVING `status` = (SELECT * FROM `users`)
    ```

1. `orHaving`

    ```php

    $db->orHaving('<columnName1>', '<columnValue1>');

    // or

    $db->orHaving('<columnName1>', "<operator>", '<columnValue1>');

    // or

    $db->orHaving(
        '<columnName1>',
        function ($query) {
            $query->table('<table name>');
            // do something
        }
    );

    // or

    $db->orHaving(
        '<columnName1>',
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

    $db->orHaving('<columnName1>', $dbTable);

    // or 

    $db->orHaving('<columnName1>', "<operator>", $dbTable);

    // example

    $db->table('orders')->groupBy('status')->having('status', 'shipped')->orHaving('status', 'delivered');

    // output: SELECT * FROM `orders` GROUP BY `status` ASC HAVING `status` = ? OR `status` = ?
    ```

### limit or offset

1. `limit`

    ```php

    $db->limit(1); // set query LIMIT

    // or

    $db->limit(10, 1); // set query LIMIT

    // example

    $db->table('orders')->limit(1);

    // output: SELECT * FROM `orders` LIMIT ?

    $db->table('orders')->limit(10, 1);

    // output: SELECT * FROM `orders` LIMIT ?, ?
    ```

1. `offset`

    ```php

    $db->offset(1); // set query OFFSET

    // example

    $db->table('orders')->offset(1);

    // output: SELECT * FROM `orders` OFFSET ?
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

    // example

    $db->table('orders')->groupBy('status', 'DESC');

    // output: SELECT * FROM `orders` GROUP BY `status` DESC

    $db->table('orders')->groupBy([
        ['status', 'DESC'],
        ['type', 'ASC'],
    ]);

    // output: SELECT * FROM `orders` GROUP BY `status` DESC, `type` ASC

    $dbTable = $connection->newQuery()->table('users');

    $db->table('orders')->groupBy($dbTable, 'DESC');

    // output: SELECT * FROM `orders` GROUP BY (SELECT * FROM `users`) DESC
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

    // example

    $db->table('orders')->groupByDesc('status');

    // output: SELECT * FROM `orders` GROUP BY `status` DESC

    $db->table('orders')->groupByDesc('status', 'type');

    // output: SELECT * FROM `orders` GROUP BY `status` DESC, `type` DESC

    $dbTable = $connection->newQuery()->table('users');

    $db->table('orders')->groupByDesc($dbTable, 'status');

    // output: SELECT * FROM `orders` GROUP BY (SELECT * FROM `users`) DESC, `status` DESC
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

    // example

    $db->table('orders')->groupByAsc('status');

    // output: SELECT * FROM `orders` GROUP BY `status` ASC
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

    // example

    $db->table('orders')->orderBy('created_at', 'DESC');

    // output: SELECT * FROM `orders` ORDER BY `created_at` DESC

    $db->table('orders')->orderBy([
        ['created_at', 'DESC'],
        ['id', 'ASC'],
    ]);

    // output: SELECT * FROM `orders` ORDER BY `created_at` DESC, `id` ASC
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

    // example

    $db->table('orders')->orderByDesc('created_at');

    // output: SELECT * FROM `orders` ORDER BY `created_at` DESC

    $db->table('orders')->orderByDesc('created_at', 'id');

    // output: SELECT * FROM `orders` ORDER BY `created_at` DESC, `id` DESC
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

    // example

    $db->table('orders')->orderByAsc('created_at');

    // output: SELECT * FROM `orders` ORDER BY `created_at` ASC
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

    // example

    $db->table('orders')->union(function ($query) {
        $query->table('orders')->where('status', 'pending');
    });

    // output: SELECT * FROM `orders` UNION SELECT * FROM `orders` WHERE `status` = ?
    ```

1. `unionAll`
    sam `union`

### Get Data

1. `get`

    ```php

    $db->get(); // get all data

    // example

    $db->table('orders')->get();

    // output (real rows from the `orders` table):
    // [
    //     ['id' => 1, 'status' => 'shipped', 'type' => 'online', 'amount' => 150, ...],
    //     ['id' => 2, 'status' => 'pending', 'type' => 'offline', 'amount' => 80, ...],
    // ]
    ```

1. `first`

    ```php

    $db->first(); // get first data

    // example

    $db->table('orders')->first();

    // output: ['id' => 1, 'status' => 'shipped', 'type' => 'online', 'amount' => 150, ...]
    ```

1. `find`

    ```php

    $db->find('<id>'); // get find data

    // example

    $db->table('orders')->find(1);

    // output: ['id' => 1, 'status' => 'shipped', 'type' => 'online', 'amount' => 150, ...]
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

    // example

    $db->table('orders')->where('id', '=', 1)->update(['status' => 'delivered']);

    // output: UPDATE `orders` SET `status` = ? WHERE `id` = ?
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

    // example

    $db->table('orders')->increment('amount');

    // output: UPDATE `orders` SET `amount` = `amount` + ?

    $db->table('orders')->increment('amount', 5);

    // output: UPDATE `orders` SET `amount` = `amount` + ?
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

    // example

    $db->table('orders')->decrement('amount');

    // output: UPDATE `orders` SET `amount` = `amount` - ?

    $db->table('orders')->decrement('amount', 5);

    // output: UPDATE `orders` SET `amount` = `amount` - ?
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

    // example

    ```php

    $db->table('orders')->insert([
        'status' => 'pending',
        'type' => 'online',
    ]);

    // output: INSERT INTO `orders` (`status`, `type`) VALUES (?, ?)

    // or

    $db->table('orders')->insert([
        [
            'status' => 'pending',
            'type' => 'online',
        ],
        [
            'status' => 'shipped',
            'type' => 'offline',
        ],
    ]);

    // output: INSERT INTO `orders` (`status`, `type`) VALUES (?, ?), (?, ?)
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

    // output: Insert <table> (<ColumnName1>, <ColumnName2>) SELECT <Sub ColumnName1>, <Sub ColumnName2> FROM <Sub table name>
    // WHERE <Sub columnName3> = <Sub value1> AND <Sub columnName4> = <Sub value2>
    ```

    // example

    ```php

    $db->table('orders')->insertSub(['status', 'type'], function ($query) {
        $query->from('orders')->select('status', 'type')
            ->where('id', 1)->where('amount', 100);
    });

    // output: INSERT INTO `orders` (`status`, `type`) SELECT `status`, `type` FROM `orders` WHERE `id` = ? AND `amount` = ?
    ```

1. `insertGetId` insert and return the new row's auto-increment id

    ```php

    $db->insertGetId([
        '<ColumnName1>' => '<ColumnValue1>',
        '<ColumnName2>' => '<ColumnValue2>',
        ...
    ]);

    // or, with a custom sequence name

    $db->insertGetId([
        '<ColumnName1>' => '<ColumnValue1>',
        '<ColumnName2>' => '<ColumnValue2>',
        ...
    ], '<sequence name>');
    ```

    // example

    ```php

    $db->table('orders')->insertGetId([
        'status' => 'pending',
        'type' => 'online',
    ]);

    // output: 10 (the new row's auto-increment id)
    ```

1. `getLastInsertId` or `lastInsertId` get the id of the last inserted row

    ```php

    $db->getLastInsertId();

    // or, with a custom sequence name

    $db->getLastInsertId('<sequence name>');
    ```

    // example

    ```php

    $db->table('orders')->insert(['status' => 'pending', 'type' => 'online']);

    $db->getLastInsertId();

    // output: "11"
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

    // example

    ```php

    $db->table('orders')->where('id', '=', 1)->delete();

    // output: DELETE FROM `orders` WHERE `id` = ?

    // or

    $orders = $db->table('orders')->where('id', '=', 1);

    $orders->first();

    $orders->delete();

    // output: SELECT * FROM `orders` WHERE `id` = ? LIMIT 1
    // output: DELETE FROM `orders` WHERE `id` = ?
    ```

1. `softDelete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->softDelete('<deleteColumnName1>', '<date time format>'); // default deleted_at, "Y-m-d H:i:s"

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->softDelete('<deleteColumnName1>', '<date time format>'); // default deleted_at, "Y-m-d H:i:s"
    ```

    // example

    ```php

    $db->table('orders')->where('id', '=', 1)->softDelete();

    // output: UPDATE `orders` SET `deleted_at` = ? WHERE `id` = ?

    // or

    $orders = $db->table('orders')->where('id', '=', 1);

    $orders->first();

    $orders->softDelete();

    // output: SELECT * FROM `orders` WHERE `id` = ? LIMIT 1
    // output: UPDATE `orders` SET `deleted_at` = ? WHERE `id` = ?
    ```

1. `reStore` recovery (`delete` cannot recovery data)

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->reStore('<deleteColumnName1>'); // default deleted_at

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->reStore('<deleteColumnName1>'); // default deleted_at
    ```

    // example

    ```php

    $db->table('orders')->where('id', '=', 1)->reStore();

    // output: UPDATE `orders` SET `deleted_at` = NULL WHERE `id` = ?
    ```

### Raw

1. `raw`
    ```php

    // select

    $db->select($db->raw("<sql string in select column>"));
    
    // example

    $db->table('orders')->select($db->raw("COUNT(*)"));

    // output: SELECT COUNT(*) FROM `orders`

    // update

    $db->update([
        $db->raw("<sql string in select column>"),
    ]);

    // example

    $db->table('orders')->where('id', 1)->update([
        $db->raw("status = 'shipped'"),
    ]);

    // output: UPDATE `orders` SET status = 'shipped' WHERE `id` = ?
    ```

### CASE WHEN

1. `caseWhen` - Simple CASE (with column)

    ```php

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value1>', '<result1>')
        ->when('<value2>', '<result2>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // output: select CASE `<columnName>` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - Searched CASE (without column, string condition)

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when('<condition expression>', '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // example

    $db->from('users')
        ->caseWhen()
        ->when('age > 18', 'Adult')
        ->otherwise('Minor')
        ->end('age_group');

    // output: select CASE WHEN age > 18 THEN ? ELSE ? END AS `age_group` from `users`
    ```

1. `caseWhen` - Searched CASE (Closure WHERE condition)

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when(function ($query) {
            $query->where('<columnName>', '<operator>', '<value>');
        }, '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // output: select CASE WHEN (<columnName> <operator> ?) THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - Searched CASE (Closure with `from()` → EXISTS subquery)

    ```php

    $db->from('<table name>')
        ->caseWhen()
        ->when(function ($query) {
            $query->from('<sub table name>')->select('<columnName>');
        }, '<result1>')
        ->otherwise('<default result>')
        ->end('<alias>');

    // output: select CASE WHEN EXISTS(SELECT `<columnName>` FROM `<sub table name>`) THEN ? ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - Nested CASE as THEN value

    ```php

    $innerCase = $db->caseWhen('<columnName1>')
        ->when('<value1>', '<inner result1>')
        ->otherwise('<inner default>');

    $db->from('<table name>')
        ->caseWhen('<columnName2>')
        ->when('<value2>', $innerCase)
        ->otherwise('<outer default>')
        ->end('<alias>');

    // output: select CASE `<columnName2>` WHEN ? THEN CASE `<columnName1>` WHEN ? THEN ? ELSE ? END ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` - `Expression` as THEN / ELSE value (embed raw SQL, no binding)

    ```php

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value1>', new \Wilkques\Database\Queries\Expression('<raw SQL>'))
        ->otherwise(new \Wilkques\Database\Queries\Expression('NULL'))
        ->end('<alias>');

    // example

    $db->from('orders')
        ->caseWhen('status')
        ->when('shipped', new \Wilkques\Database\Queries\Expression('NOW()'))
        ->otherwise(new \Wilkques\Database\Queries\Expression('NULL'))
        ->end('shipped_at');

    // output: select CASE `status` WHEN ? THEN NOW() ELSE NULL END AS `shipped_at` from `orders`
    ```

1. `caseWhen` - `Expression` as Simple CASE column (raw SQL, no backtick wrapping)

    ```php

    $db->from('<table name>')
        ->caseWhen(new \Wilkques\Database\Queries\Expression('<raw column expression>'))
        ->when('<value1>', '<result1>')
        ->otherwise('<default>')
        ->end('<alias>');

    // example

    $db->from('orders')
        ->caseWhen(new \Wilkques\Database\Queries\Expression('YEAR(created_at)'))
        ->when(2024, 'This Year')
        ->otherwise('Other')
        ->end('year_label');

    // output: select CASE YEAR(created_at) WHEN ? THEN ? ELSE ? END AS `year_label` from `orders`
    ```

1. `end` - Compile and add to SELECT, returns `CompiledClause` (proxies to parent Builder)

    ```php

    // with alias — returns CompiledClause, chain via proxy
    $db->from('<table name>')->caseWhen('<columnName>')->when(...)->end('<alias>')->select('name')->get();

    // without alias
    $db->from('<table name>')->caseWhen('<columnName>')->when(...)->end();

    // example

    $db->from('orders')->caseWhen('status')->when('active', 'Active')->otherwise('Inactive')->end('status_label')->select('id')->get();

    // output: SELECT CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label`, `id` FROM `orders`

    $db->from('orders')->caseWhen('status')->when('active', 'Active')->otherwise('Inactive')->end()->get();

    // output: SELECT CASE `status` WHEN ? THEN ? ELSE ? END FROM `orders`
    ```

    > `end()` now returns a `CompiledClause` object instead of the parent `Builder`.  
    > All method calls on `CompiledClause` are transparently proxied to the parent `Builder`,  
    > so existing fluent chains continue to work unchanged.

### IF Expression

> ⚠️ `IF()` is MySQL-specific. For other databases use `caseWhen()` instead.

1. `ifExpr` - Simple scalar condition

    ```php

    $db->from('<table name>')
        ->ifExpr('<condition expression>')
        ->then('<true result>')
        ->otherwise('<false result>')
        ->end('<alias>');

    // example

    $db->from('users')
        ->ifExpr('age >= 18')
        ->then('Adult')
        ->otherwise('Minor')
        ->end('age_group');

    // output: select IF(age >= 18, ?, ?) AS `age_group` from `users`
    ```

1. `ifExpr` - Nested IF (pass IfClause instance as value)

    ```php

    $inner = $db->ifExpr('<condition1>')->then('<result1>')->otherwise('<result2>');

    $db->from('<table name>')
        ->ifExpr('<condition2>')
        ->then('<result3>')
        ->otherwise($inner)
        ->end('<alias>');

    // output: select IF(<condition2>, ?, IF(<condition1>, ?, ?)) AS `<alias>` from `<table name>`
    ```

1. `ifExpr` - Closure with `from()` → EXISTS subquery

    ```php

    $db->from('<table name>')
        ->ifExpr(function ($query) {
            $query->from('<sub table name>')->select('<columnName>')->where('<columnName2>', '<value>');
        })
        ->then('<true result>')
        ->otherwise('<false result>')
        ->end('<alias>');

    // output: select IF(EXISTS(SELECT `<columnName>` FROM `<sub table name>` WHERE `<columnName2>` = ?), ?, ?) AS `<alias>` from `<table name>`
    ```

1. `ifExpr` as CASE WHEN THEN value

    ```php

    $ifExpr = $db->ifExpr('<condition>')->then('<result1>')->otherwise('<result2>');

    $db->from('<table name>')
        ->caseWhen('<columnName>')
        ->when('<value>', $ifExpr)
        ->otherwise('<default>')
        ->end('<alias>');

    // output: select CASE `<columnName>` WHEN ? THEN IF(<condition>, ?, ?) ELSE ? END AS `<alias>` from `<table name>`
    ```

1. `caseWhen` as `ifExpr` THEN / ELSE value

    ```php

    $caseExpr = $db->caseWhen('<columnName>')
        ->when('<value1>', '<result1>')
        ->otherwise('<default>');

    $db->from('<table name>')
        ->ifExpr('<condition>')
        ->then($caseExpr)
        ->otherwise('<fallback>')
        ->end('<alias>');

    // output: select IF(<condition>, CASE `<columnName>` WHEN ? THEN ? ELSE ? END, ?) AS `<alias>` from `<table name>`
    ```

### CASE WHEN / IF in `select()` and `update()` Array Form

> Pass `CaseClause` / `IfClause` / `CompiledClause` directly inside `select([...])` or `update([...])` arrays.

#### `select([...])` — three forms

1. **Direct form** (recommended for ordering guarantee): alias via array key, no `end()` call

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

    // output: SELECT `name`,
    //         CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label`,
    //         IF(age >= 18, ?, ?) AS `age_group`
    //         FROM `users`
    ```

1. **`end()` form**: alias provided by `end()`, array key is ignored

    ```php

    $db->from('users')->select([
        $db->caseWhen('status')
            ->when('active', 'Active User')
            ->otherwise('Unknown')
            ->end('status_label'),
    ])->get();

    // output: SELECT CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label` FROM `users`
    ```

    > ⚠️ **Column ordering**: `end()` triggers a `selectRaw` side-effect when evaluated by PHP,  
    > so `end()`-form columns always appear **before** other columns in the SELECT list,  
    > regardless of their position in the array. Use the direct form to guarantee order.

1. **Closure form**: Closure must `return` the clause; no return falls back to scalar subquery mode

    ```php

    // ✅ with return — CASE expression inserted directly
    $db->from('users')->select([
        function ($q) {
            return $q->caseWhen('status')
                ->when('active', 'Active User')
                ->otherwise('Unknown')
                ->end('status_label');
        },
    ])->get();

    // output: SELECT CASE `status` WHEN ? THEN ? ELSE ? END AS `status_label` FROM `users`

    // ⚠️ without return — falls back to subquery (wraps the SELECT as a scalar subquery)
    $db->from('users')->select([
        function ($q) {
            $q->caseWhen('status')->when('active', 'Active User')->end('status_label');
            // no return → $q's SELECT has the CASE, but it becomes (SELECT CASE ...)
        },
    ])->get();

    // output: SELECT (SELECT CASE `status` WHEN ? THEN ? END AS `status_label`) FROM `users`
    ```

#### `update([...])` — four forms

1. **Direct form without `end()`** (recommended): column from array key, no alias side-effect

    ```php

    $db->from('users')->where('id', 1)->update([
        'status_label' => $db->caseWhen('status')
            ->when('active', 'Active User')
            ->otherwise('Unknown'),
        'age_group' => $db->ifExpr('age >= 18')
            ->then('Adult')
            ->otherwise('Minor'),
    ]);

    // output: UPDATE `users`
    //         SET `status_label` = CASE `status` WHEN ? THEN ? ELSE ? END,
    //             `age_group` = IF(age >= 18, ?, ?)
    //         WHERE `id` = ?
    ```

1. **Direct form with `end()`**: `end()` alias is ignored in UPDATE; column name comes from array key

    ```php

    $db->from('users')->where('id', 1)->update([
        'status' => $db->caseWhen('status')
            ->when('active', 'Active User')
            ->when('inactive', 'Inactive User')
            ->otherwise('Unknown')
            ->end('status_label'),  // 'status_label' is ignored; column is 'status' from array key
        'status2' => $db->ifExpr('age >= 18')
            ->then('Adult')
            ->otherwise('Minor')
            ->end('age_group'),     // 'age_group' is ignored; column is 'status2' from array key
    ]);

    // output: UPDATE `users`
    //         SET `status` = CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END,
    //             `status2` = IF(age >= 18, ?, ?)
    //         WHERE `id` = ?
    ```

    > ⚠️ `end()` still fires a `selectRaw` side-effect on the parent builder.  
    > For clean UPDATE without side-effects, prefer the form without `end()`.

1. **Closure form with `return`**: Closure must `return` the clause

    ```php

    $db->from('users')->where('id', 1)->update([
        'status_label' => function ($q) {
            return $q->caseWhen('status')
                ->when('active', 'Active User')
                ->otherwise('Unknown');  // return CaseClause directly (no end() call)
        },
    ]);

    // output: UPDATE `users` SET `status_label` = CASE `status` WHEN ? THEN ? ELSE ? END WHERE `id` = ?
    ```

1. **Closure form without `return`**: `end()` called inside Closure but not returned → falls back to scalar subquery

    ```php

    $db->from('users')->where('id', 1)->update([
        'status2' => function ($q) {
            $q->caseWhen('status')
                ->when('active', 'Active User')
                ->when('inactive', 'Inactive User')
                ->otherwise('Unknown')
                ->end('status_label2');  // no return — becomes a scalar subquery
        },
    ]);

    // output: UPDATE `users`
    //         SET `status2` = (SELECT CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `status_label2`)
    //         WHERE `id` = ?
    ```

    > ⚠️ Without `return`, the Closure falls back to subquery mode — the CASE becomes a scalar subquery  
    > instead of a direct SET expression. Use the `return` form or direct form to avoid this.

1. **Mixed forms** (same as REPORT example — valid but with caveats noted above)

    ```php

    $db->from('users')->where('id', 1)->update([
        'status' => $db->caseWhen('status')        // end() form: alias ignored, column from key
            ->when('active', 'Active User')
            ->when('inactive', 'Inactive User')
            ->otherwise('Unknown')
            ->end('status_label'),
        'status2' => function ($q) {               // Closure without return: scalar subquery
            $q->caseWhen('status')
                ->when('active', 'Active User')
                ->when('inactive', 'Inactive User')
                ->otherwise('Unknown')
                ->end('status_label2');
        },
    ]);

    // output: UPDATE `users`
    //         SET `status`  = CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END,
    //             `status2` = (SELECT CASE `status` WHEN ? THEN ? WHEN ? THEN ? ELSE ? END AS `status_label2`)
    //         WHERE `id` = ?
    ```

#### Notes

- In `update()`, **the array key always determines the column name**. `end()` aliases are never used in SET clauses.
- `compileSql()` always returns SQL **without** alias — safe for `UPDATE SET` expressions.
- Closure without `return` → scalar subquery (wraps the SELECT); Closure with `return` → direct expression.
- `CaseClause` and `IfClause` implement `CompilableClause` interface, so any future expression type that also implements `compileSql()` will work automatically in `select()` / `update()`.

### SQL Execute

1. `query` set SQL string

    ```php

    $db->query("<SQL String>")->fetch();

    // for example

    $db->query("SELECT * FROM `<your table name>`")->fetch();
    ```

    // example

    ```php

    $row = $db->query("SELECT * FROM `orders` LIMIT 1")->fetch();

    // output: {"id":2,"status":"pending","type":"offline","amount":81,"user_id":2,"shipped_at":null,"created_at":null,"deleted_at":"2026-09-29 07:04:06"}
    ```

1. `prepare` execute SQL string

    ```php

    $db->prepare("<SQL String>")->execute(['<value1>', '<value2>' ...])->fetch();
    ```

    // example

    ```php

    $row = $db->prepare("SELECT * FROM `orders` WHERE `id` = ?")->execute([2])->fetch();

    // output: {"id":2,"status":"pending","type":"offline","amount":81,"user_id":2,"shipped_at":null,"created_at":null,"deleted_at":"2026-09-29 07:04:06"}
    ```

1. `bindParams` execute SQL string

    ```php

    $stat = $db->prepare("<SQL String>");

    // execute() returns a new Result — fetch() must be called on that
    // return value, not on $stat itself (Statement has no fetch()).
    $result = $stat->bindParams(['<value1>', '<value2>' ...])->execute();

    $result->fetch();
    ```

    // example

    ```php

    $stat = $db->prepare("SELECT * FROM `orders` WHERE `id` = ?");

    $result = $stat->bindParams([2])->execute();

    $row = $result->fetch();

    // output: {"id":2,"status":"pending","type":"offline","amount":81,"user_id":2,"shipped_at":null,"created_at":null,"deleted_at":"2026-09-29 07:04:06"}
    ```

1. `execute` execute SQL string

    ```php

    // example

    $db->prepare('SELECT * FROM `orders` WHERE `id` = 2')->execute()->fetch();

    // output: {"id":2,"status":"pending","type":"offline","amount":81,"user_id":2,"shipped_at":null,"created_at":null,"deleted_at":"2026-09-29 07:04:06"}
    ```

### SQL Execute result

1. `fetchNumeric` get result key to numeric

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` WHERE `id` = 2')->fetchNumeric();

    // output: [2, "pending"]
    ```

1. `fetchAssociative` get result key value

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` WHERE `id` = 2')->fetchAssociative();

    // output: {"id": 2, "status": "pending"}
    ```

1. `fetchFirstColumn` get result first column

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` WHERE `id` = 2')->fetchFirstColumn();

    // output: 2
    ```

1. `fetchAllNumeric` get all result key to numeric

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` ORDER BY `id` LIMIT 2')->fetchAllNumeric();

    // output: [[2, "pending"], [3, "pending"]]
    ```

1. `fetchAllAssociative` get all result key value

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` ORDER BY `id` LIMIT 2')->fetchAllAssociative();

    // output: [{"id": 2, "status": "pending"}, {"id": 3, "status": "pending"}]
    ```

1. `fetchAllFirstColumn` get all result first column

    ```php

    // example

    $db->query('SELECT `id`, `status` FROM `orders` ORDER BY `id` LIMIT 2')->fetchAllFirstColumn();

    // output: [2, 3]
    ```

1. `rowCount` get result

    ```php

    // example

    $db->query('UPDATE `orders` SET `status` = "shipped" WHERE `id` = 2')->rowCount();

    // output: 1
    ```

1. `free` PDO method `closeCursor` [PHP PDOStatement::closeCursor](https://www.php.net/manual/en/pdostatement.closecursor.php)

1. `fetch` [PDOStatement::fetch](https://www.php.net/manual/en/pdostatement.fetch.php)

    ```php

    // example

    $db->query('SELECT * FROM `orders` WHERE `id` = 2')->fetch();

    // output: {"id":2,"status":"pending","type":"offline","amount":81,"user_id":2,"shipped_at":null,"created_at":null,"deleted_at":"2026-09-29 07:04:06"}
    ```

1. `fetchAll` [PDOStatement::fetchAll](https://www.php.net/manual/en/pdostatement.fetchall.php)

    ```php

    // example

    $db->query('SELECT * FROM `orders` ORDER BY `id` LIMIT 2')->fetchAll();

    // output: [{"id":2,"status":"pending",...}, {"id":3,"status":"pending",...}]
    ```

### Query Log

1. `enableQueryLog` enable query logs
    ```php

    $db->enableQueryLog();
    ```

1. `getQueryLog` or `queryLog` get all query string and bind data

    ```php

    $db->getQueryLog();

    // or

    $db->queryLog();
    ```

    // example

    ```php

    $db->enableQueryLog();

    $db->table('orders')->where('status', '=', 'pending')->get();

    $db->getQueryLog();

    // output: [{"query": "SELECT * FROM `orders` WHERE `status` = ?", "bindings": {"1": "pending"}}]
    ```

1. `getLastQueryLog` or `lastQueryLog` get the most recent query string and bind data

    ```php

    $db->getLastQueryLog();

    // or

    $db->lastQueryLog();
    ```

    // example

    ```php

    $db->enableQueryLog();

    $db->table('orders')->where('status', '=', 'pending')->get();

    $db->getLastQueryLog();

    // output: {"query": "SELECT * FROM `orders` WHERE `status` = ?", "bindings": {"1": "pending"}}
    ```

1. `getParseQueryLog` or `parseQueryLog` get paser query logs
    ```php

    $db->getParseQueryLog();
    ```

    // example

    ```php

    $db->getParseQueryLog();

    // output: ["SELECT * FROM `orders` WHERE `status` = \"pending\""]
    ```

1. `getLastParseQuery` or `lastParseQuery` get paser query
    ```php

    $db->getLastParseQuery();
    ```

    // example

    ```php

    $db->getLastParseQuery();

    // output: SELECT * FROM `orders` WHERE `status` = "pending"
    ```

### Lock

1. `lockForUpdate`

    ```php
    
    $db->lockForUpdate();
    ```

    // example

    ```php

    $db->table('orders')->where('id', '=', 1)->lockForUpdate()->toSql();

    // output: SELECT * FROM `orders` WHERE `id` = ? FOR UPDATE
    ```

1. `sharedLock`

    ```php
    
    $db->sharedLock();
    ```

    // example

    ```php

    $db->table('orders')->where('id', '=', 1)->sharedLock()->toSql();

    // output: SELECT * FROM `orders` WHERE `id` = ? LOCK IN SHARE MODE
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

    // example

    ```php

    $db->table('orders')->currentPage(1)->prePage(2)->getForPage();

    // output: SELECT * FROM `orders` LIMIT ? OFFSET ?

    // or

    $db->table('orders')->getForPage(2, 1); // prePage 2, currentPage 1

    // output: SELECT * FROM `orders` LIMIT ? OFFSET ?
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

    // example

    ```php

    $db->beginTransaction();

    $db->table('orders')->insert(['status' => 'pending', 'type' => 'test-txn']);

    $db->rollback();

    $db->table('orders')->where('type', '=', 'test-txn')->first();

    // output: false (rolled back, row never persisted)

    $db->beginTransaction();

    $db->table('orders')->insert(['status' => 'pending', 'type' => 'test-txn2']);

    $db->commit();

    $db->table('orders')->where('type', '=', 'test-txn2')->first();

    // output: {"id": 8, "status": "pending", "type": "test-txn2", ...} (committed, row persisted)
    ```

### Connect

1. `setHost` or `host` / `getHost`

    ```php

    $db->setHost('<DB host>');

    // or

    $db->host('<DB host>');

    $db->getHost();
    ```

1. `setUsername` or `username` / `getUsername`

    ```php

    $db->setUsername('<DB username>');

    // or

    $db->username('<DB username>');

    $db->getUsername();
    ```

1. `setPassword` or `password` / `getPassword`

    ```php

    $db->setPassword('<DB password>');

    // or

    $db->password('<DB password>');

    $db->getPassword();
    ```

1. `setDatabase` or `database` / `getDatabase`

    ```php

    $db->setDatabase('<DB name>');

    // or

    $db->database('<DB name>');

    $db->getDatabase();
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

    All of the setters above (`setHost`/`setUsername`/`setPassword`/`setDatabase`/`newConnection`/`reConnection`/`selectDatabase`, and their shortened `host`/`username`/`password`/`database` forms) return `$db` itself, so they chain into the rest of the query builder like any other setter.

    ```php

    // example

    $db->setHost('127.0.0.1')->setUsername('root')->setPassword('root')->setDatabase('test')->table('orders')->toSql();

    // output: SELECT * FROM `orders`

    // or, using the shortened forms

    $db->host('127.0.0.1')->username('root')->password('root')->database('test')->table('orders')->toSql();

    // output: SELECT * FROM `orders`
    ```