# Limit and offset

The `\Yiisoft\Db\Query\Query::limit()` and `\Yiisoft\Db\Query\Query::offset()` methods specify
the `LIMIT` and `OFFSET` fragments of a SQL query accordingly.

For example, the following code will build a query that will return only 10 records starting from the 20th one.

```php
$query->limit(10)->offset(20);
```

The relevant part of SQL is:

```sql
LIMIT 10 OFFSET 20
```

The query ignores invalid limit or offset such as a negative value.

> Note: For DBMS that don't support `LIMIT` and `OFFSET` such as `MSSQL`, query builder will generate a SQL 
> statement that emulates this behavior.
