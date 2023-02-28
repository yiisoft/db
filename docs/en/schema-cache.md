## Configuring schema cache

 The [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php) is used to `cache` database schema information.
 
 The [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) retrieves information about the database schema from the database server and stores it in the `cache` for faster access. When the [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) needs to retrieve information about the database schema, it first checks the `cache` using the [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php). If the information is not in the `cache`, the [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) retrieves it from the database server and stores it in the `cache` using the [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php).

 For configuration of [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php) you can do it in two ways, the first is configure [psr-16](https://github.com/php-fig/simple-cache) cache in the [di container](https://github.com/yiisoft/di) and it is configured automatically by autowired in the application controller, the second is configure it manually in the configuration file.

### Configuration file with autowired cache

Create a file `config/common/di/psr16.php` for cache:

```php
<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\File\FileCache;

/** @var array $params */

return [
    CacheInterface::class => [
        'class' => FileCache::class,
        '__construct()' => [
            'path' => __DIR__ . '/../../runtime/cache',
        ],
    ],
];
```

### Configuration file with manual cache configuration

Create a file `config/common/di/db-schema-cache.php` for cache:

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        '__construct()' => [
            new FileCache(__DIR__ . '/../../runtime/cache'),
        ],
    ],
];
```


