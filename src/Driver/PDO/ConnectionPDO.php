<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use PDOException;
use Psr\Log\LogLevel;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_keys;
use function is_string;

abstract class ConnectionPDO extends Connection implements ConnectionPDOInterface
{
    protected ?PDO $pdo = null;
    protected string $serverVersion = '';

    protected ?bool $emulatePrepare = null;

    protected ?QueryBuilderInterface $queryBuilder = null;
    protected ?QuoterInterface $quoter = null;
    protected ?SchemaInterface $schema = null;

    public function __construct(
        protected PDODriverInterface $driver,
        protected QueryCache $queryCache,
        protected SchemaCache $schemaCache
    ) {
        parent::__construct($queryCache);
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
     *
     * @return array
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
        if (!empty($this->pdo)) {
            return;
        }

        if (empty($this->driver->getDsn())) {
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

    public function getEmulatePrepare(): ?bool
    {
        return $this->emulatePrepare;
    }

    public function getPDO(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Input variables $sql and $forRead needs for future implementation of Connection + Pool
     *
     * @param string|null $sql
     * @param bool|null $forRead
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return PDO
     */
    public function getActivePDO(?string $sql = '', ?bool $forRead = null): PDO
    {
        $this->open();
        $pdo = $this->getPDO();

        if ($pdo === null) {
            throw new Exception('PDO cannot be initialized.');
        }

        return $pdo;
    }

    public function getLastInsertID(?string $sequenceName = null): string
    {
        if ($this->isActive() && $this->pdo) {
            return $this->pdo->lastInsertID($sequenceName === null
                ? null : $this->getQuoter()->quoteTableName($sequenceName));
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    public function getName(): string
    {
        return $this->driver->getDriverName();
    }

    /**
     * @throws Exception
     */
    public function getServerVersion(): string
    {
        if ($this->serverVersion === '') {
            /** @var mixed */
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
        if (!is_string($value)) {
            return $value;
        }

        return $this->getActivePDO()->quote($value);
    }

    public function setEmulatePrepare(bool $value): void
    {
        $this->emulatePrepare = $value;
    }

    abstract protected function initConnection(): void;
}
