# Connecting MySQL/MariaDb

To configure [Yii Db Mysql/MariaDb](https://github.com/yiisoft/db-mysql) without [di container](https://github.com/yiisoft/di), you need to follow the following steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\ConnectionPDO;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Mysql\PDODriver;

// Dsn.
$dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache psr-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn, 'user', 'password'); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
