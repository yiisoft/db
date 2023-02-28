## Create Connection with di container for Sqlite Server

To configure [Yii Db Sqlite](https://github.com/yiisoft/db-pgsql) with [di container](https://github.com/yiisoft/di) you need to create a configuration file.

Create a file `config/common/di/db-sqlite.php` for Sqlite:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\ConnectionPDO;
use Yiisoft\Db\Sqlite\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver($params['yiisoft/db-sqlite']['dsn']),
        ]
    ]
];
```

Create a file `config/common/params.php` for `common` parameters:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Sqlite\Dsn;

return [
    'yiisoft/db-sqlite' => [
        'dsn' => (new Dsn('sqlite', dirname(__DIR__, 2) . '/resources/database/sqlite.db'))->__toString(),
    ]
];
```

