<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use InvalidArgumentException;

/**
 * ConnectionProvider is used to manage DB connections.
 */
final class ConnectionProvider
{
    public const DEFAULT = 'default';

    /** @var ConnectionInterface[] $connections */
    private static array $connections = [];

    /**
     * Returns all connections.
     *
     * @return ConnectionInterface[]
     */
    public static function all(): array
    {
        return self::$connections;
    }

    /**
     * Clears all connections.
     */
    public static function clear(): void
    {
        self::$connections = [];
    }

    /**
     * Returns a connection by name.
     */
    public static function get(string $name = self::DEFAULT): ConnectionInterface
    {
        return self::$connections[$name]
            ?? throw new InvalidArgumentException("Connection with name '$name' does not exist.");
    }

    /**
     * Checks if a connection with the given name exists.
     */
    public static function has(string $name = self::DEFAULT): bool
    {
        return isset(self::$connections[$name]);
    }

    /**
     * Removes a connection by name.
     */
    public static function remove(string $name = self::DEFAULT): void
    {
        unset(self::$connections[$name]);
    }

    /**
     * Sets a connection by name.
     */
    public static function set(ConnectionInterface $connection, string $name = self::DEFAULT): void
    {
        self::$connections[$name] = $connection;
    }
}
