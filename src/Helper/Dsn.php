<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

final class Dsn
{
    private ?string $dbname;
    private string $driver;
    private ?string $dsn = null;
    private ?string $host;
    private ?string $port;
    private array $options;

    public function __construct(string $driver, string $host, string $dbname, string $port = null, array $options = [])
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->dbname = $dbname;
        $this->port = $port;
        $this->options = $options;
    }

    /**
     * @return string the Data Source Name, or DSN, contains the information required to connect to the database.
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

    public function getDsn(): string
    {
        $this->dsn = "$this->driver:" . "host=$this->host" . ';' . "dbname=$this->dbname";

        if ($this->port !== null) {
            $this->dsn .= ';' . "port=$this->port";
        }

        $parts = [];

        foreach ($this->options as $key => $value) {
            $parts[] = "$key=$value";
        }

        if (!empty($parts)) {
            $this->dsn . ';' . implode(';', $parts);
        }

        return $this->dsn;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
