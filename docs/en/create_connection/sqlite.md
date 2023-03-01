# Connecting Sqlite

To configure [Yii Db Sqlite](https://github.com/yiisoft/db-pgsql) without [di container](https://github.com/yiisoft/di), you need to follow the following steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\ConnectionPDO;
use Yiisoft\Db\Sqlite\Dsn;
use Yiisoft\Db\Sqlite\PDODriver;

// Dsn.
$dsn = (new Dsn('sqlite', 'memory'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache psr-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
