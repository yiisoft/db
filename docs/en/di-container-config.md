## Di container configuration

To configure [Yii Db](https://github.com/yiisoft/db) with [di container](https://github.com/yiisoft/di) you need to create a configuration file.

### Configuration file

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
            setLogget(Reference::to(LoggerInterface::class)), // optional
            setProfiler(Reference::to(ProfilerInterface::class)), // optional
        ],
    ],
];
```

*Optional config:*

- `setLogget()` is a method for set your implementation [psr-3](https://www.php-fig.org/psr/psr-3/). You can configure a logger that implements `Psr\Log\LoggerInterface` in the [di container](https://github.com/yiisoft/di), for example [Yii Logging Library](https://github.com/yiisoft/log)
- `setProfiler()` is a method for set your implementation [Yii Profiler](https://github.com/yiisoft/profiler) that implements `Yiisoft\Profiler\ProfilerInterface`, a tool for collecting and analyzing database queries. This can be useful for debugging and optimizing database performance.

Create a file `config/common/params.php` for Mssql:

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

For configuration of `Yiisoft\Db\Cache\SchemaCache::class` you can do it in two ways, the first is configure [psr-16](https://github.com/php-fig/simple-cache) cache in the [di container](https://github.com/yiisoft/di) and it is configured automatically by autowired in the application controller, the second is configure it manually in the configuration file.

### Configuration file with autowired cache

Create a file `config/common/di/psr16.php` for cache:

```php
<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\File\FileCache;

/** @var array $params */

return [
    CacheInterface::class => [
        'class' => FileCache::class,
        '__construct()' => [
            'path' => __DIR__ . '/../../runtime/cache',
        ],
    ],
];
```

### Configuration file with manual cache configuration

Create a file `config/common/di/db-mssql.php` for Mssql:

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        '__construct()' => [
            new FileCache(__DIR__ . '/../../runtime/cache'),
        ],
    ],
];
```
