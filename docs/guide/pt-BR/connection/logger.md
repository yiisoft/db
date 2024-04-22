# Conectando com o logger

Yii DB usa [PSR-3](https://www.php-fig.org/psr/psr-3/) para registro.
Você pode configurar um criador de logs que implemente `Psr\Log\LoggerInterface::class` no
[Contêiner DI](https://github.com/yiisoft/di).

No exemplo a seguir, você configura [Yii Logging Library](https://github.com/yiisoft/log) com um
[arquivo como destino](https://github.com/yiisoft/log-target-file).

Crie um arquivo `config/common/di/logger.php`:

```php
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

Dependendo do SGBD utilizado, crie um arquivo com configuração da conexão com o banco de dados. Por exemplo, ao usar PostgreSQL,
será `config/common/di/db-pgsql.php`:

```php
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

Para outros DBMS, consulte a seção ["Criar conexão"](/docs/guide/pt-BR/README.md#criar-conexão).

## Uso avançado do Logger

Se você precisar redefinir mensagens do logger ou aumentar/diminuir o nível de registro:

1. Crie uma classe de logger personalizada
2. Use o contexto para detectar o tipo da mensagem no método “log”

```php
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
