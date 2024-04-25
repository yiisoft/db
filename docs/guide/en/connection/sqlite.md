# Connecting SQLite

To configure [Yii DB SQLite](https://github.com/yiisoft/db-sqlite) with
a [DI container](https://github.com/yiisoft/di), you need to create `config/common/di/db-sqlite.php` configuration file.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver($params['yiisoft/db-sqlite']['dsn']),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
use Yiisoft\Db\Sqlite\Dsn;

return [
    'yiisoft/db-sqlite' => [
        'dsn' => (new Dsn('sqlite', dirname(__DIR__, 2) . '/resources/database/sqlite.db'))->__toString(),
    ],
];
```

To configure without [DI container](https://github.com/yiisoft/di), you need to follow these steps:

```php
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Db\Sqlite\Dsn;

// Dsn.
$dsn = (new Dsn('sqlite', 'memory'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
