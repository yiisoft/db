# Connecting Oracle

To configure [Yii DB Oracle](https://github.com/yiisoft/db-oracle) with [DI container](https://github.com/yiisoft/di),
you need to create `config/common/di/db-oracle.php` configuration file.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-oracle']['dsn'],
                $params['yiisoft/db-oracle']['username'],
                $params['yiisoft/db-oracle']['password'],
            ),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
use Yiisoft\Db\Oracle\Dsn;

return [
    'yiisoft/db-oracle' => [
        'dsn' => (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

To use more than one database connection you can
[use class-aliases](https://github.com/yiisoft/di#using-class-aliases-for-specific-configuration).

To configure without [DI container](https://github.com/yiisoft/di), you need to follow these steps:

```php
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Driver;
use Yiisoft\Db\Oracle\Dsn;

// Dsn.
$dsn = (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
