# Select

The `Yiisoft\Db\Query\Query::select()` method specifies the `SELECT` fragment of a **SQL statement**. You can specify **columns** to be selected in either an array or a string, like the following. The column names being selected will be automatically quoted when the **SQL statement** is being generated from a **query object**.

```php
$query->select(['id', 'email']);

// equivalent to:

$query->select('id, email');
```

The column names being selected may include table prefixes and/or column aliases, like you do when writing raw SQL queries.

For example, the following code will select the `id` and `email` columns from the `user` table.

```php
$query->select(['user.id AS user_id', 'email']);

// equivalent to:

$query->select('user.id AS user_id, email');
```

If you are using the array format to specify columns, you can also use the array keys to specify the column aliases.

For example, the above code can be rewritten as follows.

```php
$query->select(['user_id' => 'user.id', 'email']);
```

If you do not call the `Yiisoft\Db\Query\Query::select()` method when building a query, `*` will be selected, which means selecting all columns.

Besides column names, you can also select **DB expressions**. You must use the array format when selecting a **DB expression** that contains commas to avoid incorrect automatic name quoting. 

For example, the following code will select columns `CONCAT(first_name, ' ', last_name)` with alias `full_name` and column `email`.

```php
$query->select(["CONCAT(first_name, ' ', last_name) AS full_name", 'email']); 
```

As with all places where **raw SQL** is involved, you may use the DBMS agnostic quoting syntax for table and column names when writing DB expressions in select.

You may also select **sub-queries**. You should specify each **sub-query** in terms of a `Yiisoft\Db\Query\Query` object.

For example, the following code will select count of users in each post.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$subQuery = (new Query($db))->select('COUNT(*)')->from('user');

// SELECT `id`, (SELECT COUNT(*) FROM `user`) AS `count` FROM `post`
$query = (new Query($db))->select(['id', 'count' => $subQuery])->from('post');
```

To select distinct rows, you may call distinct(), like the following.

```php
// SELECT DISTINCT `user_id` ...
$query->select('user_id')->distinct();
```

You can call `Yiisoft\Db\Query\Query::addSelect()` to select additional columns.

For example, the following code will select `id` and `username` columns, additionally to the `email` column. 

```php
$query->select(['id', 'username'])->addSelect(['email']);
```
