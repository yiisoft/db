# Group by

The `\Yiisoft\Db\Query\Query::groupBy()` method specifies the `GROUP BY` fragment of a SQL query.

For example, the following code will generate a query that groups the results by the `id` column and the `status` column.

```php
// ... GROUP BY `id`, `status`
$query->groupBy(['id', 'status']);
```

If a `GROUP BY` only involves simple column names, you can specify it using a string, just like you do when writing raw SQL statements.

For example, the following code will generate a query that groups the results by the `id` column and the `status` column.

```php
$query->groupBy('id, status');
```

**Note:** *You should use the array format if `GROUP BY` involves some DB expression*.

You can call `\Yiisoft\Db\Query\Query::addGroupBy()` to add additional columns to the `GROUP BY` fragment.

For example, the following code will generate a query that groups the results by the `id` column, the `status` column and the `age` column.

```php
$query->groupBy(['id', 'status'])->addGroupBy('age');
```
