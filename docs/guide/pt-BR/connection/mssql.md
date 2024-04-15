# Conectando MSSQL

Para configurar [Yii DB MSSQL](https://github.com/yiisoft/db-mssql) com [contêiner DI](https://github.com/yiisoft/di)
você precisa criar o arquivo de configuração `config/common/di/db-mssql.php`:

```php
use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Profiler\ProfilerInterface;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-mssql']['dsn'],
                $params['yiisoft/db-mssql']['username'],
                $params['yiisoft/db-mssql']['password'],
            ),
        ],
    ],
];
```

Crie um arquivo `config/common/params.php` para parâmetros `common`.

```php
use Yiisoft\Db\Mssql\Dsn;

return [
    'yiisoft/db-mssql' => [
        'dsn' => (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Para configurar a conexão sem [contêiner DI](https://github.com/yiisoft/di),
você precisa seguir estas etapas:

```php
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Db\Mssql\Dsn;

// Dsn.
$dsn = (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
