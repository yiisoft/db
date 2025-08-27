# Data Manipulation Language (DML) commands

DML is a set of SQL statements used to manipulate data in a database.

You can use the DML to perform the following operations:

- [Batch insert](#batch-insert)
- [Delete rows](#delete-rows)
- [Reset sequence](#reset-sequence)
- [Insert](#insert)
- [Update](#update)
- [Upsert](#upsert)

## Batch insert

To insert multiple rows into a table, you can use the `Yiisoft\Db\Command\CommandInterface::insertBatch()` method:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insertBatch(
    '{{%customer}}',
    [
        ['user1', 'email1@email.com'],
        ['user2', 'email2@email.com'],
        ['user3', 'email3@email.com'],
    ],
    ['name', 'email'],
)->execute();
```

It is possible to insert rows as associative arrays, where the keys are column names.

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insertBatch(
    '{{%customer}}',
    [
        ['name' => 'user1', 'email' => 'email1@email.com'],
        ['name' => 'user2', 'email' => 'email2@email.com'],
        ['name' => 'user3', 'email' => 'email3@email.com'],
    ],
)->execute();
```

## Delete rows

To delete rows from a table, you can use the `Yiisoft\Db\Command\CommandInterface::delete()` method:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->delete('{{%customer}}', ['id' => 1])->execute();
```

## Reset sequence

To reset the sequence of a table, you can use the `Yiisoft\Db\Command\CommandInterface::resetSequence()` method:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->resetSequence('{{%customer}}', 1)->execute();
```

## Insert

To insert a row to a table, you can use the `Yiisoft\Db\Command\CommandInterface::insert()` method:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insert('{{%customer}}', ['name' => 'John Doe', 'age' => 18])->execute();
```

## Update

To update rows in a table, you can use the `Yiisoft\Db\Command\CommandInterface::update()` method:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->update('{{%customer}}', ['status' => 2], ['id' > 1])->execute();
```

## Upsert

To atomically update existing rows and insert non-existing ones,
you can use the `Yiisoft\Db\Command\CommandInterface::upsert()` method:

```php
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

It also supports [function expressions](../expressions/functions.md) with multi-operands without values.
In this case the current column value will be used as one of the operands and the insert value as another operand.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;

/** @var ConnectionInterface $db */
$db->createCommand()->upsert(
    'products',
    [
        'name' => 'Product A',
        'sku' => 'A001', // SKU is unique
        'price' => 100,
        'stock' => 50,
        'tags' => ['new', 'sale'], // Array or JSON column
    ],
    updateColumns: [
        'tags' => new ArrayMerge(), // Merge the current tags with ['new', 'sale']
        'price' => new Greatest(), // Greater of current price and 100
        'stock' => new Least(), // Least of current stock and 50
    ],
)->execute();
```
