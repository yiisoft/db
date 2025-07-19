<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Command\AbstractCommand;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Profiler\Context\CommandContext;
use Yiisoft\Db\Profiler\ProfilerAwareInterface;
use Yiisoft\Db\Profiler\ProfilerAwareTrait;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_keys;
use function array_map;
use function restore_error_handler;
use function set_error_handler;
use function str_starts_with;

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
     * @var PdoConnectionInterface
     */
    protected readonly ConnectionInterface $db;

    /**
     * @var PDOStatement|null Represents a prepared statement and, after the statement is executed, an associated
     * result set.
     *
     * @link https://www.php.net/manual/en/class.pdostatement.php
     */
    protected PDOStatement|null $pdoStatement = null;

    /**
     * @param PdoConnectionInterface $db The PDO database connection to use.
     */
    public function __construct(PdoConnectionInterface $db)
    {
        parent::__construct($db);
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
            $dataType = $this->db->getSchema()->getDataType($value);
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
            $dataType = $this->db->getSchema()->getDataType($value);
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
         * @psalm-var array<string, int>|Param|int $value
         */
        foreach ($values as $name => $value) {
            if ($value instanceof Param) {
                $this->params[$name] = $value;
            } else {
                $type = $this->db->getSchema()->getDataType($value);
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

        $pdo = $this->db->getActivePdo($sql, $forRead);

        try {
            $this->pdoStatement = $pdo->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
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
            $this->pdoStatement?->bindValue($name, $value->value, $value->type);
        }
    }

    protected function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder()->withTypecasting($this->dbTypecasting);
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
            self::QUERY_MODE_ROW | self::QUERY_MODE_EXECUTE => 'insertReturningPks'
        };
    }

    /**
     * Executes a prepared statement.
     *
     * It's a wrapper around {@see PDOStatement::execute()} to support transactions and retry handlers.
     *
     * @throws Exception
     * @throws Throwable
     */
    protected function internalExecute(): void
    {
        for ($attempt = 0; ; ++$attempt) {
            try {
                set_error_handler(
                    static fn(int $errorNumber, string $errorString): bool =>
                        str_starts_with($errorString, 'Packets out of order. Expected '),
                    E_WARNING,
                );

                try {
                    $this->pdoStatement?->execute();
                } finally {
                    restore_error_handler();
                }
                break;
            } catch (PDOException $e) {
                $rawSql ??= $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function internalGetQueryResult(int $queryMode): mixed
    {
        if ($queryMode === self::QUERY_MODE_CURSOR) {
            /** @psalm-suppress PossiblyNullArgument */
            $dataReader = new PdoDataReader($this->pdoStatement);

            if ($this->phpTypecasting && ($row = $dataReader->current()) !== false) {
                /** @var array $row */
                $dataReader->typecastColumns($this->getResultColumns(array_keys($row)));
            }

            return $dataReader;
        }

        if ($queryMode === self::QUERY_MODE_EXECUTE) {
            return $this->pdoStatement?->rowCount() ?? 0;
        }

        if ($this->is($queryMode, self::QUERY_MODE_ROW)) {
            /** @psalm-var array|false $result */
            $result = $this->pdoStatement?->fetch(PDO::FETCH_ASSOC);

            if ($this->phpTypecasting && $result !== false) {
                $result = $this->phpTypecastRows([$result])[0];
            }
        } elseif ($this->is($queryMode, self::QUERY_MODE_SCALAR)) {
            /** @psalm-var mixed $result */
            $result = $this->pdoStatement?->fetchColumn();

            if (
                $this->phpTypecasting
                && $result !== false
                && ($column = $this->getResultColumn(0)) !== null
            ) {
                $result = $column->phpTypecast($result);
            }
        } elseif ($this->is($queryMode, self::QUERY_MODE_COLUMN)) {
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_COLUMN);

            if (
                $this->phpTypecasting
                && !empty($result)
                && ($column = $this->getResultColumn(0)) !== null
            ) {
                $result = array_map($column->phpTypecast(...), $result);
            }
        } elseif ($this->is($queryMode, self::QUERY_MODE_ALL)) {
            $result = $this->pdoStatement?->fetchAll(PDO::FETCH_ASSOC);

            if ($this->phpTypecasting && !empty($result)) {
                $result = $this->phpTypecastRows($result);
            }
        } else {
            throw new InvalidArgumentException("Unknown query mode '$queryMode'");
        }

        $this->pdoStatement?->closeCursor();

        return $result;
    }

    protected function queryInternal(int $queryMode): mixed
    {
        $logCategory = self::class . '::' . $this->getQueryMode($queryMode);

        $this->logger?->log(LogLevel::INFO, $rawSql = $this->getRawSql(), [$logCategory, 'type' => LogType::QUERY]);

        $queryContext = new CommandContext(__METHOD__, $logCategory, $this->getSql(), $this->getParams());

        /** @psalm-var string|null $rawSql */
        $this->profiler?->begin($rawSql ??= $this->getRawSql(), $queryContext);
        /** @psalm-var string $rawSql */
        try {
            /** @psalm-var mixed $result */
            $result = parent::queryInternal($queryMode);
        } catch (Throwable $e) {
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

    /**
     * Returns the column instance from the query result by the index, or `null` if the column type cannot be determined.
     */
    private function getResultColumn(int $index): ColumnInterface|null
    {
        $metadata = $this->pdoStatement?->getColumnMeta($index);

        if (empty($metadata)) {
            return null;
        }

        return $this->db->getSchema()->getResultColumn($metadata);
    }

    /**
     * Returns column instances with keys from the query result.
     *
     * @return ColumnInterface[]
     *
     * @psalm-param array<int, string|int> $keys
     */
    private function getResultColumns(array $keys): array
    {
        $columns = [];

        foreach ($keys as $i => $key) {
            $column = $this->getResultColumn($i);

            if ($column !== null) {
                $columns[$key] = $column;
            }
        }

        return $columns;
    }

    /**
     * Typecasts rows from the query result to PHP types according to the column types.
     *
     * @param array[] $rows
     */
    private function phpTypecastRows(array $rows): array
    {
        $keys = array_keys($rows[0]);
        $columns = $this->getResultColumns($keys);

        if (empty($columns)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($columns as $key => $column) {
                $row[$key] = $column->phpTypecast($row[$key]);
            }
        }

        return $rows;
    }
}
