# Connecting PostgreSQL

To configure [Yii Db Pgsql](https://github.com/yiisoft/db-pgsql) without [di container](https://github.com/yiisoft/di), you need to follow the following steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Pgsql\ConnectionPDO;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Pgsql\PDODriver;

// Dsn.
$dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache psr-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn, 'user', 'password'); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
