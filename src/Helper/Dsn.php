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

    public function __construct(string $driver, string $host = null, string $dbname = null, string $port = null, array $options = [])
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->dbname = $dbname;
        $this->port = $port;
        $this->options = $options;
    }

    public function getDsn(): string
    {
        if ($this->driver !== 'sqlite') {
            $this->dsn = "$this->driver:" . "host=$this->host" . ';' . "dbname=$this->dbname";
        } else {
            $this->dsn = "$this->driver:" . $this->dbname;
        }

        if ($this->port !== null) {
            $this->dsn = $this->dsn . ';' . "port=$this->port";
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
}
