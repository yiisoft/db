# Call one of the SQL execute method to execute the command

The **query methods** introduced in the [Create a command with a plain SQL query](create-command.md) all deal with SELECT queries which fetch data from databases. For queries that do not bring back data, you should call the `Yiisoft\Db\Command\CommandInterface::execute()` method. The following example shows how to execute a non-SELECT query:

If the query is successful, `Yiisoft\Db\Command\CommandInterface::execute()` will return the number of rows affected by the SQL statement. If the sql does not affect any row, 0 will be returned. If the query fails, a `Yiisoft\Db\Exception\Exception` exception will be thrown.

For example, the following code executes a non-SELECT query and row count is `1`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('UPDATE customer SET status=2 WHERE id=1');
$command->execute();
```

Now, let's see what happens when executing a non-SELECT query and row count is `0`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('UPDATE customer SET status=2 WHERE id=1000');
$command->execute();
```

Finally, let's see what happens when executing a non-SELECT query and an exception is `thrown`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand('bad SQL')->execute();
```
