## Database Access Objects

DAO are a way to access the database. They are a layer of abstraction between the database and the application. They are used to access the database and to convert the data from the database into objects that can be used by the application.

Built on top of [PDO](https://www.php.net/manual/en/book.pdo.php), [Yii Db](https://github.com/yiisoft/db) (Database Access Objects) provides an
object-oriented API for accessing relational databases. It is the foundation for other more advanced database access methods, including [Query Builder](dquery-builder.md) and [Active Record](active-record.md).

When using [Yii Db](https://github.com/yiisoft/db), you mainly need to deal with plain SQLs and PHP arrays. As a result, it is the most efficient way to access databases. However, because SQL syntax may vary for different databases, using [db](https://github.com/yiisoft/db) also means you have to take extra effort to create a database-agnostic application.

In Yii 3.0, [Yii Db](https://github.com/yiisoft/db) supports the following databases out of the box:

Dbms                                                               | Supported versions | Repository driver
-------------------------------------------------------------------|--------------------|--------------------------------------------------------------------------
[Mssql](https://www.microsoft.com/en-us/sql-server/sql-server-2019)| 2017 - 2019 - 2022 | [Yii Mssql](https://github.com/yiisoft/db-mssql)
[MySQL](https://www.mysql.com/)                                    | 5.7 - 8.0          | [Yii Mysql](https://github.com/yiisoft/db-mysql)
[MariaDB](https://mariadb.org/)                                    | 10.4 - 10.9        | 
[Oracle](https://www.oracle.com/database/)                         | 18 - 21            | [Yii Oracle](https://github.com/yiisoft/db-oracle)
[PostgreSQL](https://www.postgresql.org/)                          | 9.6 - 14           | [Yii Pgsql](https://github.com/yiisoft/db-pgsql)
[SQLite](https://www.sqlite.org/index.html)                        | 3.0                | [Yii Sqlite](https://github.com/yiisoft/db-pgsql)
