# Select

The `Yiisoft\Db\Query\Query::select()` method specifies the `SELECT` fragment of a SQL statement.

By default, all columns will be selected, so the call of `select()` method can be skipped completely:

```php
$query->from('{{%user}}');

// equal to:

$query->select('*')->from('{{%user}}');
```

You can specify columns to select either as an array or as a string.
The selected column names will be automatically quoted during the generation of the SQL statement.

```php
$query->select(['id', 'email']);

// equal to:

$query->select('id, email');
```

The column names selected may include table prefixes and/or column aliases, like you do when writing raw SQL queries.

For example, the following code will select the `id` and `email` columns from the `user` table.

```php
$query->select(['user.id AS user_id', 'email']);

// equal to:

$query->select('user.id AS user_id, email');
```

If you're using the array format to specify columns, you can also use the array keys to specify the column aliases.

For example, the above code can be rewritten as follows.

```php
$query->select(['user_id' => 'user.id', 'email']);
```

If you don't call the `Yiisoft\Db\Query\Query::select()` method when building a query,
it assumes to select `*` which means selecting all columns.

Besides column names, you can also select DB expressions.
In this case, you must use the array format to avoid wrong automatic name quoting. 

For example, the following code will select columns `CONCAT(first_name, ' ', last_name)` with alias `full_name`
and column `email`.

```php
$query->select(["CONCAT(first_name, ' ', last_name) AS full_name", 'email']); 
```

As with all places with raw SQL involved,
you may use the DBMS agnostic quoting syntax for table and column names when writing DB expressions in select.

You may also select sub-queries. You should specify each sub-query in terms of a `Yiisoft\Db\Query\Query` object.

For example, the following code will select count of users in each post.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$subQuery = (new Query($db))->select('COUNT(*)')->from('{{%user}}');
$query = (new Query($db))->select(['id', 'count' => $subQuery])->from('{{%post}}');
```

The equivalent SQL is:

```sql
SELECT `id`, (SELECT COUNT(*) FROM `user`) AS `count` FROM `post`
```

To select distinct rows, you may call `distinct()`, like the following.

```php
$query->select('user_id')->distinct();
```

The relevant part of SQL is:

```sql
SELECT DISTINCT `user_id`
```

You can call `Yiisoft\Db\Query\Query::addSelect()` to select more columns.

For example, the following code will select `email` column, additionally to `id` and `username` columns specified 
initially: 

```php
$query->select(['id', 'username'])->addSelect(['email']);
```
