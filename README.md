<p align="center" style="text-align: center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="YiiFramework">
    </a>
    <h1 align="center">Yii DataBase Library</h1>
</p>

Yii is an open-source PHP framework for developing web applications. Yii Database Library is a database extension for the [Yii Framework] that provides a set of database utilities and a database-agnostic, [ActiveRecord] implementation for working with databases in Yii applications.

The package contains a number of classes that can be used to connect to and query different types of databases, such as [MariaDB], [MSSQL], [MySQL], [Oracle], [PostgreSQL], and [SQLite]. It also provides an [ActiveRecord] implementation that allows you to interact with database tables using objects, similar to the way you would use ORM (Object-Relational Mapping) frameworks like Doctrine or Hibernate.

Using the Yii Database Library, you can perform common database tasks such as creating, reading, updating, and deleting records in a database table, as well as executing raw SQL queries. The package is designed to be flexible and can be easily extended to support additional database types or to customize the way it interacts with databases.

It is used in [Yii Framework] but can be used separately.

[ActiveRecord]: https://github.com/yiisoft/active-record
[MariaDB]: https://mariadb.org
[MSSQL]: https://www.microsoft.com/sql-server
[MySQL]: https://www.mysql.com
[Oracle]: https://www.oracle.com/database
[PostgreSQL]: https://www.postgresql.org
[SQLite]: https://www.sqlite.org
[Yii Framework]: https://www.yiiframework.com

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db/v/stable.png)](https://packagist.org/packages/yiisoft/db)
[![Total Downloads](https://poser.pugx.org/yiisoft/db/downloads.png)](https://packagist.org/packages/yiisoft/db)
[![Build status](https://github.com/yiisoft/db/workflows/build/badge.svg)](https://github.com/yiisoft/db/actions?query=workflow%3Abuild)
[![codecov](https://codecov.io/gh/yiisoft/db/branch/master/graph/badge.svg?token=pRr4gci2qj)](https://codecov.io/gh/yiisoft/db)
[![static analysis](https://github.com/yiisoft/db/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db/actions/workflows/static.yml)
[![type-coverage](https://shepherd.dev/github/yiisoft/db/coverage.svg)](https://shepherd.dev/github/yiisoft/db)

## Usage 

For use read the [docs](/docs/en/getting-started.md).

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

### Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

### License

The Yii DataBase Library is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
