<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * This interface defines a set of methods that must be implemented by a class to be used as a connection to a database
 * using {@see PDO} (PHP Data Objects).
 */
interface ConnectionPDOInterface extends ConnectionInterface
{
    /**
     * Returns the PDO instance for the current connection.
     *
     * This method will open the DB connection and then return {@see PDO}.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return PDO|null The {@see PDO} instance for the current connection.
     */
    public function getActivePDO(string $sql = '', bool $forRead = null): PDO|null;

    /**
     * The PHP {@see PDO} instance associated with this DB connection.
     *
     * This property is mainly managed by {@see open()} and {@see close()} methods.
     *
     * When a DB connection is active, this property will represent a PDO instance; otherwise, it will be null.
     *
     * @return PDO|null The PHP PDO instance associated with this DB connection.
     *
     * {@see PDO}
     */
    public function getPDO(): PDO|null;

    /**
     * Returns current DB driver.
     *
     * @return PDODriverInterface - The driver used to create current connection.
     */
    public function getDriver(): PDODriverInterface;

    /**
     * Return emulate prepare value.
     */
    public function getEmulatePrepare(): bool|null;

    /**
     * Whether to turn on prepare emulation.
     *
     * Defaults to false, meaning {@see PDO} will use native-prepared support if available.
     *
     * For some databases (such as MySQL), this may need to be set true so that {@see PDO} can emulate to preparing
     * support to bypassing the buggy native prepare support.
     *
     * The default value is null, which means the {@see PDO} `ATTR_EMULATE_PREPARES` value won't be changed.
     *
     * @param bool $value Whether to turn on prepare emulation.
     */
    public function setEmulatePrepare(bool $value): void;
}
