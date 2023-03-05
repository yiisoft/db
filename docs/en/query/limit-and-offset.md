# Limit and Offset

The `\Yiisoft\Db\Query\Query::limit()` and `\Yiisoft\Db\Query\Query::offset()` methods specify the `LIMIT` and `OFFSET` fragments of a SQL query.

For example, the following code will build a query that will return only 10 records starting from the 20th one.

```php
// ... LIMIT 10 OFFSET 20
$query->limit(10)->offset(20);
```

If you specify an invalid limit or offset (e.g. a negative value), it will be ignored.

**Info:** *For DBMS that do not support `LIMIT` and `OFFSET` (e.g. `MSSQL`), query builder will generate a SQL statement that emulates the `LIMIT/OFFSET behavior`.*
