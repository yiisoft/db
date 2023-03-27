# Data Definition Language (DDL) commands

Data Definition Language (DDL) is a set of SQL statements to define the database structure.

DDL statements are used to create and change the database objects in a database.
These objects can be tables, indexes, views, stored procedures, triggers, and more.

## Tables

### Create a table

To create a table, you can use the `Yiisoft\Db\Command\CommandInterface::createTable()` method:

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

The library will automatically form and execute SQL suitable for the database used. For example, MSSQL connection
will execute the following SQL:

```sql
CREATE TABLE [customer] (
    [id] int IDENTITY PRIMARY KEY,
    [name] nvarchar(255) NOT NULL,
    [email] nvarchar(255) NOT NULL,
    [status] int NOT NULL,
    [created_at] datetime NOT NULL
)
```

And the following SQL will be executed in MySQL/MariaDB:

```sql
CREATE TABLE `customer` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `status` int(11) NOT NULL,
    `created_at` datetime(0) NOT NULL
)
```

### Drop a table

To drop a table, you can use the `Yiisoft\Db\Command\CommandInterface::dropTable()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropTable('{{%customer}}')->execute();
```

### Truncate a table

To clear all data of a table, you can use the `Yiisoft\Db\Command\CommandInterface::truncateTable()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->truncateTable('{{%customer}}')->execute();
```

## Columns

### Add a new column

To add a new column to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addColumn()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Column;

/** @var ConnectionInterface $db */
$db->createCommand()->addColumn(
    '{{%customer}}',
    'profile_id',
     new Column('integer')
)->execute();
```

### Alter a column

To change an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::alterColumn()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Column;

/** @var ConnectionInterface $db */
$db->createCommand()->alterColumn(
    '{{%customer}}',
    'profile_id',
    new Column('integer')->notNull()
)->execute();
```

### Rename a column

To rename an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::renameColumn()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->renameColumn('{{%customer}}', 'profile_id', 'profile_id_new')->execute();
```

### Drop a column

To drop an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropColumn()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropColumn('{{%customer}}', 'profile_id')->execute();
```

### Add default value to a column

To add a default value to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addDefaultValue()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addDefaultValue('{{%customer}}', 'df-customer-name', 'name', 'John Doe')->execute();
```

### Drop default value from a column

To drop a default value from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropDefaultValue()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropDefaultValue('{{%customer}}', 'df-customer-name')->execute();
```

## Keys

### Add a primary key

To add a primary key to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addPrimaryKey()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addPrimaryKey('{{%customer}}', 'pk-customer-id', 'id')->execute();
```

### Add a foreign key

To add a foreign key to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addForeignKey()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addForeignKey(
    '{{%customer}}',
    'fk-customer-profile_id',
    'profile_id',
    '{{%profile}}',
    'id',
    'CASCADE',
    'CASCADE'
)->execute();
```

### Drop a primary key

To drop an existing primary key, you can use the `Yiisoft\Db\Command\CommandInterface::dropPrimaryKey()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropPrimaryKey('{{%customer}}', 'pk-customer-id')->execute();
```

### Drop a foreign key

To drop an existing foreign key, you can use the `Yiisoft\Db\Command\CommandInterface::dropForeignKey()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropForeignKey('{{%customer}}', 'fk-customer-profile_id')->execute();
```

## Indexes

### Add an index

To add an index to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::createIndex()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('{{%customer}}', 'idx-customer-name', 'name')->execute();
```

### Drop an index

To drop an existing index, you can use the `Yiisoft\Db\Command\CommandInterface::dropIndex()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropIndex('{{%customer}}', 'idx-customer-name')->execute();
```

### Add unique index

You can create a unique index by specifying the `UNIQUE` option in the `$indexType` parameter, it's supported by all
DBMS:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'UNIQUE')->execute();
```

### Add clustered index

In MSSQL, you can create a clustered index by specifying the `CLUSTERED` option in the `$indexType` parameter:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'CLUSTERED')->execute();
```

### Add non-clustered index

In MSSQL, you can create a non-clustered index by specifying the `NONCLUSTERED` option in the `$indexType` parameter:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'NONCLUSTERED')->execute();
```

### Add fulltext index

In MySQL and MariaDB, you can create a fulltext index by specifying the `FULLTEXT` option in the `$indexType`
parameter.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'name', 'FULLTEXT')->execute();
```

### Add bitmap index

In `Oracle`, you can create a bitmap index by specifying the `BITMAP` option in the `$indexType` parameter:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'BITMAP')->execute();
```

## Constraints

### Add `UNIQUE` constraint

To add a unique constraint to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addUnique()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addUnique('{{%customer}}', 'uq-customer-name', 'name')->execute();
```

### Drop `UNIQUE` constraint

To drop a unique constraint from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropUnique()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropUnique('{{%customer}}', 'uq-customer-name')->execute();
```

### Add `CHECK` constraint

To add a `CHECK` constraint to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addCheck()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCheck('{{%customer}}', 'ck-customer-status', 'status > 0')->execute();
```

### Drop `CHECK` constraint

To drop an existing `CHECK` constraint, you can use the `Yiisoft\Db\Command\CommandInterface::dropCheck()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCheck('{{%customer}}', 'ck-customer-status')->execute();
```


## Comments

### Add comment to a column

To add a comment to an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::addCommentOnColumn()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnColumn('{{%customer}}', 'name', 'This is a customer name')->execute();
```

### Add comment to a table

To add a comment to an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::addCommentOnTable()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnTable('{{%customer}}', 'This is a customer table')->execute();
```

### Drop comment from a column

To drop a comment from an existing column, you can use the `Yiisoft\Db\Command\CommandInterface::dropCommentFromColumn()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromColumn('{{%customer}}', 'name')->execute();
```

### Drop comment from a table

To drop a comment from an existing table, you can use the `Yiisoft\Db\Command\CommandInterface::dropCommentFromTable()`
method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromTable('{{%customer}}')->execute();
```
