# Data Manipulation Language (DML) commands

DML is a set of SQL statements used to manipulate data in a database.

You can use the DML to perform the following operations:

- [Batch insert](#batch-insert)
- [Delete rows](#delete-rows)
- [Reset sequence](#reset-sequence)
- [Update](#update)
- [Upsert](#upsert)

## Batch insert

To insert multiple rows into a table, you can use the `Yiisoft\Db\Command\CommandInterface::batchInsert()` method:

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

To delete rows from a table, you can use the `Yiisoft\Db\Command\CommandInterface::delete()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->delete('{{%customer}}', ['id' => 1])->execute();
```

## Reset sequence

To reset the sequence of a table, you can use the `Yiisoft\Db\Command\CommandInterface::resetSequence()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->resetSequence('{{%customer}}', 1)->execute();
```

## Insert

To insert a row to a table, you can use the `Yiisoft\Db\Command\CommandInterface::insert()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insert('{{%customer}}', ['name' => 'John', 'age' => 18])->execute();
```

## Update

To update rows in a table, you can use the `Yiisoft\Db\Command\CommandInterface::update()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->update('{{%customer}}', ['status' => 2], ['id' > 1])->execute();
```

## Upsert

To atomically update existing rows and insert non-existing ones,
you can use the `Yiisoft\Db\Command\CommandInterface::upsert()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;

/** @var ConnectionInterface $db */
$db->createCommand()->upsert(
    'pages',
    [
        'name' => 'Front page',
        'url' => 'https://example.com/', // URL is unique
        'visits' => 0,
    ],
    updateColumns: [
        'visits' => new Expression('visits + 1'),
    ],
    params: $params,
)->execute();
```
