<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use PDO;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

interface ConnectionPDOPoolInterface
{
    /**
     * Whether to enable read/write splitting by using {@see slaves} to read data.
     *
     * Note that if {@see slaves} is empty, read/write splitting will NOT be enabled no matter what value this property
     * takes.
     */
    public function areSlavesEnabled(): bool;

    /**
     * Returns the currently active master connection.
     *
     * If this method is called for the first time, it will try to open a master connection.
     *
     * @return ConnectionInterface|null The currently active master connection. `null` is returned if there is no master
     * available.
     */
    public function getMaster(): ?ConnectionInterface;

    /**
     * Returns the currently active slave connection.
     *
     * If this method is called for the first time, it will try to open a slave connection when {@see setEnableSlaves()}
     * is true.
     *
     * @param bool $fallbackToMaster Whether to return a master connection in case there is no slave connection
     * available.
     *
     * @return ConnectionInterface|null The currently active slave connection. `null` is returned if there is no slave available
     * and `$fallbackToMaster` is false.
     */
    public function getSlave(bool $fallbackToMaster = true): ?ConnectionInterface;

    /**
     * Whether to enable read/write splitting by using {@see setSlaves()} to read data. Note that if {@see setSlaves()}
     * is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     *
     * @param bool $value
     */
    public function setEnableSlaves(bool $value): void;

    /**
     * Set connection for master server, you can specify multiple connections, adding the id for each one.
     *
     * @param string $key Index master connection.
     * @param ConnectionInterface $master The connection every master.
     */
    public function setMaster(string $key, ConnectionInterface $master): void;

    /**
     * The retry interval in seconds for dead servers listed in {@see setMaster()} and {@see setSlave()}.
     *
     * @param int $value The retry interval in seconds.
     */
    public function setServerRetryInterval(int $value): void;

    /**
     * Whether to shuffle {@see setMaster()} before getting one.
     *
     * @param bool $value Whether to shuffle {@see setMaster()} before getting one.
     */
    public function setShuffleMasters(bool $value): void;

    /**
     * Set connection for master slave, you can specify multiple connections, adding the id for each one.
     *
     * @param string $key Index slave connection.
     * @param ConnectionInterface $slave The connection every slave.
     */
    public function setSlave(string $key, ConnectionInterface $slave): void;

    /**
     * Executes the provided callback by using the master connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function (ConnectionInterface $db) {
     *     return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     * });
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (ConnectionInterface $db)`. Its return value will be returned by this method.
     *
     * @throws Throwable If there is any exception thrown from the callback.
     *
     * @return mixed The return value of the callback.
     */
    public function useMaster(callable $callback): mixed;

    /**
     * Returns the PDO instance for the currently active master connection.
     *
     * This method will open the master DB connection and then return {@see pdo}.
     *
     * @throws Exception|InvalidConfigException
     *
     * @return PDO|null the PDO instance for the currently active master connection.
     */
    public function getMasterPDO(): PDO|null;

    /**
     * Returns the PDO instance for the currently active slave connection.
     *
     * When {@see enableSlaves} is true, one of the slaves will be used for read queries, and its PDO instance will be
     * returned by this method.
     *
     * @param bool $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     *
     * @throws Exception
     *
     * @return PDO|null the PDO instance for the currently active slave connection. `null` is returned if no slave
     * connection is available and `$fallbackToMaster` is false.
     */
    public function getSlavePDO(bool $fallbackToMaster = true): ?PDO;
}
