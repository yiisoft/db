# Connecting with logger

Yii DB uses [PSR-3](https://www.php-fig.org/psr/psr-3/) for logging.
You can configure a logger that implements `Psr\Log\LoggerInterface::class` in the
[DI container](https://github.com/yiisoft/di).

In the following example, you configure [Yii Logging Library](https://github.com/yiisoft/log) with a
[file target](https://github.com/yiisoft/log-target-file).

Create a file `config/common/di/logger.php`:

```php
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

For other DBMS refer to ["Create connecton"](/docs/guide/en/README.md#create-connection) section.

## Advanced usage of Logger

If you need to redefine logger messages or increase/decrease logging level:

1. Create a custom logger class
2. Use the context to detect type of the message in the "log" method

```php
declare(strict_types=1);

use Yiisoft\Db\Driver\Pdo\LogType;

class MyLogger extends ParentLoggerClass implements LoggerInterface
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($context['type'] === LogType::QUERY) {
            ... your logic here
        }    
    }
    
    // implements other methods of LoggerInterface without changes
}
```
