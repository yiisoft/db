# Getting Started

[Yii DB](https://github.com/yiisoft/db) is DAO (Data Access Object) layer for [YiiFramework](https://www.yiiframework.com/). It provides a set of classes that help you access relational databases. It is designed to be flexible and extensible, so that it can be used with different databases and different database schemas. Its database agnostic nature makes it easy to switch from one database to another.

Built on top of [PDO](https://www.php.net/manual/en/book.pdo.php), [Yii DB](https://github.com/yiisoft/db) provides an object-oriented API for accessing relational databases. It is the foundation for other more advanced database access methods, including [Query Builder](query-builder.md) and [Active Record](active-record.md).

When using [Yii DB](https://github.com/yiisoft/db), you mainly need to deal with plain SQLs and PHP arrays. As a result, it is the most efficient way to access databases. However, because SQL syntax may vary for different databases, using [Yii DB](https://github.com/yiisoft/db) also means you have to take extra effort to create a database agnostic application.

In [YiiFramework](https://www.yiiframework.com/), [Yii DB](https://github.com/yiisoft/db) supports the following databases out of the box:

1. [MsSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) version **2017, 2019, 2022**.
2. [MySQL](https://www.mysql.com/) version **5.7 - 8.0**.
3. [MariaDB](https://mariadb.org/) version **10.4 - 10.9**.
4. [Oracle](https://www.oracle.com/database/) version **18c - 21c**.
5. [PostgreSQL](https://www.postgresql.org/) version **9.6 - 15**. 
6. [Sqlite](https://www.sqlite.org/index.html) version **3.3 and above**.

## Installation

To install [Yii DB](https://github.com/yiisoft/db), you must select the driver you want to use and install it with [Composer](https://getcomposer.org/).

- [Yii DB MSSQL](https://github.com/yiisoft/db-mssql)

```bash
composer require yiisoft/db-mssql
```

- [Yii DB MySQL/MariaDB](https://github.com/yiisoft/db-mysql)

```bash
composer require yiisoft/db-mysql
```

- [Yii DB Oracle](https://github.com/yiisoft/db-oracle)

```bash
composer require yiisoft/db-oracle
```

- [Yii DB PostgreSQL](https://github.com/yiisoft/db-pgsql)

```bash
composer require yiisoft/db-pgsql
```

- [Yii DB SQLite](https://github.com/yiisoft/db-pgsql)

```bash
composer require yiisoft/db-sqlite
```

## Prerequisites

1. [Configuring SchemaCache](schema-cache.md)

## Create Connection

You can create a database connection instance using [DI container](https://github.com/yiisoft/di) or without it.

1. [MSSQL Server](/docs/en/create_connection/di-container-mssql.md)
2. [MySQL/MariaDB Server](/docs/en/create_connection/di-container-mysql.md)
3. [Oracle Server](/docs/en/create_connection/di-container-oracle.md)
4. [PostgreSQL Server](/docs/en/create_connection/di-container-pgsql.md)
5. [SQLite Server](/docs/en/create_connection/di-container-sqlite.md)

**Info:** *When you create a DB connection instance, the actual connection to the database is not established until you execute the first `SQL` or you call the `Yiisoft\Db\Connection\ConnectionInterface::open()` method explicitly.*

### Logger and profiler

Logger and profiler are optional. You can use them if you need to log and profile your queries.

1. [Logger](/docs/en/connection/logger_profiler/logger.md)
2. [Profiler](/docs/en/connection/logger_profiler/profiler.md)

## Executing SQL queries

Once you have a database connection instance, you can execute a SQL query by taking the following steps:

1. [Create a command with a plain SQL query](/docs/en/executing_sql_queries/create-command.md)
2. [Bind parameters](/docs/en/executing_sql_queries/bind-parameters.md).
3. [Call one of the SQL execute method to execute the command](/docs/en/executing_sql_queries/execute-command.md).
