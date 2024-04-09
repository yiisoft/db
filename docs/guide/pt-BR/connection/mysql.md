# Conectando MySQL, MariaDB

Para configurar [Yii DB MySQL/MariaDB](https://github.com/yiisoft/db-mysql) com
um [contêiner DI](https://github.com/yiisoft/di), você precisa criar o arquivo de configuração `config/common/di/db-mysql.php`:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-mysql']['dsn'],
                $params['yiisoft/db-mysql']['username'],
                $params['yiisoft/db-mysql']['password'],
            ),
        ],
    ],
];
```

Crie um arquivo `config/common/params.php` para parâmetros `common`.

```php
declare(strict_types=1);

use Yiisoft\Db\Mysql\Dsn;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Crie um arquivo `config/common/params.php` para parâmetros `common` com soquete unix DSN.

```php
declare(strict_types=1);

use Yiisoft\Db\Mysql\DsnSocket;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest'))->asString(),
        'username' => 'user',
        'password' => 'password',
    ],
];
```

Para configurar sem [contêiner DI](https://github.com/yiisoft/di), você precisa seguir estas etapas:

```php
declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;

// Dsn.
$dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($arrayCache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'user', 'password'); 

// Connection.
$db = new Connection($pdoDriver, $schemaCache);
```
