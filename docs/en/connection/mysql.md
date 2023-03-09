# Connecting MySQL, MariaDB

To configure [Yii DB MySQL/MariaDB](https://github.com/yiisoft/db-mysql) with a [DI container](https://github.com/yiisoft/di), you need to create a configuration file.

Create a file `config/common/di/db-mysql.php`.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\ConnectionPDO;
use Yiisoft\Db\Mysql\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver(
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
<?php

declare(strict_types=1);

use Yiisoft\Db\Mysql\Dsn;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Create a file `config/common/params.php` for `common` parameters with dsn unix socket.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Mysql\DsnSocket;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

To configure without [DI container](https://github.com/yiisoft/di), you need to follow the following steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\ConnectionPDO;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Mysql\PDODriver;

// Dsn.
$dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache PSR-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn, 'user', 'password'); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
