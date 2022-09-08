<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

/**
 * Dns represents the data source name that specifies how to connect to the database.
 */
final class Dsn
{
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

        /** @psalm-var string[] */
        $options = $this->options;

        foreach ($options as $key => $value) {
            $parts[] = "$key=$value";
        }

        if (!empty($parts)) {
            $dsn .= ';' . implode(';', $parts);
        }

        return $dsn;
    }

    public function __toString(): string
    {
        return $this->asString();
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string|null
    {
        return $this->port;
    }
}
