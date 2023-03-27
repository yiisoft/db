# Having

The `Yiisoft\Db\Query\Query::having()` method specifies the `HAVING` fragment of a SQL query.
It takes a condition which you can specify in the same way as that for `Yiisoft\Db\Query\Query::where()`.

For example, the following code will generate a query that filters the results by the `status` column:

```php
// ... HAVING `status` = 1
$query->having(['status' => 1]);
```

Please refer to the documentation for [Where](/docs/en/query/where.md) for more details about how to specify a condition.

You can call `Yiisoft\Db\Query\Query::andHaving()` or `Yiisoft\Db\Query\Query::orHaving()` to append more conditions
to the `HAVING` fragment.

For example, the following code will generate a query that filters the results by the `status` column and the `age`
column:

```php
// ... HAVING (`status` = 1) AND (`age` > 30)
$query->having(['status' => 1])->andHaving(['>', 'age', 30]);
```
