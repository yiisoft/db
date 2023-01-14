<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use Yiisoft\Db\Connection\ConnectionPoolInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * The ConnectionPDOPoolInterface describes creating a connection pool of PDO (PHP Data Objects) connections.
 * It defines methods for managing the connections in the pool, such as getting and releasing connections, as well as
 * methods for checking the pool's configuration and status. By implementing this interface, developers can create
 * custom connection pool classes that can be used with the yiisoft/db library to handle PDO connections in a more
 * efficient and scalable way.
 */
interface ConnectionPDOPoolInterface extends ConnectionPoolInterface
{
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
