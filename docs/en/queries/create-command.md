# Create a command with a plain SQL query

To create a command with a plain **SQL query**, you can use the `Yiisoft\Db\Connection\ConnectionInterface::createCommand()` method.

The following example shows how to create a command.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM customer');
```

## Fetching Data

To **fetch data** from a **table**, you can use the `Yiisoft\Db\Command\CommandInterface::queryAll()`, `Yiisoft\Db\Command\CommandInterface::queryOne()`, `Yiisoft\Db\Command\CommandInterface::queryColumn()`, `Yiisoft\Db\Command\CommandInterface::queryScalar()` and `Yiisoft\Db\Command\CommandInterface::query()`.

**Note:** *To preserve precision, the data fetched from databases are all represented as strings, even if the corresponding database column types are numerical. You may need to use type conversion to convert them into the corresponding PHP types.*

### Query all

Returns an array of all rows in the result set. Each array element is an array representing a row of data, with the array keys as column names. An empty array is returned if the query results in nothing.

For example, the following code fetches all rows from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM customer')->queryAll();
```

The result of the above example is.

```php
[
    [
        'id' => '1',
        'email' => 'user1@example.com',
        'name' => 'user1',
        'address' => 'address1',
        'status' => '1',
        'profile_id' => '1',
    ],
    [
        'id' => '2',
        'email' => 'user2@example.com'
        'name' => 'user2',
        'address' => 'address2',
        'status' => '1',
        'profile_id' => null,
    ],
    [
        'id' => '3',
        'email' => 'user3@example.com',
        'name' => 'user3',
        'address' => 'address3',
        'status' => '2',
        'profile_id' => '2',
    ],
]
```

### Query one

Returns a single row of data. The return value is an array representing the first row of the query result. An `null` is returned if the query results in nothing.

For example, the following code fetches the first row from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM customer')->queryOne();
```

The result of the above example is.

```php
[
    'id' => '1',
    'email' => 'user1@example.com',
    'name' => 'user1',
    'address' => 'address1',
    'status' => '1',
    'profile_id' => '1',
]
```

### Query column

Returns the values of the first column in the query result. An empty array is returned if the query results in nothing.

For example, the following code fetches the values of the first column from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->queryColumn();
```

The result of the above example is.

```php
[
    '1',
    '2',
    '3',
]
```

### Query scalar

Returns the value of the first column in the first row of the query result. `false` is returned if there is no value.

For example, the following code fetches the value of the first column from the first row from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->queryScalar();
```

The result of the above example is.

```php
'1'
```

### Query

Returns a `Yiisoft\Db\DataReader\DataReaderInterface` object for traversing the rows in the result set.

For example, the following code fetches all rows from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->query();

foreach ($command as $row) {
    // do something with $row
}
```

The result of the above example is.

```php
Yiisoft\Db\Query\Data\DataReader#4710
(
    [Yiisoft\Db\Query\Data\DataReader:index] => -1
    [Yiisoft\Db\Query\Data\DataReader:statement] => PDOStatement#4711
    (
        [queryString] => 'SELECT * FROM customer'
    )
)
```
