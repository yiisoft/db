# Configuring schema cache

The information about the database schema that's needed for ORM comes from
[Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) that retrieves it from the
database server.

For faster access, [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) stores database
schema information in [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php).

When the [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) needs
retrieve information about the database schema, it first checks the cache.

You can configure [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php) to use
[PSR-16 cache implementation](https://github.com/php-fig/simple-cache) in two ways:

- Use [DI container](https://github.com/yiisoft/di) autowiring.
- Configure it manually.

Examples below use [yiisoft/cache](https://github.com/yiisoft/cache). Make sure you have installed it via [Composer](https://getcomposer.org)
using `composer require yiisoft/cache`.

## Autowired PSR-16 cache

This configuration is suitable if you want to use the same cache driver for the whole application.

Create a file `config/common/di/cache.php` for cache:

```php
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\File\FileCache;

/** @var array $params */

return [
    CacheInterface::class => [
        'class' => FileCache::class,
        '__construct()' => [
            'cachePath' => __DIR__ . '/../../runtime/cache',
        ],
    ],
];
```

The `SchemaCache` requires `CacheInterface` and DI container will automatically resolve it.

## Manual cache configuration

This configuration is suitable if you want to use a different cache driver for caching schema.

Create a file `config/common/di/db-schema-cache.php` for cache:

```php
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

## Disabling schema cache

You can disable schema caching by setting the `enabled` parameter to `false` in your application's `config/params.php`:

```php
return [
    // ...
    'yiisoft/db' => [
        'schema-cache' => [
            'enabled' => false,
        ],
    ],
];
```

Then use this parameter in your DI container configuration:

```php
use Yiisoft\Db\Cache\SchemaCache;

/** @var array $params */

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        'setEnabled()' => [$params['yiisoft/db']['schema-cache']['enabled']],
    ],
];
```
