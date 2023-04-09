<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use Yiisoft\Db\Driver\DriverInterface;

/**
 * This interface provides a set of methods to implement by {@see PDO} (PHP Data Objects) driver classes.
 *
 * @link https://www.php.net/manual/en/book.pdo.php
 */
interface PdoDriverInterface extends DriverInterface
{
    /**
     * Set {@see PDO} attributes (name => value) to set when calling {@see open()} to establish a DB
     * connection.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.setattribute.php) for details about available
     * attributes.
     *
     * @param array $attributes The attributes (name => value) to set on the DB connection.
     */
    public function attributes(array $attributes): void;

    /**
     * Creates a {@see PDO} instance representing a connection to a database.
     *
     * @return PDO The created {@see PDO} instance.
     */
    public function createConnection(): PDO;

    /**
     * Set charset used for database connection. The property is only used for MySQL, PostgresSQL databases. Defaults to
     * `null`, meaning using default charset as configured by the database.
     *
     * For Oracle Database, the charset must be specified in the {@see dsn}, for example, for UTF-8 by appending
     * `;charset=UTF-8` to the DSN string.
     *
     * The same applies if you're using GBK or BIG5 charset with MySQL.
     * In this case it's highly recommended specifying
     * charset via {@see dsn} like `'mysql:dbname=database;host=127.0.0.1;charset=GBK;'`.
     *
     * @param string|null $charset The charset to use for database connection.
     */
    public function charset(string|null $charset): void;

    /**
     * @return string|null The charset of the pdo instance. If the charset isn't set yet or not
     * supported by the PDO driver, it returns `null`.
     */
    public function getCharset(): string|null;

    /**
     * @return string The DSN string for creating a PDO instance.
     */
    public function getDsn(): string;

    /**
     * @return string The driver name for DB connection.
     */
    public function getDriverName(): string;

    /**
     * @return string The password for establishing DB connection.
     */
    public function getPassword(): string;

    /**
     * @return string The username for establishing DB connection.
     */
    public function getUsername(): string;

    /**
     * Set password for establishing DB connection. Defaults to `null` meaning use no password.
     *
     * @param string $password The password for establishing DB connection.
     */
    public function password(string $password): void;

    /**
     * Set username for establishing DB connection. Defaults to `null` meaning use no username.
     *
     * @param string $username The username for establishing DB connection.
     */
    public function username(string $username): void;
}
