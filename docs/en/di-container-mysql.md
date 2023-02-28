## Create Connection with di container for Mysql/MariaDb Server

To configure [Yii Db Mysql](https://github.com/yiisoft/db-mysql)/[Yii Db MariaDb](https://github.com/yiisoft/db-mysql) with [di container](https://github.com/yiisoft/di) you need to create a configuration file.

Create a file `config/common/di/db-mysql.php` for Mysql/MariaDb:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\ConnectionPDO;
use Yiisoft\Db\Mysql\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver(
                $params['yiisoft/db-mysql']['dsn'],
                $params['yiisoft/db-mysql']['username'],
                $params['yiisoft/db-mysql']['password'],
            ),
        ]
    ]
];
```

Create a file `config/common/params.php` for `common` parameters:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Mysql\Dsn;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ]
];
```

