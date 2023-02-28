## Create Connection with di container for Mssql Server

To configure [Yii Db Mssql](https://github.com/yiisoft/db-mssql) with [di container](https://github.com/yiisoft/di) you need to create a configuration file.

Create a file `config/common/di/db-mssql.php` for Mssql:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Profiler\ProfilerInterface;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver(
                $params['yiisoft/db-mssql']['dsn'],
                $params['yiisoft/db-mssql']['username'],
                $params['yiisoft/db-mssql']['password'],
            ),
        ],
    ],
];
```

Create a file `config/common/params.php` for `common` parameters:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Mssql\Dsn;

return [
    'yiisoft/db-mssql' => [
        'dsn' => (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ]
]
```

