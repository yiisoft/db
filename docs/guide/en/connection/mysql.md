# Connecting MySQL, MariaDB

To configure [Yii DB MySQL/MariaDB](https://github.com/yiisoft/db-mysql) with
a [DI container](https://github.com/yiisoft/di), you need to create `config/common/di/db-mysql.php` configuration file:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-mysql']['dsn'],
                $params['yiisoft/db-mysql']['username'],
                $params['yiisoft/db-mysql']['password'],
            ),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
use Yiisoft\Db\Mysql\Dsn;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Create a file `config/common/params.php` for `common` parameters with unix socket DSN.

```php
use Yiisoft\Db\Mysql\DsnSocket;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest'))->asString(),
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
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;

// Dsn.
$dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($arrayCache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
