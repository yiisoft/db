# Connecting MSSQL

To configure [Yii DB MSSQL](https://github.com/yiisoft/db-mssql) with [DI container](https://github.com/yiisoft/di)
you need to create `config/common/di/db-mssql.php` configuration file:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\PdoConnection;
use Yiisoft\Db\Mssql\PdoDriver;
use Yiisoft\Profiler\ProfilerInterface;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => PdoConnection::class,
        '__construct()' => [
            'driver' => new PdoDriver(
                $params['yiisoft/db-mssql']['dsn'],
                $params['yiisoft/db-mssql']['username'],
                $params['yiisoft/db-mssql']['password'],
            ),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Mssql\Dsn;

return [
    'yiisoft/db-mssql' => [
        'dsn' => (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

To configure the connection without [DI container](https://github.com/yiisoft/di),
you need to follow these steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Mssql\PdoConnection;
use Yiisoft\Db\Mssql\PdoDriver;

// Dsn.
$dsn = (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache PSR-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PdoDriver($dsn, 'user', 'password'); 

// Connection.
$db = new PdoConnection($pdoDriver, $schemaCache);
```
