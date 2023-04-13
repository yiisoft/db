# Create a command and fetch data

To create a command, you can use the `Yiisoft\Db\Connection\ConnectionInterface::createCommand()` method:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM {{%customer}}');
```

In the command, there are different methods to **fetch data**:

- [queryAll()](#query-all)
- [queryOne()](#query-one)
- [queryColumn()](#query-column)
- [queryScalar()](#query-scalar)
- [query()](#query)

> Note: To preserve precision, all data fetched from databases in the string type, even if the corresponding 
> database column types are different, numerical for example.
> You may need to use type conversion to convert them into the corresponding PHP types.

### Query all

Returns an array of all rows in the result set.
Each array element is an array representing a row of data, with the array keys as column names.
It returns an empty array if the query results in nothing.

For example, the following code fetches all rows from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryAll();
```

The result is:

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

Returns a single row of data.
The return value is an array representing the first row of the query result.
It returns `null` if the query results in nothing.

For example, the following code fetches the first row from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryOne();
```

The result is:

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

Returns the values of the first column in the query result.
It returns an empty array if the query results in nothing.

For example, the following code fetches the values of the first column from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryColumn();
```

The result is:

```php
[
    '1',
    '2',
    '3',
]
```

### Query scalar

Returns the value of the first column in the first row of the query result.
It returns `false` if there is no value.

For example, the following code fetches the value of the first column from the first row from the `customer` table.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryScalar();
```

The result is:

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

$result = $db->createCommand('SELECT * FROM {{%customer}}')->query();

foreach ($result as $row) {
    // do something with $row
}
```
