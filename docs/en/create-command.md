## Create command object

Once you have a database connection instance, you can execute a SQL query by taking the following steps:

1.- Create a command object by calling `Yiisoft\Db\Connection\ConnectionInterface::createCommand()` with a plain SQL query.
2.- Bind parameters (optional);
3.- Call one of the SQL execution methods in `Yiisoft\Db\Command\CommandInterface` to execute the SQL statement.

The following example shows various ways of fetching data from a database:

Example 1: Fetching all rows from a table using `Yiisoft\Db\Command\CommandInterface::queryAll()`, return array all rows of the query result. Each array element is an array representing a row of data. Empty array is returned if the query results in nothing.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM customer')->queryAll();
```

The result of the above example is:

```php
array(3) {
  [0]=>
  array(6) {
    ["id"]=>
    string(1) "1"
    ["email"]=>
    string(17) "user1@example.com"
    ["name"]=>
    string(5) "user1"
    ["address"]=>
    string(8) "address1"
    ["status"]=>
    string(1) "1"
    ["profile_id"]=>
    string(1) "1"
  }
  [1]=>
  array(6) {
    ["id"]=>
    string(1) "2"
    ["email"]=>
    string(17) "user2@example.com"
    ["name"]=>
    string(5) "user2"
    ["address"]=>
    string(8) "address2"
    ["status"]=>
    string(1) "1"
    ["profile_id"]=>
    NULL
  }
  [2]=>
  array(6) {
    ["id"]=>
    string(1) "3"
    ["email"]=>
    string(17) "user3@example.com"
    ["name"]=>
    string(5) "user3"
    ["address"]=>
    string(8) "address3"
    ["status"]=>
    string(1) "2"
    ["profile_id"]=>
    string(1) "2"
  }
}
```


Example 2: Fetching a single row from a table using `Yiisoft\Db\Command\CommandInterface::queryOne()`, return array the first row (in terms of an array) of the query result. Null is returned if the query results in nothing.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM customer')->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "1"
  ["email"]=>
  string(17) "user1@example.com"
  ["name"]=>
  string(5) "user1"
  ["address"]=>
  string(8) "address1"
  ["status"]=>
  string(1) "1"
  ["profile_id"]=>
  string(1) "1"
}
```

Example 3: Fetching a single column from a table using `Yiisoft\Db\Command\CommandInterface::queryColumn()`, return array the values of the first column in the query result. An empty array is returned if the query results in nothing.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->queryColumn();
```

The result of the above example is:

```php
array(3) {
  [0]=>
  string(1) "1"
  [1]=>
  string(1) "2"
  [2]=>
  string(1) "3"
}
```

Example 4: Fetching a single value from a table using `Yiisoft\Db\Command\CommandInterface::queryScalar()`, return the value of the first column in the first row of the query result. False is returned if there is no value.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->queryScalar();
```

The result of the above example is:

```php
string(1) "1"
```

Example 5: Fetching a single row using `Yiisoft\Db\Command\CommandInterface::query()`, return `Yiisoft\Db\DataReader\DataReaderInterface` object. You can iterate over the returned object to get the data.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer')->query();

foreach ($command as $row) {
    // do something with $row
}
```

The result of the above example is:

```php
object(Yiisoft\Db\Query\Data\DataReader)#4710 (2) {
  ["index":"Yiisoft\Db\Query\Data\DataReader":private]=>
  int(-1)
  ["row":"Yiisoft\Db\Query\Data\DataReader":private]=>
  uninitialized(mixed)
  ["statement":"Yiisoft\Db\Query\Data\DataReader":private]=>
  object(PDOStatement)#4711 (1) {
    ["queryString"]=>
    string(22) "SELECT * FROM customer"
  }
}
```

## Binding parameters

When creating a DB command from a SQL with parameters, you should almost always use the approach of binding parameters to prevent SQL injection attacks. You can bind parameters to a SQL statement by using named placeholders or question mark placeholders. Named placeholders are of the form `:name` and question mark placeholders are of the form `?`. The following example shows how to bind parameters to a SQL statement:

Example 1: Binding parameters using named placeholders:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer WHERE id=:id');
$command->bindValue(':id', 1);
$command->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "1"
  ["email"]=>
  string(17) "user1@example.com"
  ["name"]=>
  string(5) "user1"
  ["address"]=>
  string(8) "address1"
  ["status"]=>
  string(1) "1"
  ["profile_id"]=>
  string(1) "1"
}
```

Example 2: Binding parameters using question mark placeholders:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer WHERE id=?');
$command->bindValue(1, 1);
$command->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "1"
  ["email"]=>
  string(17) "user1@example.com"
  ["name"]=>
  string(5) "user1"
  ["address"]=>
  string(8) "address1"
  ["status"]=>
  string(1) "1"
  ["profile_id"]=>
  string(1) "1"
}
```

In the SQL statement, you can embed one or multiple parameter placeholders (e.g. :id in the above example). A parameter placeholder should be a string starting with a colon. You may then call one of the following parameter binding methods to bind the parameter values:

1. `Yiisoft\Db\Command\CommandInterface::bindValue()` bind a single parameter value.
2. `Yiisoft\Db\Command\CommandInterface::bindValues()` bind multiple parameter values in one call.
3. `Yiisoft\Db\Command\CommandInterface::bindParam()` similar to bindValue() but also support binding parameter references.

The following example shows how to bind parameters using the above three methods:

Example 1: Binding parameters using `Yiisoft\Db\Command\CommandInterface::bindValue()`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer WHERE id=:id');
$command->bindValue(':id', 1);
$command->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "1"
  ["email"]=>
  string(17) "user1@example.com"
  ["name"]=>
  string(5) "user1"
  ["address"]=>
  string(8) "address1"
  ["status"]=>
  string(1) "1"
  ["profile_id"]=>
  string(1) "1"
}
```

Example 2: Binding parameters using `Yiisoft\Db\Command\CommandInterface::bindValues()`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer WHERE id=:id AND name=:name');
$command->bindValues([':id' => 3, ':name' => 'user3']);
$command->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "3"
  ["email"]=>
  string(17) "user3@example.com"
  ["name"]=>
  string(5) "user3"
  ["address"]=>
  string(8) "address3"
  ["status"]=>
  string(1) "2"
  ["profile_id"]=>
  string(1) "2"
}
```

Example 3: Binding parameters using `Yiisoft\Db\Command\CommandInterface::bindParam()`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM customer WHERE id=:id AND name=:name');
$id = 2;
$name = 'user2';
$command->bindParam(':id', $id);
$command->bindParam(':name', $name);
$command->queryOne();
```

The result of the above example is:

```php
array(6) {
  ["id"]=>
  string(1) "2"
  ["email"]=>
  string(17) "user2@example.com"
  ["name"]=>
  string(5) "user2"
  ["address"]=>
  string(8) "address2"
  ["status"]=>
  string(1) "1"
  ["profile_id"]=>
  NULL
}
``







