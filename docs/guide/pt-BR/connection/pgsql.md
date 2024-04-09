# Conectando PostgreSQL

Para configurar o [Yii DB PostgreSQL](https://github.com/yiisoft/db-pgsql) com
um [contêiner DI](https://github.com/yiisoft/di), você precisa criar o arquivo de configuração `config/common/di/db-pgsql.php`:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;

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
    ],
];
```

Crie um arquivo `config/common/params.php` para parâmetros `common`.

```php
declare(strict_types=1);

use Yiisoft\Db\Pgsql\Dsn;

return [
    'yiisoft/db-pgsql' => [
        'dsn' => (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Para configurar sem um [contêiner DI](https://github.com/yiisoft/di), você precisa seguir estas etapas:

```php
declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;

// Dsn.
$dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
