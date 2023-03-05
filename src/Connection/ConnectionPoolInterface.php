<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Closure;
use Throwable;

/**
 * This interface represents a connection pool. It provides a way to get a connection from the pool. It also provides
 * methods to set and get the primary and secondary connections.
 */
interface ConnectionPoolInterface
{
    /**
     * Whether to enable read/write splitting by using {@see slaves} to read data.
     *
     * Note that if {@see slaves} is empty, read/write splitting will NOT be enabled no matter what value this property
     * takes.
     */
    public function areSecondaryEnabled(): bool;

    /**
     * Returns the currently active primary connection.
     *
     * If this method is called for the first time, it will try to open a primary connection.
     *
     * @return ConnectionInterface|null The currently active primary connection. `Null` is returned if there is no
     * primary available.
     */
    public function getPrimary(): ConnectionInterface|null;

    /**
     * Returns the currently active secondary connection.
     *
     * If this method is called for the first time, it will try to open a secondary connection when
     * {@see setEnableSecondary()}
     * is true.
     *
     * @param bool $fallbackToMaster Whether to return a primary connection in case there is no secondary connection
     * available.
     *
     * @return ConnectionInterface|null The currently active secondary connection. `Null` is returned if there is no
     * secondary available and `$fallbackToMaster` is false.
     */
    public function getSecondary(bool $fallbackToMaster = true): ConnectionInterface|null;

    /**
     * Whether to enable read/write splitting by using {@see setSecondary()} to read data.
     * Note that if {@see setSecondary()} is empty, read/write splitting will NOT be enabled no matter what value this
     * property takes.
     */
    public function setEnableSecondary(bool $value): void;

    /**
     * Set connection for primary server, you can specify multiple connections, adding the id for each one.
     *
     * @param string $key Index primary connection.
     * @param ConnectionInterface $master The connection every primary.
     */
    public function setPrimary(string $key, ConnectionInterface $master): void;

    /**
     * The retry interval in seconds for dead servers listed in {@see setPrimary()} and {@see setSecondary()}.
     *
     * @param int $value The retry interval in seconds.
     */
    public function setServerRetryInterval(int $value): void;

    /**
     * Whether to shuffle {@see setMaster()} before getting one.
     *
     * @param bool $value Whether to shuffle {@see setMaster()} before getting one.
     */
    public function setShufflePrimary(bool $value): void;

    /**
     * Set connection for a primary secondary, you can specify multiple connections, adding the id for each one.
     *
     * @param string $key Index secondary connection.
     * @param ConnectionInterface $slave The connection every secondary.
     */
    public function setSlave(string $key, ConnectionInterface $slave): void;

    /**
     * Executes the provided callback by using the primary connection.
     *
     * This method is provided so that you can temporarily force using the primary connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function (ConnectionInterface $db) {
     *     return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     * });
     * ```
     *
     * @param Closure $closure a PHP Closure to be executed by this method.
     * Its signature is `function (ConnectionInterface $db)`. This method will return its return value.
     *
     * @throws Throwable If there is any exception thrown from the callback.
     *
     * @return mixed The return value of the callback.
     */
    public function useMaster(Closure $closure): mixed;
}
