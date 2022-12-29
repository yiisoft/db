<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Stringable;

/**
 * The Dsn class is typically used to parse a DSN string, which is a string that contains all the necessary information
 * to connect to a database, such as the database driver, hostname, database name, port and options.
 *
 * It also allows you to access individual components of the DSN, such as the driver or the database name.
 */
abstract class AbstractDsn implements Stringable
{
    /**
     * @psalm-param string[] $options
     */
    public function __construct(
        private string $driver,
        private string $host,
        private string $databaseName,
        private string|null $port = null,
        private array $options = []
    ) {
    }

    /**
     * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('mysql', '127.0.0.1', 'yiitest', '3306');
     * $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn->getDsn());
     * ```
     *
     * Will result in the DSN string `mysql:host=127.0.0.1;dbname=yiitest;port=3306`.
     */
    public function asString(): string
    {
        $dsn = "$this->driver:" . "host=$this->host" . ';' . "dbname=$this->databaseName";

        if ($this->port !== null) {
            $dsn .= ';' . "port=$this->port";
        }

        $parts = [];

        foreach ($this->options as $key => $value) {
            $parts[] = "$key=$value";
        }

        if (!empty($parts)) {
            $dsn .= ';' . implode(';', $parts);
        }

        return $dsn;
    }

    /**
     * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
     */
    public function __toString(): string
    {
        return $this->asString();
    }

    /**
     * @return string The database name to connect to.
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @return string The database driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return string The database host name or IP address.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return array The database connection options. Default value to an empty array.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string|null The database port. Null if not set.
     */
    public function getPort(): string|null
    {
        return $this->port;
    }
}
