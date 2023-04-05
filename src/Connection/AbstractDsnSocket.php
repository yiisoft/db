<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Stringable;

use function implode;

/**
 * It's typically used to parse a DSN string, which is a string that has all the necessary information to connect
 * to a database, such as the database driver, unix socket, database name, options.
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
        private string|null $databaseName = null,
        private array $options = []
    ) {
    }

    public function asString(): string
    {
        $dsn = "$this->driver:" . "unix_socket=$this->unixSocket";

        if ($this->databaseName !== null && $this->databaseName !== '') {
            $dsn .= ';' . "dbname=$this->databaseName";
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
