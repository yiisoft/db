# Connecting MsSQL

To configure [Yii Db Mssql](https://github.com/yiisoft/db-mssql) without [di container](https://github.com/yiisoft/di), you need to follow the following steps.

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Mssql\PDODriver;

// Dsn.
$dsn = (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Cache psr-6 implementation.
$schemaCache = new SchemaCache($cache);

// PDO driver.
$pdoDriver = new PDODriver($dsn, 'user', 'password'); 

// Connection.
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```
