# Database

[![Latest Stable Version](https://poser.pugx.org/wilkques/database/v/stable)](https://packagist.org/packages/wilkques/database)
[![License](https://poser.pugx.org/wilkques/database/license)](https://packagist.org/packages/wilkques/database)

## Notice

1. 目前只有 `MySQL`
1. 此為簡易 Database 操作
1. 暫無 `join` 功能，但可以利用 method `query` or `prepare` 自己寫 SQL Query

## ENV

1. php >= 5.4
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

    $connection = new \Wilkques\Database\Connections\PDO\MySql('<host>', '<username>', '<password>', '<database name>');

    $builder = new \Wilkques\Database\Queries\Builder(
        $connection,
        new \Wilkques\Database\Queries\Grammar\MySql,
        new \Wilkques\Database\Queries\Process\Process,
    );

    $db = \Wilkques\Database\Database::builder($builder);
    ```

1. Via Composer
    `composer require wilkques/database`

    ```php

    require "vendor/autoload.php";

    $connection = new \Wilkques\Database\Connections\PDO\MySql('<host>', '<username>', '<password>', '<database name>');

    $builder = new \Wilkques\Database\Queries\Builder(
        $connection,
        new \Wilkques\Database\Queries\Grammar\MySql,
        new \Wilkques\Database\Queries\Process\Process,
    );

    $db = \Wilkques\Database\Database::builder($builder);
    ```

## Methods

1. `table`

    ```php

    $db->table('<table name>');
    ```

1. `select`

    ```php

    $db->select(['<columnName1>', '<columnName2>', '<columnName3>']);

    // or

    $db->select("`<columnName1>`, `<columnName2>`, `<columnName3>`");
    ```

1. `limit`

    ```php

    $db->limit(1); // set query LIMIT
    ```

1. `offset`

    ```php

    $db->offset(1); // set query OFFSET
    ```

1. `groupBy`

    ```php

    $db->groupBy('<columnName1>');
    ```

1. `orderBy`

    ```php

    $db->orderBy('<columnName1>', "DESC"); // default ASC
    ```

1. `get`

    ```php

    $db->get(); // get all data
    ```

1. `first`

    ```php

    $db->first(); // get data
    ```

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
    ```

1. `increment`

    ```php

    $db->increment('<columnNmae>');

    // or

    $db->increment('<columnNmae>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

1. `decrement`

    ```php

    $db->decrement('<columnNmae>');

    // or

    $db->decrement('<columnNmae>', '<numeric>', [
        '<update column 1>' => 'update value 1',
        '<update column 2>' => 'update value 2',
        ...
    ]);
    ```

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

### Delete

1. `delete`

    ```php

    $db->where('<columnName1>', "=", '<columnValue1>')
        ->delete([
            '<deleteColumnName1>' => '<deleteColumnValue1>'
        ]);

    // or

    $db->where('<columnName1>', "=", '<columnValue1>')->first();

    $db->delete([
        '<deleteColumnName1>' => '<deleteColumnValue1>'
    ]);
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

### Where

1. `where`

    ```php

    $db->where([
        ['<columnName1>', "=", '<columnValue1>'],
    ]);

    // or

    $db->where('<columnName1>', "=", '<columnValue1>');
    ```

1. `whereIn`

    ```php

    $db->whereIn('<columnName1>', [
        ['<columnValue1>', '<columnValue2>'],
    ]);
    ```

1. `whereNull`

    ```php

    $db->whereNull('<columnName1>');

    // or

    $db->whereNull(['<columnName1>']);
    ```

1. `whereOrNull`

    ```php

    $db->whereOrNull('<columnName1>');

    // or

    $db->whereNull(['<columnName1>']);
    ```

1. `whereNotNull`

    ```php

    $db->whereNotNull('<columnName1>');

    // or

    $db->whereNotNull(['<columnName1>']);
    ```

1. `whereOrNotNull`

    ```php

    $db->whereOrNotNull('<columnName1>');

    // or

    $db->whereOrNotNull(['<columnName1>']);
    ```

### Raw

1. `raw`
    ```php

    $db->select($db->raw("<sql string in select column>"));
    
    // example

    $db->select($db->raw("COUNT(*)"));
    ```

1. `selectRaw`
    ```php

    $db->selectRaw("<sql string in select column>");

    // example

    $db->selectRaw("`first_name`, `last_name`");
    ```

1. `whereRaw`
    ```php

    $db->whereRaw("<sql string in select column>");

    // example

    $db->whereRaw("`first_name` = 'Bill' AND `last_name` = 'Whrite'");
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

### 鎖

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

1. `dbname`

    ```php

    $db->dbname('<DB name>');
    ```

1. `newConnect`

    ```php

    $db->newConnect();

    // or

    $db->newConnect("<sql server dns string>");
    ```