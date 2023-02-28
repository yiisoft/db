## Create connection with di container and profiler

[Yii Db](https://github.com/yiisoft/db) used [Yii Profiler](https://github.com/yiisoft/profiler), a tool for collecting and analyzing database queries. This can be useful for debugging and optimizing database performance.


When we install [Yii Profiler](https://github.com/yiisoft/profiler) it is automatically configured in the [di container](https://github.com/yiisoft/di) for [Yii Config](https://github.com/yiisoft/config), so we can use it in our application.

Create a file `config/common/di/db-mssql.php` for Mssql:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Definitions\Reference;
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
        'setProfiler()' => [
            Reference::to(ProfilerInterface::class),
        ],
    ],
];
```
