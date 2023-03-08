# Data Manipulation Language (DML) Commands

DML is a set of SQL statements that are used to manipulate data in a database.

The DML statements are used to perform the following operations.

## Batch insert

To insert many rows into a table, you can use the `Yiisoft\Db\Command\CommandInterface::batchInsert()` method.

The following example shows how to insert many rows into a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->batchInsert(
    '{{%customer}}',
    ['name', 'email'],
    [
        ['user1', 'email1@email.com'],
        ['user2', 'email2@email.com'],
        ['user3', 'email3@email.com'],
    ]
)->execute();
```

## Delete rows

To delete rows from a table, you can use the `Yiisoft\Db\Command\CommandInterface::delete()` method.

The following example shows how to delete rows from a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->delete('{{%customer}}', ['id' => 1])->execute();
```

## Reset sequence

To reset the sequence of a table, you can use the `Yiisoft\Db\Command\CommandInterface::resetSequence()` method.

The following example shows how to reset the sequence of a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->resetSequence('{{%customer}}', 1)->execute();
```

## Update

To update rows in a table, you can use the `Yiisoft\Db\Command\CommandInterface::update()` method.

The following example shows how to update rows in a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->update('{{%customer}}', ['status' => 2], ['id' > 1])->execute();
```

## Upsert

To upsert rows in a table, you can use the `Yiisoft\Db\Command\CommandInterface::upsert()` method.

The following example shows how to upsert rows in a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->upsert(
    'pages',
    [
        'name' => 'Front page',
        'url' => 'http://example.com/', // url is unique
        'visits' => 0,
    ],
    [
        'visits' => new \Yiisoft\Db\Expression\Expression('visits + 1'),
    ],
    $params,
)->execute();
```
