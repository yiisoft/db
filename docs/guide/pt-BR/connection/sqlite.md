# Conectando SQLite

Para configurar o [Yii DB SQLite](https://github.com/yiisoft/db-sqlite) com
um [contêiner DI](https://github.com/yiisoft/di), você precisa criar o arquivo de configuração `config/common/di/db-sqlite.php`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver($params['yiisoft/db-sqlite']['dsn']),
        ],
    ],
];
```

Crie um arquivo `config/common/params.php` para parâmetros `common`.

```php
declare(strict_types=1);

use Yiisoft\Db\Sqlite\Dsn;

return [
    'yiisoft/db-sqlite' => [
        'dsn' => (new Dsn('sqlite', dirname(__DIR__, 2) . '/resources/database/sqlite.db'))->__toString(),
    ],
];
```

Para configurar sem [contêiner DI](https://github.com/yiisoft/di), você precisa seguir estas etapas:

```php
declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Db\Sqlite\Dsn;

// Dsn.
$dsn = (new Dsn('sqlite', 'memory'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new Driver($dsn); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
