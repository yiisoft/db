<p align="center" style="text-align: center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Database</h1>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db/v)](https://packagist.org/packages/yiisoft/db)
[![Total Downloads](https://poser.pugx.org/yiisoft/db/downloads)](https://packagist.org/packages/yiisoft/db)
[![Build status](https://github.com/yiisoft/db/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/yiisoft/db/actions/workflows/build.yml?query=branch%3Amaster)
[![Code Coverage](https://codecov.io/gh/yiisoft/db/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/db)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db/master)
[![Static analysis](https://github.com/yiisoft/db/actions/workflows/static.yml/badge.svg?branch=master)](https://github.com/yiisoft/db/actions/workflows/static.yml?query=branch%3Amaster)
[![type-coverage](https://shepherd.dev/github/yiisoft/db/coverage.svg)](https://shepherd.dev/github/yiisoft/db)
[![psalm-level](https://shepherd.dev/github/yiisoft/db/level.svg)](https://shepherd.dev/github/yiisoft/db)

Framework-agnostic database abstraction layer that provides a set of classes to connect and interact with various
database management systems (DBMS) using a unified API, including a powerful query builder.

Available database drivers:

- [Yii DB MSSQL](https://github.com/yiisoft/db-mssql)
- [Yii DB MySQL](https://github.com/yiisoft/db-mysql) (also supports MariaDB)
- [Yii DB Oracle](https://github.com/yiisoft/db-oracle)
- [Yii DB PostgreSQL](https://github.com/yiisoft/db-pgsql)
- [Yii DB SQLite](https://github.com/yiisoft/db-sqlite)

Optional packages that provide additional functionality:

- [Yii Active Record](https://github.com/yiisoft/active-record) provides an object-oriented interface for working with database tables, similar to ORM 
  frameworks such as Doctrine or Hibernate.
- [Yii DB Migration](https://github.com/yiisoft/db-migration) allows you to manage database schema using migrations.

## Requirements

- PHP 8.1 - 8.5.
- `pdo` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org).

```shell
composer require yiisoft/db yiisoft/db-sqlite
```

> [!IMPORTANT]
> You must install `yiisoft/db` together with at least one database driver (e.g., `yiisoft/db-mysql`,
> `yiisoft/db-pgsql`, `yiisoft/db-sqlite`, etc.) to actually connect to a database.
>
> It also depends on [PSR-16: Common Interface for Caching Libraries](https://www.php-fig.org/psr/psr-16/) and requires
> the installation of [PSR-16 implementation](https://packagist.org/providers/psr/simple-cache-implementation).
> For example, [yiisoft/cache](https://github.com/yiisoft/cache) or one of the other
> [cache handlers](https://github.com/yiisoft/cache#cache-handlers).

## General Usage

To connect to a database, create an instance of the appropriate driver:

```php
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

/**
 * @var Psr\SimpleCache\CacheInterface $cache 
 */

// Creating a database connection
$db = new Connection(
    new Driver('sqlite:memory:'),
    new SchemaCache($cache),
);
```

You can then use the `$db` object to execute SQL queries, manage transactions, and perform other database operations.
Here are some examples:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * @var ConnectionInterface $db
 */

// Query builder
$rows = $db 
    ->select(['id', 'email'])  
    ->from('{{%user}}')  
    ->where(['last_name' => 'Smith'])  
    ->limit(10)  
    ->all();

// Insert
$db->createCommand()
    ->insert(
        '{{%user}}',
         [
            'email' => 'mike@example.com',
            'first_name' => 'Mike',
            'last_name' => 'Smith',
         ],
    )
    ->execute();

// Transaction
$db->transaction(
    static function (ConnectionInterface $db) {
        $db->createCommand()
            ->update('{{%user}}', ['status' => 'active'], ['id' => 1])
            ->execute();
        $db->createCommand()
            ->update('{{%profile}}', ['visibility' => 'public'], ['user_id' => 1])
            ->execute();
    }
)
```

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Database is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
