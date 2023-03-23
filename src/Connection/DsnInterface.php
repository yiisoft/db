<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

/**
 * Represents a Data Source Name (DSN) that's used to configure a {@see ConnectionInterface} instance.
 *
 * For DSN string format see {@see DsnInterface::asString()}.
 */
interface DsnInterface
{
    /**
     * @return string The Data Source Name, or DSN, has the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']);
     * $pdoDriver = new PDODriver($dsn->asString(), 'username', 'password');
     * $connection = new Connection($pdoDriver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `mysql:host=127.0.0.1;dbname=yiitest;port=3306;charset=utf8mb4`.
     *
     * Or unix socket:
     *
     * ```php
     * $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', '', ['charset' => 'utf8mb4']);
     * $pdoDriver = new PDODriver($dsn->asString(), 'username', 'password');
     * $connection = new Connection($pdoDriver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest;charset=utf8mb4`.
     */
    public function asString(): string;
}
