# Connecting with logger

[Yii DB](https://github.com/yiisoft/db) uses [PSR-3](https://www.php-fig.org/psr/psr-3/) for logging.
You can configure a logger that implements `Psr\Log\LoggerInterface::class` in the
[DI container](https://github.com/yiisoft/di).

In the following example, you configure [Yii Logging Library](https://github.com/yiisoft/log) with a
[file target](https://github.com/yiisoft/log-target-file).

Create a file `config/common/di/logger.php`:

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;

return [
    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([FileTarget::class]),
        ],
    ],
];
```

Depending on used DBMS, create a file with database connection configuration. For example, when using PostgreSQL, it 
will be `config/common/di/db-pgsql.php`:

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
        'setLogger()' => [Reference::to(LoggerInterface::class)],        
    ],
];
```

For other DBMS refer to ["Create connecton"](/docs/en/README.md#create-connection) section.
