# Union

The `Yiisoft\Db\Query\Query::union()` method specifies the `UNION` fragment of a SQL query.

For example.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$query1 = (new Query($db))->select("id, category_id AS type, name")->from('{{%post}}')->limit(10);
$query2 = (new Query($db))->select('id, type, name')->from('{{%user}}')->limit(10);
$query1->union($query2);
```

You can call `Yiisoft\Db\Query\Query::union()` multiple times to append more `UNION` fragments.
