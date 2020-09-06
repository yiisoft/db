<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

class ConnectionPool
{
    private static array $connectionsPool = [];

    public static function getConnectionPool(string $key): ConnectionInterface
    {
        return static::$connectionsPool[$key];
    }

    public static function setConnectionsPool(string $key, ConnectionInterface $config): void
    {
        static::$connectionsPool[$key] = $config;
    }
}
