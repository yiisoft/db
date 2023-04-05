# Connecting PostgreSQL

To configure [Yii DB PostgreSQL](https://github.com/yiisoft/db-pgsql) with
a [DI container](https://github.com/yiisoft/di), you need to create `config/common/di/db-pgsql.php` configuration file:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-pgsql']['dsn'],
                $params['yiisoft/db-pgsql']['username'],
                $params['yiisoft/db-pgsql']['password'],
            ),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Pgsql\Dsn;

return [
    'yiisoft/db-pgsql' => [
        'dsn' => (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();,
        'username' => 'user',
        'password' => 'password',
    ],
];
```

To configure without a [DI container](https://github.com/yiisoft/di), you need to follow these steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;

// Dsn.
$dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache PSR-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
