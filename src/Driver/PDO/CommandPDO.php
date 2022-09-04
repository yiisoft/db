<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Query\Data\DataReader;

abstract class CommandPDO extends Command implements CommandPDOInterface
{
    protected ?PDOStatement $pdoStatement = null;

    public function __construct(protected ConnectionPDOInterface $db, QueryCache $queryCache)
    {
        parent::__construct($queryCache);
    }

    /**
     * @inheritDoc
     * This method mainly sets {@see pdoStatement} to be null.
     */
    public function cancel(): void
    {
        $this->pdoStatement = null;
    }

    public function getPdoStatement(): ?PDOStatement
    {
        return $this->pdoStatement;
    }

    /**
     * @inheritDoc
     *
     * @link http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam(
        int|string $name,
        mixed &$value,
        ?int $dataType = null,
        ?int $length = null,
        mixed $driverOptions = null
    ): static {
        $this->prepare();

        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }

        if ($length === null) {
            $this->pdoStatement?->bindParam($name, $value, $dataType);
        } elseif ($driverOptions === null) {
            $this->pdoStatement?->bindParam($name, $value, $dataType, $length);
        } else {
            $this->pdoStatement?->bindParam($name, $value, $dataType, $length, $driverOptions);
        }

        return $this;
    }

    public function bindValue(int|string $name, mixed $value, ?int $dataType = null): static
    {
        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }

        $this->params[$name] = new Param($value, $dataType);

        return $this;
    }

    public function bindValues(array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        /**
         * @psalm-var array<string, int>|ParamInterface|int $value
         */
        foreach ($values as $name => $value) {
            if ($value instanceof ParamInterface) {
                $this->params[$name] = $value;
            } else {
                $type = $this->db->getSchema()->getPdoType($value);
                $this->params[$name] = new Param($value, $type);
            }
        }

        return $this;
    }

    /**
     * @throws Exception|InvalidConfigException|PDOException
     */
    public function prepare(?bool $forRead = null): void
    {
        if (isset($this->pdoStatement)) {
            $this->bindPendingParams();

            return;
        }

        $sql = $this->getSql();

        $pdo = $this->db->getActivePDO($sql, $forRead);

        try {
            $this->pdoStatement = $pdo?->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            /** @var array|null */
            $errorInfo = $e->errorInfo ?? null;

            throw new Exception($message, $errorInfo, $e);
        }
    }

    /**
     * Binds pending parameters that were registered via {@see bindValue()} and {@see bindValues()}.
     *
     * Note that this method requires an active {@see pdoStatement}.
     */
    protected function bindPendingParams(): void
    {
        foreach ($this->params as $name => $value) {
            $this->pdoStatement?->bindValue($name, $value->getValue(), $value->getType());
        }
    }

    protected function getCacheKey(int $queryMode, string $rawSql): array
    {
        return array_merge([static::class , $queryMode], $this->db->getCacheKey(), [$rawSql]);
    }

    /**
     * @throws InvalidParamException
     */
    protected function internalGetQueryResult(int $queryMode): mixed
    {
        if ($queryMode === static::QUERY_MODE_CURSOR) {
            return new DataReader($this);
        }

        if ($queryMode === static::QUERY_MODE_NONE) {
            return $this->pdoStatement?->rowCount() ?? 0;
        }

        if ($queryMode === static::QUERY_MODE_ROW) {
            /** @var mixed */
            $result = $this->pdoStatement?->fetch(PDO::FETCH_ASSOC);
        } elseif ($queryMode === static::QUERY_MODE_COLUMN) {
            /** @var mixed */
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_COLUMN);
        } else {
            /** @var mixed */
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->pdoStatement?->closeCursor();

        return $result;
    }

    /**
     * Executes a prepared statement.
     *
     * It's a wrapper around {@see PDOStatement::execute()} to support transactions and retry handlers.
     *
     * @param string|null $rawSql the rawSql if it has been created.
     *
     * @throws Exception|Throwable
     */
    abstract protected function internalExecute(?string $rawSql): void;
}
