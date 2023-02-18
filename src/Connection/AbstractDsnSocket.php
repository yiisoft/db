<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Stringable;

/**
 * The Dsn class is typically used to parse a DSN string, which is a string that contains all the necessary information
 * to connect to a database, such as the database driver, unix socket, database name, options.
 *
 * It also allows you to access individual components of the DSN, such as the driver or the database name.
 */
abstract class AbstractDsnSocket implements DsnInterface, Stringable
{
    /**
     * @psalm-param string[] $options
     */
    public function __construct(
        private string $driver,
        private string $unixSocket,
        private string $databaseName,
        private array $options = []
    ) {
    }

    public function asString(): string
    {
        $dsn = "$this->driver:" . "unix_socket=$this->unixSocket" . ';' . "dbname=$this->databaseName";

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
     * @return string The database driver to use.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return string The unix socket to connect to.
     */
    public function getUnixSocket(): string
    {
        return $this->unixSocket;
    }

    /**
     * @return array The options to use.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
