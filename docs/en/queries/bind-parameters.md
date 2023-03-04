# Binding parameters

When creating a **DB** command from a **SQL with parameters**, you should almost always use the approach of binding parameters to prevent **SQL injection attacks**. You can **bind parameters** to a **SQL statement** by using named placeholders or question mark placeholders. Named placeholders are of the form `:name` and question mark placeholders are of the form `?`. The following example shows how to **bind parameters** to a **SQL statement**.

**Info:** *Parameter binding is only used in places where values need to be inserted into strings that contain plain **SQL**. In many places in higher abstraction layers like **query builder** you often specify an **array of values** which will be transformed into **SQL**. In these places parameter binding is done by Yii internally, so there is no need to specify params manually.*

## Bind value

`BindValue()` binds a value to a parameter. It is recommended to use this method to bind parameter values to ensure the security of your application.

For example, the following code binds the value `1` to the named placeholder `:id`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id');
$command->bindValue(':id', 1);
$command->queryOne();
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

## Bind values

`BindValues()` binds a list of values to the corresponding named placeholders in the **SQL statement**. It is recommended to use this method to bind parameter values to ensure the security of your application.

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

The result of the above example is.

```php
[
    'id' => '3'
    'email' => 'user3@example.com'
    'name' => 'user3'
    'address' => 'address3'
    'status' => '2'
    'profile_id' => '2'
]
```

## Bind parameter

`bindParam()` binds a parameter to the specified variable name. It is recommended to use this method to bind parameter values to ensure the security of your application.

For example, the following code binds the value `2` and `user2` to the named placeholders `:id` and `:name`.

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
$command->queryOne();
```

The result of the above example is.

```php
[
    'id' => '2'
    'email' => 'user2@example.com'
    'name' => 'user2'
    'address' => 'address2'
    'status' => '1'
    'profile_id' => NULL
]
```
