<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * The ConnectionPDOInterface class defines a set of methods that must be implemented by a class to be used as a
 * connection to a database using PDO (PHP Data Objects).
 */
interface ConnectionPDOInterface extends ConnectionInterface
{
    /**
     * The PHP PDO instance associated with this DB connection. This property is mainly managed by {@see open()} and
     * {@see close()} methods. When a DB connection is active, this property will represent a PDO instance; otherwise,
     * it will be null.
     *
     * @return PDO|null The PHP PDO instance associated with this DB connection.
     *
     * {@see pdoClass}
     */
    public function getPDO(): PDO|null;

    /**
     * Returns the PDO instance for the current connection.
     *
     * This method will open the DB connection and then return {@see pdo}.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return PDO|null The PDO instance for the current connection.
     */
    public function getActivePDO(string $sql = '', bool $forRead = null): PDO|null;

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
     * Whether to turn on prepare emulation. Defaults to false, meaning PDO will use the native prepare support if
     * available. For some databases (such as MySQL), this may need to be set true so that PDO can emulate to prepare
     * support to bypass the buggy native prepare support. The default value is null, which means the PDO
     * ATTR_EMULATE_PREPARES value will not be changed.
     *
     * @param bool $value Whether to turn on prepare emulation.
     */
    public function setEmulatePrepare(bool $value): void;
}
