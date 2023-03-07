# Working with database

## Creating a table

To create a table, you can use the `Yiisoft\Db\Command\CommandInterface::createTable()` method.

The following example shows how to create a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createTable('{{%customer}}', [
    'id' => $db->getSchema()->createColumnSchemaBuilder('pk'),
    'email' => $db->getSchema()->createColumnSchemaBuilder('string')->notNull(),
    'name' => $db->getSchema()->createColumnSchemaBuilder('string')->notNull(),
    'address' => $db->getSchema()->createColumnSchemaBuilder('string'),
    'status' => $db->getSchema()->createColumnSchemaBuilder('smallint'),
    'profile_id' => $db->getSchema()->createColumnSchemaBuilder('integer'),
])->execute();
```

## Dropping a table

To drop a table, you can use the `Yiisoft\Db\Command\CommandInterface::dropTable()` method.

The following example shows how to drop a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropTable('{{%customer}}')->execute();
```

## Adding a new column

To add a new column to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addColumn()` method.

The following example shows how to add a new column to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addColumn(
    '{{%customer}}',
    'profile_id',
     $db->getSchema()->createColumnSchemaBuilder('integer')
)->execute();
```

## Dropping a column

To drop an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropColumn()` method.

The following example shows how to drop an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropColumn('{{%customer}}', 'profile_id')->execute();
```

## Renaming a column

To rename an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::renameColumn()` method.

The following example shows how to rename an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->renameColumn('{{%customer}}', 'profile_id', 'profile_id_new')->execute();
```

## Modifying a column

To modify an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::alterColumn()` method.

The following example shows how to modify an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->alterColumn(
    '{{%customer}}',
    'profile_id',
    $db->getSchema()->createColumnSchemaBuilder('integer')->notNull()
)->execute();
```

## Adding a primary key

To add a primary key to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addPrimaryKey()` method.

The following example shows how to add a primary key to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addPrimaryKey('pk-customer-id', '{{%customer}}', 'id')->execute();
```

## Dropping a primary key

To drop an existing primary key, you can use the `Yiisoft\Db\Command\CommandInterface::dropPrimaryKey()` method.

The following example shows how to drop an existing primary key.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropPrimaryKey('pk-customer-id', '{{%customer}}')->execute();
```

## Adding a foreign key

To add a foreign key to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addForeignKey()` method.

The following example shows how to add a foreign key to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addForeignKey(
    'fk-customer-profile_id',
    '{{%customer}}',
    'profile_id',
    '{{%profile}}',
    'id',
    'CASCADE',
    'CASCADE'
)->execute();
```

## Dropping a foreign key

To drop an existing foreign key, you can use the `Yiisoft\Db\Command\CommandInterface::dropForeignKey()` method.

The following example shows how to drop an existing foreign key.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropForeignKey('fk-customer-profile_id', '{{%customer}}')->execute();
```

## Adding an index

To add an index to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::createIndex()` method.

The following example shows how to add an index to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('idx-customer-name', '{{%customer}}', 'name')->execute();
```

## Dropping an index

To drop an existing index, you can use the `Yiisoft\Db\Command\CommandInterface::dropIndex()` method.

The following example shows how to drop an existing index.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropIndex('idx-customer-name', '{{%customer}}')->execute();
```

## Add `CHECK` constraint

To add a `CHECK` constraint to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addCheck()` method.

The following example shows how to add a `CHECK` constraint to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCheck('ck-customer-status', '{{%customer}}', 'status > 0')->execute();
```

## Drop `CHECK` constraint

To drop an existing `CHECK` constraint, you can use the `Yiisoft\Db\Command\CommandInterface::dropCheck()` method.

The following example shows how to drop an existing `CHECK` constraint.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCheck('ck-customer-status', '{{%customer}}')->execute();
```

## Add comment to table

To add a comment to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addCommentOnTable()` method.

The following example shows how to add a comment to an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnTable('{{%customer}}', 'This is a customer table')->execute();
```

## Drop comment from table

To drop a comment from an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::dropCommentFromTable()`
method.

The following example shows how to drop a comment from an existing table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromTable('{{%customer}}')->execute();
```

## Add comment to column

To add a comment to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addCommentOnColumn()`
method.

The following example shows how to add a comment to an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnColumn('{{%customer}}', 'name', 'This is a customer name')->execute();
```

## Drop comment from column

To drop a comment from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropCommentFromColumn()`
method.

The following example shows how to drop a comment from an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromColumn('{{%customer}}', 'name')->execute();
```

## Add default value to column

To add a default value to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addDefaultValue()`
method.

The following example shows how to add a default value to an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addDefaultValue('df-customer-name', '{{%customer}}', 'name', 'John Doe')->execute();
```

## Drop default value from column

To drop a default value from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropDefaultValue()`
method.

The following example shows how to drop a default value from an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropDefaultValue('df-customer-name', '{{%customer}}')->execute();
```

## Add `UNIQUE` constraint

To add a unique constraint to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addUnique()`
method.

The following example shows how to add a unique constraint to an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addUnique('uq-customer-name', '{{%customer}}', 'name')->execute();
```

## Drop `UNIQUE` constraint

To drop a unique constraint from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropUnique()`
method.

The following example shows how to drop a unique constraint from an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropUnique('uq-customer-name', '{{%customer}}')->execute();
```

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

## Delete

To delete rows from a table, you can use the `Yiisoft\Db\Command\CommandInterface::delete()` method.

The following example shows how to delete rows from a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->delete('{{%customer}}', ['id' => 1])->execute();
```

## Insert

To insert a row into a table, you can use the `Yiisoft\Db\Command\CommandInterface::insert()` method.

The following example shows how to insert a row into a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insert(
    '{{%customer}}',
    [
        'name' => 'user1',
        'email' => 'email1@email.com',
    ]
)->execute();
```

## Insert with returning pks

To insert a row into a table and return the primary key value, you can use the `Yiisoft\Db\Command\CommandInterface::insertWithReturningPks()`
method.

The following example shows how to insert a row into a table and return the primary key value.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$result = $db->createCommand()->insertWithReturningPks(
    '{{%customer}}',
    [
        'name' => 'user1',
        'email' => 'email1@email.com',
    ]
)->execute();

// $result array with primary keys
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
