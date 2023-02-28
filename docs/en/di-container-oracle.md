## Create connection with di container for oracle server

To configure [Yii Db Oracle](https://github.com/yiisoft/db-oracle) with [di container](https://github.com/yiisoft/di) you need to create a configuration file.

Create a file `config/common/di/db-oracle.php`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\Oracle\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver(
                $params['yiisoft/db-oracle']['dsn'],
                $params['yiisoft/db-oracle']['username'],
                $params['yiisoft/db-oracle']['password'],
            ),
        ]
    ]
];
```

Create a file `config/common/params.php` for `common` parameters:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Oracle\Dsn;

return [
    'yiisoft/db-oracle' => [
        'dsn' => (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString(),
        'username' => 'user',
        'password' => 'password',
    ]
];
```

