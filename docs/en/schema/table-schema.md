# Table schema

Represents the metadata of a database table such as table name, column names, column types, etc.

## Retrieving schema name

You can retrieve the schema name for a given table using the `getSchemaName()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$schemaName = $tableSchema->getSchemaName();
```

## Retrieving all tables for schema

You can retrieve all table schemas for a given database using the `getTableSchemas()` method of `Schema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * When schema name is not set, so the default schema name will be used.
 * 
 * @var ConnectionInterface $db
 */
$schemas = $db->getSchema()->getTableSchemas();
```

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * When schema name is set, so the default schema name will be ignored.
 * When `refresh` is `true`, the table schema will be re-created even if it is found in the cache.
 * 
 * @var ConnectionInterface $db
 */
$schemas = $db->getSchema()->getTableSchemas('public', true);
```

> Note: When `refresh` is `true`, the table schema will be re-created even if it is found in the cache.
> If `$db->getSchema()->getTableSchemas()` return `[]`, then the table does not exist.

## Retrieving table schema

You can retrieve the table schema for a given table name using the `getTableSchema()` method of a database connection.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Shortcout for $db->getSchema()->getTableSchema('customer');
 * 
 * @var ConnectionInterface $db
 */
$tableSchema = $db->getTableSchema('customer');
```

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * When `refresh` is `true`, the table schema will be re-created even if it is found in the cache.
 * Shortcout for $db->getSchema()->getTableSchema('customer', true);
 * 
 * @var ConnectionInterface $db
 */
$tableSchema = $db->getTableSchema('customer', true);
```

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Check if table exists or not.
 *  
 * @var ConnectionInterface $db
 */
if ($db->getTableSchema('customer') !== null) {
    // table exists
    // ...your code here
} else {
    // table does not exist
    // ...your code here
}
```

> Note: If `$db->getTableSchema()` return `null`, then the table does not exist.

### Retrieving table name

You can retrieve the table name for a given table using the `getName()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$tableName = $tableSchema->getName();
```

### Retrieving table full name

You can retrieve the table full name for a given table using the `getFullName()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$tableFullName = $tableSchema->getFullName();
```

> Note: The full name includes the schema name prefix, if any. 
> That if the schema name is the same as the `Schema::defaultSchema`, the schema name won't be included.

### Retrieving comment

You can retrieve the comment for a given table using the `getComment()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$comment = $tableSchema->getComment();
```

### Retrieving primary key

You can retrieve the primary key for a given table using the `getPrimaryKey()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$primaryKey = $tableSchema->getPrimaryKey();
```

### Retrieving foreign keys

You can retrieve the foreign keys for a given table using the `getForeignKeys()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$foreignKeys = $tableSchema->getForeignKeys();
```

### Retrieving columns

You can retrieve the column metadata for a given table using the `getColumns()` method of `TableSchema` class.
The array keys are column names, and the array values are the corresponding `ColumnSchema` objects.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$columns = $tableSchema->getColumns();
```

### Retrieving column

You can retrieve the column metadata for a given table using the `getColumn()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$column = $tableSchema->getColumn('id');
```

### Retrieving column names

You can retrieve the column names for a given table using the `getColumnNames()` method of `TableSchema` class.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$columnNames = $tableSchema->getColumnNames();
```
