## Create connection with di container and logger

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

Create a file `config/common/di/db-mssql.php`:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Definitions\Reference;

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
        'setLogger()' => [
            Reference::to(LoggerInterface::class),
        ],
    ],
];
