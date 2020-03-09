<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

class ConnectionPool
{
    private static array $connectionsPool = [];

    public static function getConnectionPool(string $key): Connection
    {
        return static::$connectionsPool[$key];
    }

    public static function setConnectionsPool(string $key, Connection $config): void
    {
        static::$connectionsPool[$key] = $config;
    }
}
