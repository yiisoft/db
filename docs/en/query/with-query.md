# With query

The `\Yiisoft\Db\Query\Query::withQuery()` method specifies the `WITH` prefix of a SQL query.
You can use it instead of sub-query for more readability and some unique features (recursive CTE).
[Read more at modern SQL](https://modern-sql.com/).

For example, this query will select all nested permissions of admin with their children recursively.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$initialQuery = (new Query($db))
    ->select(['parent', 'child'])
    ->from(['aic' => '{{%auth_item_child}}'])
    ->where(['parent' => 'admin']);

$recursiveQuery = (new Query($db))
    ->select(['aic.parent', 'aic.child'])
    ->from(['aic' => '{{%auth_item_child}}'])
    ->innerJoin('t1', 't1.child = aic.parent');

$mainQuery = (new Query($db))
    ->select(['parent', 'child'])
    ->from('{{%t1}}')
    ->withQuery($initialQuery->union($recursiveQuery), 't1', true);
```

`\Yiisoft\Db\Query\Query::withQuery()` can be called many times to prepend more CTEs to the main query.
Queries will be prepended in the same order as they attached.
If one of the queries is recursive, then the whole CTE becomes recursive.
