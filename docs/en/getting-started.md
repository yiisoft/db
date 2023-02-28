## Getting Started

[Yii Db](https://github.com/yiisoft/db) is DAO (Data Access Object) layer for Yii 3.0. It provides a set of classes that help you access relational databases. It is designed to be flexible and extensible, so that it can be used with different databases and different database schemas. Its db-agnostic nature makes it easy to switch from one database to another.

Built on top of [PDO](https://www.php.net/manual/en/book.pdo.php), [Yii Db](https://github.com/yiisoft/db) provides an object-oriented API for accessing relational databases. It is the foundation for other more advanced database access methods, including [Query Builder](query-builder.md) and [Active Record](active-record.md).

When using [Yii Db](https://github.com/yiisoft/db), you mainly need to deal with plain SQLs and PHP arrays. As a result, it is the most efficient way to access databases. However, because SQL syntax may vary for different databases, using [Yii Db](https://github.com/yiisoft/db) also means you have to take extra effort to create a database-agnostic application.

In Yii 3.0, [Yii Db](https://github.com/yiisoft/db) supports the following databases out of the box:

1. [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) version 2017, 2019, 2022,
2. [MySQL](https://www.mysql.com/) version 5.7 - 8.0.
3. [MariaDB](https://mariadb.org/) version 10.4 - 10.9.
4. [Oracle](https://www.oracle.com/database/) version 18c - 21c.
5. [PostgreSQL](https://www.postgresql.org/) version 9.6 - 15. 
6. [Sqlite](https://www.sqlite.org/index.html) version 3.3 and above.

## Installation

To install [Yii Db](https://github.com/yiisoft/db), you must select the driver you want to use and install it with [Composer](https://getcomposer.org/).

- [Yii Mssql](https://github.com/yiisoft/db-mssql)

```bash
composer require yiisoft/db-mssql
```

- [Yii Mysql](https://github.com/yiisoft/db-mysql)
- [Yii MariaDB](https://github.com/yiisoft/db-mysql)

```bash
composer require yiisoft/db-mysql
```

- [Yii Oracle](https://github.com/yiisoft/db-oracle)

```bash
composer require yiisoft/db-oracle
```

- [Yii Pgsql](https://github.com/yiisoft/db-pgsql)

```bash
composer require yiisoft/db-pgsql
```

- [Yii Sqlite](https://github.com/yiisoft/db-pgsql)

```bash
composer require yiisoft/db-sqlite
```

## Configuration

1. [di-container](di-container-config.md)
2. [without-di-container](without-di-container.md)
