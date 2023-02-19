<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use Yiisoft\Db\Driver\DriverInterface;

/**
 * The PDODriverInterface provides a set of methods that must be implemented by PDO (PHP Data Objects) driver classes.
 * These methods include basic CRUD (create, read, update, delete) operations for interacting with a database, such as
 * connecting to a database, preparing and executing SQL statements, and retrieving data from the result set.
 */
interface PDODriverInterface extends DriverInterface
{
    /**
     * PDO attributes (name => value) that should be set when calling {@see open()} to establish a DB connection.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.setattribute.php) for details about available
     * attributes.
     *
     * @param array $attributes the attributes (name => value) to be set on the DB connection.
     */
    public function attributes(array $attributes): void;

    public function createConnection(): PDO;

    /**
     * The charset used for database connection. The property is only used for MySQL, PostgresSQL databases. Defaults to
     * null, meaning using default charset as configured by the database.
     *
     * For Oracle Database, the charset must be specified in the {@see dsn}, for example for UTF-8 by appending
     * `;charset=UTF-8` to the DSN string.
     *
     * The same applies for if you're using GBK or BIG5 charset with MySQL, then it's highly recommended specifying
     * charset via {@see dsn} like `'mysql:dbname=database;host=127.0.0.1;charset=GBK;'`.
     *
     * @param string|null $charset
     */
    public function charset(string|null $charset): void;

    /**
     * Returns the charset currently used for database connection. The returned charset is only applicable for MySQL,
     * PostgresSQL databases.
     *
     * @return string|null the charset of the pdo instance. Null is returned if the charset is not set yet or not
     * supported by the pdo driver
     */
    public function getCharset(): string|null;

    /**
     * Return dsn string for current driver.
     */
    public function getDsn(): string;

    /**
     * Returns the password for establishing DB connection.
     *
     * @return string the password for establishing DB connection.
     */
    public function getPassword(): string;

    /**
     * Returns the username for establishing DB connection.
     *
     * @return string the username for establishing DB connection.
     */
    public function getUsername(): string;

    /**
     * Returns the driver name
     *
     * @return string the driver name DB connection.
     */
    public function getDriverName(): string;

    /**
     * The password for establishing DB connection. Defaults to `null` meaning no password to use.
     *
     * @param string $password the password for establishing DB connection.
     */
    public function password(string $password): void;

    /**
     * The username for establishing DB connection. Defaults to `null` meaning no username to use.
     *
     * @param string $username the username for establishing DB connection.
     */
    public function username(string $username): void;
}
