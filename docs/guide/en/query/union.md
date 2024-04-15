# Union

The `Yiisoft\Db\Query\Query::union()` method specifies the `UNION` fragment of a SQL query.

For example:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$query1 = (new Query($db))->select("id, category_id AS type, name")->from('{{%post}}')->limit(10);
$query2 = (new Query($db))->select('id, type, name')->from('{{%user}}')->limit(10);
$query1->union($query2);
```

Further calls to `Yiisoft\Db\Query\Query::union()` will append other `UNION` fragments.
