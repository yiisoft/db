# Execute a command

The methods introduced in the [Create a command and fetch data](create-command-fetch-data.md) all deals with
`SELECT` queries which fetch data from databases.

For queries that don't bring back data, you should call the `Yiisoft\Db\Command\CommandInterface::execute()` method.

If the query is successful, `Yiisoft\Db\Command\CommandInterface::execute()` will return the number of rows the command
execution affected.
It returns `0` if no rows the command affected no rows.
If the query fails, it throws a `Yiisoft\Db\Exception\Exception`.

For example, the following code executes a non-`SELECT` query and affected row count is `1`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('UPDATE {{%customer}} SET [[status]] = 2 WHERE [[id]] = 1');
$rowCount = $command->execute(); // 1
```

The following query and affects now rows.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('UPDATE {{%customer}} SET [[status]] = 2 WHERE [[id]] = 1000');
$rowCount = $command->execute(); // 0
```

In case of bad SQL, there's an exception.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand('bad SQL')->execute();
```
