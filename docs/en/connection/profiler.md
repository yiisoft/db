# Connecting with profiler

Yii DB can be used with [Yii Profiler](https://github.com/yiisoft/profiler), a tool for collecting and analyzing
database queries useful for debugging and optimizing database performance.

When you install [Yii Profiler](https://github.com/yiisoft/profiler) it's automatically configured in the
[DI container](https://github.com/yiisoft/di) for [Yii Config](https://github.com/yiisoft/config),
so you can use it in your application right away.

The following describes how to configure it manually.

Create a file `config/common/di/profiler.php`.

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;

return [
    ProfilerInterface::class => [
        'class' => Profiler::class,
        '__construct()' => [
            Reference::to(LoggerInterface::class),
        ],
    ],
];
```

Depending on used DBMS, create a file with database connection configuration. For example, when using PostgreSQL, it
will be `config/common/di/db-pgsql.php`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Definitions\Reference;
use Yiisoft\Profiler\ProfilerInterface;

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
        'setProfiler()' => [Reference::to(ProfilerInterface::class)],
    ],
];
```

For other DBMS refer to ["Create connecton"](/docs/en/README.md#create-connection) section.
