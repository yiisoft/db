# Union

O método `Yiisoft\Db\Query\Query::union()` especifica o fragmento `UNION` de uma consulta SQL.

Por exemplo:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$query1 = (new Query($db))->select("id, category_id AS type, name")->from('{{%post}}')->limit(10);
$query2 = (new Query($db))->select('id, type, name')->from('{{%user}}')->limit(10);
$query1->union($query2);
```

Outras chamadas para `Yiisoft\Db\Query\Query::union()` anexarão outros fragmentos `UNION`.
