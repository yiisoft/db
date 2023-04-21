<p align="center" style="text-align: center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="YiiFramework">
    </a>
    <h1 align="center">Yii Database</h1>
</p>

Yii Database is a framework-agnostic package to work with different types of databases,
such as [MariaDB], [MSSQL], [MySQL], [Oracle], [PostgreSQL], and [SQLite].

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

There is an [ActiveRecord] implementation built on top of it.
It allows interacting with database tables using objects,
similar to the way you would use ORM (Object-Relational Mapping) frameworks like Doctrine or Hibernate.

[ActiveRecord]: https://github.com/yiisoft/active-record
[MariaDB]: https://mariadb.org
[MSSQL]: https://www.microsoft.com/sql-server
[MySQL]: https://www.mysql.com
[Oracle]: https://www.oracle.com/database
[PostgreSQL]: https://www.postgresql.org
[SQLite]: https://www.sqlite.org

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db/v/stable.png)](https://packagist.org/packages/yiisoft/db)
[![Total Downloads](https://poser.pugx.org/yiisoft/db/downloads.png)](https://packagist.org/packages/yiisoft/db)
[![Build status](https://github.com/yiisoft/db/workflows/build/badge.svg)](https://github.com/yiisoft/db/actions?query=workflow%3Abuild)
[![codecov](https://codecov.io/gh/yiisoft/db/branch/master/graph/badge.svg?token=pRr4gci2qj)](https://codecov.io/gh/yiisoft/db)
[![static analysis](https://github.com/yiisoft/db/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db/actions/workflows/static.yml)
[![type-coverage](https://shepherd.dev/github/yiisoft/db/coverage.svg)](https://shepherd.dev/github/yiisoft/db)

## Usage 

[Check the documentation](/docs/en/README.md) to learn about usage.

## Support

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/db/68) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## Testing

[Check the testing instructions](/docs/en/testing.md) to learn about testing.

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii DataBase Library is free software. It's released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
