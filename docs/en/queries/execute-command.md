# Execute a command

All methods introduced in the [Create a command and fetch data](create-command-fetch-data.md) deal with
`SELECT` queries which fetch data from databases.

For queries that don't return any data, you should call the `Yiisoft\Db\Command\CommandInterface::execute()` method:

- If the query is successful, `Yiisoft\Db\Command\CommandInterface::execute()` will return the number of rows affected 
by the command execution.
- If no rows were affected by the command execution, it returns `0`. 
- If the query fails, it throws a `Yiisoft\Db\Exception\Exception`.

Let's say there is a customer table, with row having `1` id is present and row having `1000` id is missing. And 
non-`SELECT` queries are being executed for both of them.

Then, in the following code affected row count will be `1`, because the row has been found and successfully updated:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand("UPDATE {{%customer}} SET [[name]] = 'John' WHERE [[id]] = 1");
$rowCount = $command->execute(); // 1
```

This query however affects no rows, because no rows were found by the given condition in `WHERE` clause:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand("UPDATE {{%customer}} SET [[name]] = 'John' WHERE [[id]] = 1000");
$rowCount = $command->execute(); // 0
```

In case of invalid SQL, the according exception will be thrown.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand('bad SQL')->execute();
```
