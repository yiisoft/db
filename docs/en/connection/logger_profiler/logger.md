# Connecting with logger

[Yii DB](https://github.com/yiisoft/db) used [PSR-3](https://www.php-fig.org/psr/psr-3/) for logger. You can configure a logger that implements `Psr\Log\LoggerInterface::class` in the [DI container](https://github.com/yiisoft/di), for example [Yii Logging Library](https://github.com/yiisoft/log) and [Yii Logging Library - File Target](https://github.com/yiisoft/log-target-file).

Create a file `config/common/di/logger.php` for Logger:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;

/** @var array $params */

return [
    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([
                FileTarget::class,
            ]),
        ],
    ],
];
```

Create a file `config/common/di/db-pgsql.php`:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\ConnectionPDO;
use Yiisoft\Db\Pgsql\PDODriver;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver(
                $params['yiisoft/db-pgsql']['dsn'],
                $params['yiisoft/db-pgsql']['username'],
                $params['yiisoft/db-pgsql']['password'],
            ),
        ],
        'setLogger()' => [
            Reference::to(LoggerInterface::class),
        ],        
    ],
];
```
