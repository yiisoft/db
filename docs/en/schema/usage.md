# Reading database schema

Yii DB provides a way to inspect the metadata of a database, such as table names, column names, etc. You can do it
via schema objects.

## Get tables available

To get schemas for all tables available, you can use the following code:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$schemas = $db->getSchema()->getTableSchemas();
foreach ($schemas as $schema) {
    echo $schema->getFullName();
}
```

If you want to get tables from a certain database schema only:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * When schema name is set, the default schema name will be ignored.
 * 
 * @var ConnectionInterface $db
 */
$schemas = $db->getSchema()->getTableSchemas('public', true);
foreach ($schemas as $schema) {
    echo $schema->getFullName();
}
```

> Note: When `refresh` is `true`, the table schema will be re-created even if it's found in the cache.

## Inspect a table

To obtain a schema for a certain table, use the following code:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
```

If no table exists, the method returns `null` so to check if table exists you can do:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
if ($db->getTableSchema('customer') === null) {
    // there is no "customer" table
}
```

> Note: `getTableSchema()` may return cached schema information. If you need to be sure that the information is
> up to date, pass `true` as a second argument.

Having a table schema, you can get various info about the table:

```php
<?php

declare(strict_types=1);

use \Yiisoft\Db\Schema\TableSchemaInterface;

/** @var TableSchemaInterface $tableSchema */

echo 'To create ' . $tableSchema->getFullName() . " use the following SQL:\n";
echo $tableSchema->getCreateSql(); 
```

In the full name is a table name prefixed by database schema.
If the schema name is the same as the default schema, the full name won't include the schema name.

### Retrieving column schemas

You can retrieve the column metadata for a given table using either the `getColumns()` method or `getColumn()` method
of `TableSchema` class:


```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$columns = $tableSchema->getColumns();
foreach ($columns as $name => $column) {
    echo $name . ' (' . $column->getDbType() . ')';
}

// or a single column by name

$column = $tableSchema->getColumn('id');
echo 'id (' . $column->getDbType() . ')';
```

In either case you get instance or instances
or `ColumnSchemaInterface` that you can use to get all the information about the column.

