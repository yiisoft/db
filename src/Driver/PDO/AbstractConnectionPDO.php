<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use PDOException;
use Psr\Log\LogLevel;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\AbstractConnection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_keys;
use function is_string;

/**
 * Represents a connection to a database using the PDO (PHP Data Objects) extension.
 *
 * It provides a set of methods for interacting with a database using PDO, such as executing SQL statements, preparing
 * and executing statements, and managing transactions.
 *
 * The ConnectionPDO classes extend from this class, which is a base class for representing a connection to a database.
 *
 * It implements the ConnectionInterface, which defines the interface for interacting with a database connection.
 */
abstract class AbstractConnectionPDO extends AbstractConnection implements ConnectionPDOInterface
{
    protected PDO|null $pdo = null;
    protected string $serverVersion = '';
    protected bool|null $emulatePrepare = null;
    protected QueryBuilderInterface|null $queryBuilder = null;
    protected QuoterInterface|null $quoter = null;
    protected SchemaInterface|null $schema = null;

    public function __construct(protected PDODriverInterface $driver, protected SchemaCache $schemaCache)
    {
    }

    /**
     * Reset the connection after cloning.
     */
    public function __clone()
    {
        $this->transaction = null;
        $this->pdo = null;
    }

    /**
     * Close the connection before serializing.
     */
    public function __sleep(): array
    {
        $fields = (array) $this;

        unset(
            $fields["\000*\000" . 'pdo'],
            $fields["\000*\000" . 'transaction'],
            $fields["\000*\000" . 'schema']
        );

        return array_keys($fields);
    }

    public function open(): void
    {
        if ($this->pdo instanceof PDO) {
            return;
        }

        if ($this->driver->getDsn() === '') {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        $token = 'Opening DB connection: ' . $this->driver->getDsn();

        try {
            $this->logger?->log(LogLevel::INFO, $token);
            $this->profiler?->begin($token, [__METHOD__]);
            $this->initConnection();
            $this->profiler?->end($token, [__METHOD__]);
        } catch (PDOException $e) {
            $this->profiler?->end($token, [__METHOD__]);
            $this->logger?->log(LogLevel::ERROR, $token);

            throw new Exception($e->getMessage(), (array) $e->errorInfo, $e);
        }
    }

    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Closing DB connection: ' . $this->driver->getDsn() . ' ' . __METHOD__,
            );

            $this->pdo = null;
            $this->transaction = null;
        }
    }

    public function getCacheKey(): array
    {
        return [$this->driver->getDsn(), $this->driver->getUsername()];
    }

    public function getDriver(): PDODriverInterface
    {
        return $this->driver;
    }

    public function getEmulatePrepare(): bool|null
    {
        return $this->emulatePrepare;
    }

    public function getActivePDO(string|null $sql = '', bool|null $forRead = null): PDO
    {
        $this->open();
        $pdo = $this->getPDO();

        if ($pdo === null) {
            throw new Exception('PDO cannot be initialized.');
        }

        return $pdo;
    }

    public function getPDO(): PDO|null
    {
        return $this->pdo;
    }

    public function getLastInsertID(string $sequenceName = null): string
    {
        if ($this->pdo !== null) {
            return $this->pdo->lastInsertID($sequenceName ?? null);
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    public function getName(): string
    {
        return $this->driver->getDriverName();
    }

    public function getServerVersion(): string
    {
        if ($this->serverVersion === '') {
            /** @psalm-var mixed $version */
            $version = $this->getActivePDO()->getAttribute(PDO::ATTR_SERVER_VERSION);
            $this->serverVersion = is_string($version) ? $version : 'Version could not be determined.';
        }

        return $this->serverVersion;
    }

    public function isActive(): bool
    {
        return $this->pdo !== null;
    }

    public function quoteValue(mixed $value): mixed
    {
        if (is_string($value) === false) {
            return $value;
        }

        return $this->getActivePDO()->quote($value);
    }

    public function setEmulatePrepare(bool $value): void
    {
        $this->emulatePrepare = $value;
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`, if {@see getEmulatePrepare()} is `true`.
     */
    protected function initConnection(): void
    {
        if ($this->getEmulatePrepare() !== null) {
            $this->driver->attributes([PDO::ATTR_EMULATE_PREPARES => $this->getEmulatePrepare()]);
        }

        $this->pdo = $this->driver->createConnection();
    }
}
