<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Stringable;

use function implode;

/**
 * It's typically used to parse a DSN string, which is a string that has all the necessary information to connect
 * to a database, such as the database driver, hostname, database name, port, and options.
 *
 * It also allows you to access individual components of the DSN, such as the driver or the database name.
 */
abstract class AbstractDsn implements DsnInterface, Stringable
{
    /**
     * @psalm-param string[] $options
     */
    public function __construct(
        private string $driver,
        private string $host,
        private string|null $databaseName = null,
        private string|null $port = null,
        private array $options = []
    ) {
    }

    public function asString(): string
    {
        $dsn = "$this->driver:" . "host=$this->host";

        if ($this->databaseName !== null && $this->databaseName !== '') {
            $dsn .= ';' . "dbname=$this->databaseName";
        }

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
     * @return string The Data Source Name, or DSN, has the information required to connect to the database.
     */
    public function __toString(): string
    {
        return $this->asString();
    }

    /**
     * @return string|null The database name to connect to.
     */
    public function getDatabaseName(): string|null
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
     * @return string|null The database port. Null if isn't set.
     */
    public function getPort(): string|null
    {
        return $this->port;
    }
}
