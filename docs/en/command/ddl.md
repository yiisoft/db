# Data Definition Language (DDL) commands

Data Definition Language (DDL) is a set of SQL statements that allows you to define the database structure.

DDL statements are used to create and change the database objects in a database. These objects can be tables, indexes, 
views, stored procedures, triggers, and so on.

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

## Alter column

To change an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::alterColumn()` method.

The following example shows how to change an existing column.

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

### Unique index

You can create a unique index by specifying the `unique` option in the `$indexType` parameter, it's supported by all
dbms.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('idx_test_name', 'test', 'id', 'UNIQUE')->execute();
```

### Clustered index

In `MSSQL`, you can create a clustered index by specifying the `clustered` option in the `$indexType` parameter.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('idx_test_name', 'test', 'id', 'CLUSTERED')->execute();
```

### Non-clustered index

In `MSSQL`, you can create a non-clustered index by specifying the `nonclustered` option in the `$indexType` parameter.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('idx_test_name', 'test', 'id', 'NONCLUSTERED')->execute();
```

### Fulltext index

In `Mysql` and `MariaDB`, you can create a fulltext index by specifying the `fulltext` option in the `$indexType`
parameter.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('idx_test_name', 'test', 'name', 'FULLTEXT')->execute();
```

### Bitmap index

In `Oracle`, you can create a bitmap index by specifying the `bitmap` option in the `$indexType` parameter.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand()->createIndex('idx_test_name', 'test', 'id', 'BITMAP')->execute();
```

## Creating a table

To create a table, you can use the `Yiisoft\Db\Command\CommandInterface::createTable()` method.

The following example shows how to create a table in an agnostic way.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createTable(
    '{{%customer}}',
     [
        'id' => 'pk',
        'name' => 'string(255) NOT NULL',
        'email' => 'string(255) NOT NULL',
        'status' => 'integer NOT NULL',
        'created_at' => 'datetime NOT NULL',
     ],
)->execute();
```

This results in the following SQL execution in `MSSQL`.

```sql
CREATE TABLE [customer] (
    [id] int IDENTITY PRIMARY KEY,
    [name] nvarchar(255) NOT NULL,
    [email] nvarchar(255) NOT NULL,
    [status] int NOT NULL,
    [created_at] datetime NOT NULL
)
```

This results in the following SQL execution in `MySQL`/`MariaDB`.

```sql
CREATE TABLE `customer` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `status` int(11) NOT NULL,
    `created_at` datetime(0) NOT NULL
)
```

This results in the following SQL execution in `Oracle`.

```sql
CREATE TABLE "customer" (
    "id" NUMBER(10) NOT NULL PRIMARY KEY,
    "name" VARCHAR2(255) NOT NULL,
    "email" VARCHAR2(255) NOT NULL,
    "status" NUMBER(10) NOT NULL,
    "created_at" TIMESTAMP NOT NULL
)
```

This results in the following SQL execution in `PostgreSQL`.

```sql
CREATE TABLE "customer" (
    "id" serial NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "email" varchar(255) NOT NULL,
    "status" integer NOT NULL,
    "created_at" timestamp(0) NOT NULL
)
```

This results in the following SQL execution in `SQLite`.

```sql
CREATE TABLE "customer" (
    `id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `status` integer NOT NULL,
    `created_at` datetime NOT NULL
)
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

## Drop column

To drop an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropColumn()` method.

The following example shows how to drop an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropColumn('{{%customer}}', 'profile_id')->execute();
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

## Drop a primary key

To drop an existing primary key, you can use the `Yiisoft\Db\Command\CommandInterface::dropPrimaryKey()` method.

The following example shows how to drop an existing primary key.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropPrimaryKey('pk-customer-id', '{{%customer}}')->execute();
```

## Drop a table

To drop a table, you can use the `Yiisoft\Db\Command\CommandInterface::dropTable()` method.

The following example shows how to drop a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropTable('{{%customer}}')->execute();
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

## Rename a column

To rename an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::renameColumn()` method.

The following example shows how to rename an existing column.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->renameColumn('{{%customer}}', 'profile_id', 'profile_id_new')->execute();
```

## Truncate a table

To truncate a table, you can use the `Yiisoft\Db\Command\CommandInterface::truncateTable()` method.

The following example shows how to truncate a table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->truncateTable('{{%customer}}')->execute();
```
