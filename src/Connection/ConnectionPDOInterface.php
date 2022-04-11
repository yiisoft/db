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
    public function getPDO(): ?PDO;

    /**
     * Returns the PDO instance for the currently connection.
     *
     * This method will open the DB connection and then return {@see pdo}.
     *
     * @throws Exception|InvalidConfigException
     *
     * @return PDO|null the PDO instance for the currently connection.
     */
    public function getActivePDO(string $sql = '', ?bool $forRead = null): ?PDO;
}
