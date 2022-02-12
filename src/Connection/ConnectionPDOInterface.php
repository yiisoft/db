<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use PDO;
use Yiisoft\Db\Driver\PDODriver;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

interface ConnectionPDOInterface extends ConnectionInterface
{
    /**
     * Returns the currently active driver connection.
     */
    public function getDriver(): PDODriver;

    /**
     * The PHP PDO instance associated with this DB connection. This property is mainly managed by {@see open()} and
     * {@see close()} methods. When a DB connection is active, this property will represent a PDO instance; otherwise,
     * it will be null.
     *
     * @return PDO|null
     *
     * {@see pdoClass}
     */
    public function getPdo(): ?PDO;

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
