# Join

The `Yiisoft\Db\Query\Query::join()` method specifies the `JOIN` fragment of a SQL query.

```php
$query->join('LEFT JOIN', 'post', 'post.user_id = user.id');
```

The relevant part of SQL is:

```sql
LEFT JOIN `post` ON `post`.`user_id` = `user`.`id`
```

The `Yiisoft\Db\Query\Query::join()` method takes four parameters:

- `type`: join type such as `INNER JOIN`, `LEFT JOIN`.
- `table`: the name of the table to join.
- `on`: optional join condition, that's the `ON` fragment.
  Refer to `Yiisoft\Db\Query\Query::where()` for details about specifying a condition.
  > **Note:** The array syntax doesn't work for specifying a column based condition.
  > `['user.id' => 'comment.userId']` will result in a condition
  > where the user id must be equal to the string `comment.userId`.
  > You should use the string syntax instead and specify the condition as `user.id = comment.userId`.
- `params`: optional parameters to bind to the join condition.

You can use the following shortcut methods to specify `INNER JOIN`, `LEFT JOIN` and `RIGHT JOIN`, respectively.

- `innerJoin()`.
- `leftJoin()`.
- `rightJoin()`.

For example:

```php
$query->leftJoin('post', 'post.user_id = user.id');
```

To join with many tables, call the join methods many times, once for each table.

Besides joining with tables, you can also join with subqueries.
To do so, specify the sub-queries to join as `Yiisoft\Db\Query\Query` objects.

For example:

```php
use Yiisoft\Db\Query\Query;

$subQuery = (new Query())->from('post');
$query->leftJoin(['u' => $subQuery], 'u.id = author_id');
```

In this case, you should put the subquery into array and use the array key to specify the alias.
