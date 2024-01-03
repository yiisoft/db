# Connecting with logger

Yii DB uses [PSR-3](https://www.php-fig.org/psr/psr-3/) for logging.
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
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Definitions\Reference;

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
        'setLogger()' => [Reference::to(LoggerInterface::class)],        
    ],
];
```

For other DBMS refer to ["Create connecton"](/docs/en/README.md#create-connection) section.

## Advanced usage of Logger

If you needed re-define logger messages or increase(decrease) level of log:
1. Create custom logger class
2. Use context for detect type of message in method "log"

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Driver\Pdo\LogTypes;

class MyLogger extends ParentLoggerClass implements LoggerInterface
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($context[LogTypes::KEY] === LogTypes::TYPE_QUERY) {
            ... your logic here
        }    
    }
    
    // implements other methods of LoggerInterface without changes
}
```
