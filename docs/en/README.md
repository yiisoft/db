# Getting started

Yii DB is DAO (Data Access Object) layer for applications using [PHP](https://www.php.net/).
It provides a set of classes that help you access relational databases.
It's designed to be flexible and extensible,
so that it can be used with different databases and different database schemas.
Its database agnostic nature makes it easy to switch from one database to another.

Yii DB provides an object-oriented API for accessing relational databases.
It's the foundation for other more advanced database access methods, including [Query Builder](query-builder.md).

When using Yii DB, you mainly need to deal with plain SQLs and PHP arrays.
As a result, it's the most efficient way to access databases.
However, because SQL syntax may vary for different databases, using Yii DB also means you have to take extra effort to
create a database agnostic application.

Yii DB supports the following databases out of the box:

- [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) of versions **2017, 2019, 2022**.
- [MySQL](https://www.mysql.com/) of versions **5.7 - 8.0**.
- [MariaDB](https://mariadb.org/) of versions **10.4 - 10.9**.
- [Oracle](https://www.oracle.com/database/) of versions **12c - 21c**.
- [PostgreSQL](https://www.postgresql.org/) of versions **9.6 - 15**. 
- [SQLite](https://www.sqlite.org/index.html) of version **3.3 and above**.

## Installation

To install Yii DB, you must select the driver you want to use and install it with [Composer](https://getcomposer.org/).

For [MSSQL](https://github.com/yiisoft/db-mssql):

```bash
composer require yiisoft/db-mssql
```

For [MySQL/MariaDB](https://github.com/yiisoft/db-mysql):

```bash
composer require yiisoft/db-mysql
```

For [Oracle](https://github.com/yiisoft/db-oracle):

```bash
composer require yiisoft/db-oracle
```

For [PostgreSQL](https://github.com/yiisoft/db-pgsql):

```bash
composer require yiisoft/db-pgsql
```

For [SQLite](https://github.com/yiisoft/db-sqlite):

```bash
composer require yiisoft/db-sqlite
```

## Prerequisites

## Configure schema cache

First, you need to [configure database schema cache](schema/cache.md).

## Create connection

You can create a database connection instance using a [DI container](https://github.com/yiisoft/di) or without it.

- [MSSQL Server](connection/mssql.md)
- [MySQL/MariaDB Server](connection/mysql.md)
- [Oracle Server](connection/oracle.md)
- [PostgreSQL Server](connection/pgsql.md)
- [SQLite Server](connection/sqlite.md)

> Info: When you create a DB connection instance, the actual connection to the database isn't established until
> you execute the first SQL or call the `Yiisoft\Db\Connection\ConnectionInterface::open()` method explicitly.

### Logger and profiler

Logger and profiler are optional. You can use them if you need to log and profile your queries.

- [Logger](connection/logger.md)
- [Profiler](connection/profiler.md)

## Execute SQL queries

Once you have a database connection instance, you can execute an SQL query by taking the following steps:

1. [Create a command and fetch data](queries/create-command-fetch-data.md)
2. [Bind parameters](queries/bind-parameters.md)
3. [Execute a command](queries/execute-command.md)
4. [Execute many commands in a transaction](queries/transactions.md)

## Quote table and column names

When writing a database-agnostic code, quoting table and column names is often a headache because different databases
have different names quoting rules.

To overcome this problem, you may use the following quoting syntax introduced by Yii DB:

- `[[column name]]`: enclose a *column name* to quote in *double square brackets*.
- `{{%table name}}`: enclose a *table name* to quote in *double curly brackets*, and the percentage character `%`
  will be replaced with the *table prefix*.

Yii DB will automatically convert such constructs into the corresponding quoted column or table names using the DBMS-specific syntax.

For example, the following code will generate an SQL statement that's valid for all supported databases:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT COUNT([[id]]) FROM {{%employee}}')->queryScalar()
```

## Query Builder

Yii DB provides a [Query Builder](query-builder.md) that helps you create SQL statements in a more convenient way.
It's a powerful tool to create complex SQL statements in a simple way.

## Commands

Yii DB provides a `Command` class that represents an **SQL** statement to be executed against a database.

You can use it to execute **SQL** statements that don't return any result set, such as `INSERT`, `UPDATE`, `DELETE`,
`CREATE TABLE`, `DROP TABLE`, `CREATE INDEX`, `DROP INDEX`, etc.

- [DDL commands](command/ddl.md)
- [DML commands](command/dml.md)

## Schema

Yii DB provides a way to inspect the metadata of a database, such as table names, column names, etc. You can do it
via schema:

- [Reading database schema](schema/usage.md)
- [Configuring schema cache](schema/cache.md)

## Extensions

The following extensions are available for Yii DB.

- [Active Record](https://github.com/yiisoft/active-record).
- [Cache DB](https://github.com/yiisoft/cache-db)
- [Data DB](https://github.com/yiisoft/data-db)
- [Log Target DB](https://github.com/yiisoft/log-target-db)
- [Rbac DB](https://github.com/yiisoft/rbac-db)
- [Translator Message DB](https://github.com/yiisoft/translator-message-db)
- [Yii DB Migration](https://github.com/yiisoft/yii-db-migration)
