<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * This interface defines a set of methods to implement in a class that allows to connect to a database
 * with {@see PDO} (PHP Data Objects).
 */
interface PdoConnectionInterface extends ConnectionInterface
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
     * When a DB connection is active, this property will represent a PDO instance.
     * Otherwise, it will be `null`.
     *
     * @return PDO|null The PHP PDO instance associated with this DB connection.
     *
     * @see PDO
     */
    public function getPDO(): PDO|null;

    /**
     * Returns current DB driver.
     *
     * @return PdoDriverInterface The driver used to create current connection.
     */
    public function getDriver(): PdoDriverInterface;

    /**
     * Whether to emulate prepared statements on PHP side.
     *
     * @see setEmulatePrepare()
     */
    public function getEmulatePrepare(): bool|null;

    /**
     * Whether to turn on prepare statements emulation on PHP side.
     *
     * Defaults to `false`, meaning {@see PDO} will use database prepared statements support if possible.
     *
     * For some databases (such as MySQL), you may need to set it to `true` so that {@see PDO} can
     * replace buggy prepared statements support provided by database itself.
     *
     * The default value is `null`, which means using default {@see PDO} `ATTR_EMULATE_PREPARES` value.
     *
     * @param bool $value Whether to turn on prepared statement emulation.
     */
    public function setEmulatePrepare(bool $value): void;
}
