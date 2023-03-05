# Join

The `Yiisoft\Db\Query\Query::join()` method specifies the `JOIN` fragment of a SQL query.

For example.

```php
// ... LEFT JOIN `post` ON `post`.`user_id` = `user`.`id`
$query->join('LEFT JOIN', 'post', 'post.user_id = user.id');
```

The `Yiisoft\Db\Query\Query::join()` method takes four parameters:

- `type`: join type, e.g., `INNER JOIN`, `LEFT JOIN`.
- `table`: the name of the table to be joined.
- `on`: optional, the join condition, i.e., the `ON` fragment. Please refer to `Yiisoft\Db\Query\Query::where()` for details about specifying a condition.
**Note**: that the array syntax does not work for specifying a column based condition, e.g. `['user.id' => 'comment.userId']` will result in a condition where the user id must be equal to the string `comment.userId`. You should use the string syntax instead and specify the condition as `user.id = comment.userId`.
- `params`: optional, the parameters to be bound to the join condition.

You can use the following shortcut methods to specify `INNER JOIN`, `LEFT JOIN` and `RIGHT JOIN`, respectively.

- `Yiisoft\Db\Query\Query::innerJoin()`.
- `Yiisoft\Db\Query\Query::leftJoin()`.
- `Yiisoft\Db\Query\Query::rightJoin()`.

For example.

```php
$query->leftJoin('post', 'post.user_id = user.id');
```

To join with multiple tables, call the above join methods multiple times, once for each table.

Besides joining with tables, you can also join with sub-queries. To do so, specify the sub-queries to be joined as `Yiisoft\Db\Query\Query` objects.

For example.

```php
$subQuery = (new \yii\db\Query())->from('post');
$query->leftJoin(['u' => $subQuery], 'u.id = author_id');
```

In this case, you should put the sub-query in an array and use the array key to specify the alias.
