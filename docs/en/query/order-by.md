# Order by

The `\Yiisoft\Db\Query\Query::orderBy()` method specifies the `ORDER BY` fragment of a SQL query.

For example, the following code will generate a query that orders the results by the `id` column in ascending order and the `name` column in descending order.

```php
// ... ORDER BY `id` ASC, `name` DESC
$query->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC]);
```

In the above code, the array keys are column names while the array values are the corresponding order by directions. The PHP constant SORT_ASC specifies ascending sort and SORT_DESC descending sort.

If `ORDER BY` only involves simple column names, you can specify it using a string, just like you do when writing raw SQL statements. 

For example, the following code will generate a query that orders the results by the `id` column in ascending order and the `name` column in descending order.

```php
$query->orderBy('id ASC, name DESC');
```

**Note:** *You should use the array format if `ORDER BY` involves some DB expression*.

You can call `\Yiisoft\Db\Query\Query::addOrderBy()` to add more columns to the `ORDER BY` fragment.

For example, the following code will generate a query that orders the results by the `id` column in ascending order and the `name` column in descending order.

```php
$query->orderBy('id ASC')->addOrderBy('name DESC');
```
