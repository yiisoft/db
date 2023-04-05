# Query builder

Built on top of Yii DB, query builder allows you to construct an SQL query in a programmatic and DBMS-agnostic way.

Compared to writing raw SQL statements, using query builder will help you write more readable SQL-related code
and generate more secure SQL statements.

Using a query builder usually involves two steps:

1. Build a `Yiisoft\Db\Query\Query` class instance to represent different parts (such as `SELECT`, `FROM`) of a `SELECT`
   SQL statement.
2. Execute a **query method** for example, `all()`, `one()`, `scalar()`, `column()`, `query()` of
   `Yiisoft\Db\Query\Query` to retrieve data from the database.

The following code shows a typical way of using a query builder.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$rows = (new Query($db))
    ->select(['id', 'email'])
    ->from('{{%user}}')
    ->where(['last_name' => 'Smith'])
    ->limit(10)
    ->all();
```

The above code generates and executes the following SQL query, where the `:last_name` parameter is bound with
the string `Smith`:

```sql
SELECT `id`, `email` 
FROM `user`
WHERE `last_name` = :last_name
LIMIT 10
```

> Note: `Yiisoft\Db\Query\Query` is intended to be used more instead of `Yiisoft\Db\QueryBuilder\QueryBuilder`.
> The former invokes the latter implicitly when you call one of the query methods.
> `Yiisoft\Db\QueryBuilder\QueryBuilder` is the class responsible for generating DBMS-dependent SQL statements such as
> `SELECT`, `FROM`, `WHERE`, `ORDER BY` from `Yiisoft\Db\Query\Query`.

## Usage

- [Building queries](/docs/en/query-builder/building-queries.md).
