<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;

/**
 * The AbstractPDODriver serves as the base class for creating PDO (PHP Data Objects) drivers. It provides a set of
 * common methods and properties that are implemented by the specific PDO driver classes, such as Pgsql, Mysql, MariaDb,
 * Sqlite, Oracle, Mssql etc. These methods and properties include things like the PDO connection object, the ability to
 * quote and format table and column names, and methods for performing common database operations like inserting,
 * updating, and deleting records.
 */
abstract class AbstractPDODriver implements PDODriverInterface
{
    protected string|null $charset = null;

    public function __construct(
        protected string $dsn,
        protected string $username = '',
        protected string $password = '',
        protected array $attributes = []
    ) {
    }

    public function attributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function createConnection(): PDO
    {
        return new PDO($this->dsn, $this->username, $this->password, $this->attributes);
    }

    public function charset(string|null $charset): void
    {
        $this->charset = $charset;
    }

    public function getCharset(): string|null
    {
        return $this->charset;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function password(string $password): void
    {
        $this->password = $password;
    }

    public function username(string $username): void
    {
        $this->username = $username;
    }
}
