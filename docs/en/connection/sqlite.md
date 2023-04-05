# Connecting SQLite

To configure [Yii DB SQLite](https://github.com/yiisoft/db-pgsql) with
a [DI container](https://github.com/yiisoft/di), you need to create `config/common/di/db-sqlite.php` configuration file.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\ConnectionPDO;
use Yiisoft\Db\Sqlite\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver($params['yiisoft/db-sqlite']['dsn']),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Sqlite\Dsn;

return [
    'yiisoft/db-sqlite' => [
        'dsn' => (new Dsn('sqlite', dirname(__DIR__, 2) . '/resources/database/sqlite.db'))->__toString(),
    ],
];
```

To configure without [DI container](https://github.com/yiisoft/di), you need to follow these steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\ConnectionPDO;
use Yiisoft\Db\Sqlite\Dsn;
use Yiisoft\Db\Sqlite\PDODriver;

// Dsn.
$dsn = (new Dsn('sqlite', 'memory'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache PSR-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
