# Bind parameters

There are two use-cases for binding parameters:

- When you do the same query with different data many times.
- When you need to insert values into SQL string to prevent **SQL injection attacks**.

You can do binding by using named placeholders (`:name`) or positional placeholders (`?`) in place of values and
pass values as a separate argument.

> Info: In many places in higher abstraction layers, like **query builder**, you often specify an
**array of values** and Yii DB does parameter binding for you, so there is no need to specify
parameters manually.

## Bind a single value

You can use `bindValue()` to bind a value to a parameter.
For example, the following code binds the value `42` to the named placeholder `:id`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id');
$command->bindValue(':id', 42);
$command->queryOne();
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

## Bind many values at once

`bindValues()` binds a list of values to the corresponding named placeholders in the SQL statement.

For example, the following code binds the values `3` and `user3` to the named placeholders `:id` and `:name`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id AND [[name]] = :name');
$command->bindValues([':id' => 3, ':name' => 'user3']);
$command->queryOne();
```

The result is:

```php
[
    'id' => '3',
    'email' => 'user3@example.com',
    'name' => 'user3',
    'address' => 'address3',
    'status' => '2',
    'profile_id' => '2',
]
```

## Bind a parameter

`bindParam()` binds a parameter to the specified **variable**.
The difference with `bindValue()` is that the variable may change. 

For example, the following code binds the value `2` and `user2` to the named placeholders `:id` and `:name` and
then it's changing the value:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id AND [[name]] = :name');
$id = 2;
$name = 'user2';
$command->bindParam(':id', $id);
$command->bindParam(':name', $name);
$user2 = $command->queryOne();

$id = 3;
$name = 'user3';
$user3 = $command->queryOne();
```

The results are:

```php
[
    'id' => '2',
    'email' => 'user2@example.com',
    'name' => 'user2',
    'address' => 'address2',
    'status' => '1',
    'profile_id' => '1',
]

[
    'id' => '3',
    'email' => 'user3@example.com',
    'name' => 'user3',
    'address' => 'address3',
    'status' => '2',
    'profile_id' => '2',
]
```
