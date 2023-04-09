<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Command\AbstractCommand;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Profiler\Context\CommandContext;
use Yiisoft\Db\Profiler\ProfilerAwareInterface;
use Yiisoft\Db\Profiler\ProfilerAwareTrait;
use Yiisoft\Db\Query\Data\DataReader;

/**
 * Represents a database command that can be executed using a PDO (PHP Data Object) database connection.
 *
 * It's an abstract class that provides a common interface for building and executing various types of statements
 * such as {@see cancel()}, {@see execute()}, {@see insert()}, {@see update()}, {@see delete()}, etc., using a PDO
 * connection.
 *
 * It also provides methods for binding parameter values and retrieving query results.
 */
abstract class AbstractPdoCommand extends AbstractCommand implements PdoCommandInterface, LoggerAwareInterface, ProfilerAwareInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    /**
     * @var PDOStatement|null Represents a prepared statement and, after the statement is executed, an associated
     * result set.
     *
     * @link https://www.php.net/manual/en/class.pdostatement.php
     */
    protected PDOStatement|null $pdoStatement = null;

    public function __construct(protected PdoConnectionInterface $db)
    {
    }

    /**
     * This method mainly sets {@see pdoStatement} to be `null`.
     */
    public function cancel(): void
    {
        $this->pdoStatement = null;
    }

    public function getPdoStatement(): PDOStatement|null
    {
        return $this->pdoStatement;
    }

    public function bindParam(
        int|string $name,
        mixed &$value,
        int|null $dataType = null,
        int|null $length = null,
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

    public function bindValue(int|string $name, mixed $value, int|null $dataType = null): static
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

    public function prepare(bool|null $forRead = null): void
    {
        if (isset($this->pdoStatement)) {
            $this->bindPendingParams();

            return;
        }

        $sql = $this->getSql();

        /**
         * If SQL is empty, there will be {@see \ValueError} on prepare pdoStatement.
         *
         * @link https://php.watch/versions/8.0/ValueError
         */
        if ($sql === '') {
            return;
        }

        $pdo = $this->db->getActivePDO($sql, $forRead);

        try {
            $this->pdoStatement = $pdo?->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            /** @psalm-var array|null $errorInfo */
            $errorInfo = $e->errorInfo ?? null;

            throw new Exception($message, $errorInfo, $e);
        }
    }

    /**
     * Binds pending parameters registered via {@see bindValue()} and {@see bindValues()}.
     *
     * Note that this method requires an active {@see pdoStatement}.
     */
    protected function bindPendingParams(): void
    {
        foreach ($this->params as $name => $value) {
            $this->pdoStatement?->bindValue($name, $value->getValue(), $value->getType());
        }
    }

    protected function getQueryMode(int $queryMode): string
    {
        return match ($queryMode) {
            self::QUERY_MODE_EXECUTE => 'execute',
            self::QUERY_MODE_ROW => 'queryOne',
            self::QUERY_MODE_ALL => 'queryAll',
            self::QUERY_MODE_COLUMN => 'queryColumn',
            self::QUERY_MODE_CURSOR => 'query',
            self::QUERY_MODE_SCALAR => 'queryScalar',
            self::QUERY_MODE_ROW | self::QUERY_MODE_EXECUTE => 'insertWithReturningPks'
        };
    }

    /**
     * Executes a prepared statement.
     *
     * It's a wrapper around {@see PDOStatement::execute()} to support transactions and retry handlers.
     *
     * @param string|null $rawSql The rawSql if it has been created.
     *
     * @throws Exception
     * @throws Throwable
     */
    abstract protected function internalExecute(string|null $rawSql): void;

    /**
     * @throws InvalidParamException
     */
    protected function internalGetQueryResult(int $queryMode): mixed
    {
        if ($queryMode === self::QUERY_MODE_CURSOR) {
            return new DataReader($this);
        }

        if ($queryMode === self::QUERY_MODE_EXECUTE) {
            return $this->pdoStatement?->rowCount() ?? 0;
        }

        if ($this->is($queryMode, self::QUERY_MODE_ROW) || $this->is($queryMode, self::QUERY_MODE_SCALAR)) {
            /** @psalm-var array|false $result */
            $result = $this->pdoStatement?->fetch(PDO::FETCH_ASSOC);
        } elseif ($this->is($queryMode, self::QUERY_MODE_COLUMN)) {
            /** @psalm-var mixed $result */
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($this->is($queryMode, self::QUERY_MODE_ALL)) {
            /** @psalm-var mixed $result */
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw new InvalidParamException("Unknown query mode '$queryMode'");
        }

        $this->pdoStatement?->closeCursor();

        return $result;
    }

    /**
     * Logs the current database query if query logging is on and returns the profiling token if profiling is on.
     */
    protected function logQuery(string $rawSql, string $category): void
    {
        $this->logger?->log(LogLevel::INFO, $rawSql, [$category]);
    }

    protected function queryInternal(int $queryMode): mixed
    {
        $rawSql = $this->getRawSql();
        $logCategory = self::class . '::' . $this->getQueryMode($queryMode);

        $this->logQuery($rawSql, $logCategory);

        $queryContext = new CommandContext(__METHOD__, $logCategory, $this->getSql(), $this->getParams());

        $this->profiler?->begin($rawSql, $queryContext);
        try {
            /** @psalm-var mixed $result */
            $result = parent::queryInternal($queryMode);
        } catch (Exception $e) {
            $this->profiler?->end($rawSql, $queryContext->setException($e));
            throw $e;
        }
        $this->profiler?->end($rawSql, $queryContext);

        return $result;
    }

    /**
     * Refreshes table schema, which was marked by {@see requireTableSchemaRefresh()}.
     */
    protected function refreshTableSchema(): void
    {
        if ($this->refreshTableName !== null) {
            $this->db->getSchema()->refreshTableSchema($this->refreshTableName);
        }
    }
}
