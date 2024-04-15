# Conectando com o criador de perfil

O Yii DB pode ser usado com o [Yii Profiler](https://github.com/yiisoft/profiler), uma ferramenta para coletar e analisar
consultas de banco de dados úteis para depuração e otimização do desempenho do banco de dados.

Quando você instala o [Yii Profiler](https://github.com/yiisoft/profiler) ele é automaticamente configurado no
[Contêiner DI](https://github.com/yiisoft/di) para [Yii Config](https://github.com/yiisoft/config),
para que você possa usá-lo em seu aplicativo imediatamente.

O seguinte descreve como configurá-lo manualmente.

Crie um arquivo `config/common/di/profiler.php`.

```php
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

Dependendo do SGBD utilizado, crie um arquivo com configuração de conexão com o banco de dados. Por exemplo, ao usar PostgreSQL,
será `config/common/di/db-pgsql.php`:

```php
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

Para outros DBMS, consulte a seção ["Criar conexão"](/docs/guide/pt-BR/README.md#criar-conexão).
