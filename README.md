<p align="center" style="text-align: center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Database</h1>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db/v)](https://packagist.org/packages/yiisoft/db)
[![Total Downloads](https://poser.pugx.org/yiisoft/db/downloads)](https://packagist.org/packages/yiisoft/db)
[![Build status](https://github.com/yiisoft/db/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/db/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/yiisoft/db/branch/master/graph/badge.svg?token=pRr4gci2qj)](https://codecov.io/gh/yiisoft/db)
[![static analysis](https://github.com/yiisoft/db/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db/actions/workflows/static.yml)
[![type-coverage](https://shepherd.dev/github/yiisoft/db/coverage.svg)](https://shepherd.dev/github/yiisoft/db)

Yii Database is a framework-agnostic package to work with different types of databases,
such as [MariaDB](https://mariadb.org), [MySQL](https://www.mysql.com), [MSSQL](https://www.microsoft.com/sql-server), [Oracle](https://www.oracle.com/database), [PostgreSQL](https://www.postgresql.org) and [SQLite](https://www.sqlite.org).

Using the package, you can perform common database tasks such as creating, reading, updating, and deleting
records in a database table, as well as executing raw SQL queries.

```php
$rows = (new Query($db))  
    ->select(['id', 'email'])  
    ->from('{{%user}}')  
    ->where(['last_name' => 'Smith'])  
    ->limit(10)  
    ->all();
```

The package is designed to be flexible
and can be extended to support extra database types or to customize the way it interacts with databases.

There is an [ActiveRecord](https://github.com/yiisoft/active-record) implementation built on top of it.
It allows interacting with database tables using objects,
similar to the way you would use ORM (Object-Relational Mapping) frameworks like Doctrine or Hibernate.

## Requirements

- PHP 8.1 - 8.4.

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
