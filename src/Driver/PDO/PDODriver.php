<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;

abstract class PDODriver implements PDODriverInterface
{
    protected ?string $charset = null;

    public function __construct(
        protected string $dsn,
        protected string $username = '',
        protected string $password = '',
        protected array $attributes = []
    ) {
    }

    /**
     * PDO attributes (name => value) that should be set when calling {@see open()} to establish a DB connection.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.setattribute.php) for details about available
     * attributes.
     *
     * @param array $attributes the attributes (name => value) to be set on the DB connection.
     */
    public function attributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function createConnection(): PDO
    {
        return new PDO($this->dsn, $this->username, $this->password, $this->attributes);
    }

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
    public function setCharset(?string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * Returns the charset currently used for database connection. The returned charset is only applicable for MySQL,
     * PostgresSQL databases.
     *
     * @return string|null the charset of the pdo instance. Null is returned if the charset is not set yet or not
     * supported by the pdo driver
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    abstract public function getDriverName(): string;

    /**
     * Return dsn string for current driver.
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * Returns the password for establishing DB connection.
     *
     * @return string the password for establishing DB connection.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Returns the username for establishing DB connection.
     *
     * @return string the username for establishing DB connection.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * The password for establishing DB connection. Defaults to `null` meaning no password to use.
     *
     * @param string $password the password for establishing DB connection.
     */
    public function password(string $password): void
    {
        $this->password = $password;
    }

    /**
     * The username for establishing DB connection. Defaults to `null` meaning no username to use.
     *
     * @param string $username the username for establishing DB connection.
     */
    public function username(string $username): void
    {
        $this->username = $username;
    }
}
