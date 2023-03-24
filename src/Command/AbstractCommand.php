<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Closure;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Profiler\ProfilerAwareTrait;
use Yiisoft\Db\Profiler\Context\CommandContext;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function current;
use function explode;
use function get_resource_type;
use function is_array;
use function is_bool;
use function is_int;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function preg_replace_callback;
use function stream_get_contents;
use function strncmp;

/**
 * Represents an SQL statement to be executed against a database.
 *
 * A command object is usually created by calling {@see \Yiisoft\Db\Connection\ConnectionInterface::createCommand()}.
 *
 * The SQL statement it represents can be set via the {@see sql} property.
 *
 * To execute a non-query SQL (such as INSERT, DELETE, UPDATE), call {@see execute()}.
 *
 * To execute a SQL statement that returns a result data set (such as SELECT), use {@see queryAll()}, {@see queryOne()},
 * {@see queryColumn()}, {@see queryScalar()}, or {@see query()}.
 *
 * For example,
 *
 * ```php
 * $users = $connectionInterface->createCommand('SELECT * FROM user')->queryAll();
 * ```
 *
 * Abstract command supports SQL statement preparation and parameter binding.
 *
 * Call {@see bindValue()} to bind a value to a SQL parameter.
 * Call {@see bindParam()} to bind a PHP variable to a SQL parameter.
 *
 * When binding a parameter, the SQL statement is automatically prepared. You may also call {@see prepare()} explicitly
 * to prepare a SQL statement.
 *
 * Abstract command also supports building SQL statements by providing methods such as {@see insert()}, {@see update()},
 * etc.
 *
 * For example, the following code will create and execute an INSERT SQL statement:
 *
 * ```php
 * $connectionInterface->createCommand()->insert(
 *     'user',
 *     ['name' => 'Sam', 'age' => 30],
 * )->execute();
 * ```
 *
 * To build SELECT SQL statements, please use {@see QueryInterface} instead.
 */
abstract class AbstractCommand implements CommandInterface
{
    protected const QUERY_MODE_EXECUTE = 1;
    protected const QUERY_MODE_ROW = 2;
    protected const QUERY_MODE_ALL = 4;
    protected const QUERY_MODE_COLUMN = 8;
    protected const QUERY_MODE_CURSOR = 16;

    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    protected string|null $isolationLevel = null;
    /** @psalm-var ParamInterface[] */
    protected array $params = [];
    protected string|null $refreshTableName = null;
    protected Closure|null $retryHandler = null;
    /** @var string The SQL statement to be executed */
    private string $sql = '';

    public function addCheck(string $name, string $table, string $expression): static
    {
        $sql = $this->queryBuilder()->addCheck($name, $table, $expression);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addColumn(string $table, string $column, string $type): static
    {
        $sql = $this->queryBuilder()->addColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): static
    {
        $sql = $this->queryBuilder()->addCommentOnColumn($table, $column, $comment);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addCommentOnTable(string $table, string $comment): static
    {
        $sql = $this->queryBuilder()->addCommentOnTable($table, $comment);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addDefaultValue(string $name, string $table, string $column, mixed $value): static
    {
        $sql = $this->queryBuilder()->addDefaultValue($name, $table, $column, $value);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string $delete = null,
        string $update = null
    ): static {
        $sql = $this->queryBuilder()->addForeignKey(
            $name,
            $table,
            $columns,
            $refTable,
            $refColumns,
            $delete,
            $update
        );
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addPrimaryKey(string $name, string $table, array|string $columns): static
    {
        $sql = $this->queryBuilder()->addPrimaryKey($name, $table, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addUnique(string $name, string $table, array|string $columns): static
    {
        $sql = $this->queryBuilder()->addUnique($name, $table, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function alterColumn(string $table, string $column, string $type): static
    {
        $sql = $this->queryBuilder()->alterColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function batchInsert(string $table, array $columns, iterable $rows): static
    {
        $table = $this->queryBuilder()->quoter()->quoteSql($table);

        /** @psalm-var string[] $columns */
        foreach ($columns as &$column) {
            $column = $this->queryBuilder()->quoter()->quoteSql($column);
        }

        unset($column);

        $params = [];
        $sql = $this->queryBuilder()->batchInsert($table, $columns, $rows, $params);

        $this->setRawSql($sql);
        $this->bindValues($params);

        return $this;
    }

    abstract public function bindValue(int|string $name, mixed $value, int $dataType = null): static;

    abstract public function bindValues(array $values): static;

    public function checkIntegrity(string $schema, string $table, bool $check = true): static
    {
        $sql = $this->queryBuilder()->checkIntegrity($schema, $table, $check);
        return $this->setSql($sql);
    }

    public function createIndex(
        string $name,
        string $table,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): static {
        $sql = $this->queryBuilder()->createIndex($name, $table, $columns, $indexType, $indexMethod);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function createTable(string $table, array $columns, string $options = null): static
    {
        $sql = $this->queryBuilder()->createTable($table, $columns, $options);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): static
    {
        $sql = $this->queryBuilder()->createView($viewName, $subQuery);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    public function delete(string $table, array|string $condition = '', array $params = []): static
    {
        $sql = $this->queryBuilder()->delete($table, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function dropCheck(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropCheck($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropColumn(string $table, string $column): static
    {
        $sql = $this->queryBuilder()->dropColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromColumn(string $table, string $column): static
    {
        $sql = $this->queryBuilder()->dropCommentFromColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromTable(string $table): static
    {
        $sql = $this->queryBuilder()->dropCommentFromTable($table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropDefaultValue(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropDefaultValue($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropForeignKey(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropForeignKey($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropIndex(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropIndex($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropPrimaryKey(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropPrimaryKey($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropTable(string $table): static
    {
        $sql = $this->queryBuilder()->dropTable($table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropUnique(string $name, string $table): static
    {
        $sql = $this->queryBuilder()->dropUnique($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropView(string $viewName): static
    {
        $sql = $this->queryBuilder()->dropView($viewName);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    public function getParams(bool $asValues = true): array
    {
        if (!$asValues) {
            return $this->params;
        }

        $buildParams = [];

        foreach ($this->params as $name => $value) {
            /** @psalm-var mixed */
            $buildParams[$name] = $value->getValue();
        }

        return $buildParams;
    }

    public function getRawSql(): string
    {
        if (empty($this->params)) {
            return $this->sql;
        }

        $params = [];

        /** @psalm-var mixed $value */
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }

            if ($value instanceof ParamInterface) {
                /** @psalm-var mixed $value */
                $value = $value->getValue();
            }

            if (is_string($value)) {
                /** @psalm-var mixed */
                $params[$name] = $this->queryBuilder()->quoter()->quoteValue($value);
            } elseif (is_bool($value)) {
                /** @psalm-var string */
                $params[$name] = $value ? 'TRUE' : 'FALSE';
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif ((!is_object($value) && !is_resource($value)) || $value instanceof Expression) {
                /** @psalm-var mixed */
                $params[$name] = $value;
            }
        }

        if (!isset($params[0])) {
            return preg_replace_callback('#(:\w+)#', static function (array $matches) use ($params): string {
                $m = $matches[1];
                return (string) ($params[$m] ?? $m);
            }, $this->sql);
        }

        // Support unnamed placeholders should be dropped
        $sql = '';

        foreach (explode('?', $this->sql) as $i => $part) {
            $sql .= $part . (string) ($params[$i] ?? '');
        }

        return $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function insert(string $table, QueryInterface|array $columns): static
    {
        $params = [];
        $sql = $this->queryBuilder()->insert($table, $columns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        $params = [];

        $sql = $this->queryBuilder()->insertWithReturningPks($table, $columns, $params);

        $this->setSql($sql)->bindValues($params);

        /** @psalm-var array|bool $result */
        $result = $this->queryInternal(self::QUERY_MODE_ROW | self::QUERY_MODE_EXECUTE);

        return is_array($result) ? $result : false;
    }

    public function execute(): int
    {
        $sql = $this->getSql();

        if ($sql === '') {
            return 0;
        }

        /** @psalm-var int|bool $execute */
        $execute = $this->queryInternal(self::QUERY_MODE_EXECUTE);

        return is_int($execute) ? $execute : 0;
    }

    public function query(): DataReaderInterface
    {
        /** @psalm-var DataReaderInterface */
        return $this->queryInternal(self::QUERY_MODE_CURSOR);
    }

    public function queryAll(): array
    {
        /** @psalm-var array<array-key, array>|null $results */
        $results = $this->queryInternal(self::QUERY_MODE_ALL);
        return $results ?? [];
    }

    public function queryColumn(): array
    {
        /** @psalm-var mixed $results */
        $results = $this->queryInternal(self::QUERY_MODE_COLUMN);
        return is_array($results) ? $results : [];
    }

    public function queryOne(): array|null
    {
        /** @psalm-var mixed $results */
        $results = $this->queryInternal(self::QUERY_MODE_ROW);
        return is_array($results) ? $results : null;
    }

    public function queryScalar(): bool|string|null|int|float
    {
        /** @psalm-var array|false $firstRow */
        $firstRow = $this->queryInternal(self::QUERY_MODE_ROW);

        if (!$firstRow) {
            return false;
        }

        /** @psalm-var mixed $result */
        $result = current($firstRow);

        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return is_scalar($result) ? $result : null;
    }

    public function renameColumn(string $table, string $oldName, string $newName): static
    {
        $sql = $this->queryBuilder()->renameColumn($table, $oldName, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function renameTable(string $table, string $newName): static
    {
        $sql = $this->queryBuilder()->renameTable($table, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function resetSequence(string $table, int|string $value = null): static
    {
        $sql = $this->queryBuilder()->resetSequence($table, $value);
        return $this->setSql($sql);
    }

    public function setRawSql(string $sql): static
    {
        if ($sql !== $this->sql) {
            $this->cancel();
            $this->reset();
            $this->sql = $sql;
        }

        return $this;
    }

    public function setSql(string $sql): static
    {
        $this->cancel();
        $this->reset();
        $this->sql = $this->queryBuilder()->quoter()->quoteSql($sql);
        return $this;
    }

    public function setRetryHandler(Closure|null $handler): static
    {
        $this->retryHandler = $handler;
        return $this;
    }

    public function truncateTable(string $table): static
    {
        $sql = $this->queryBuilder()->truncateTable($table);
        return $this->setSql($sql);
    }

    public function update(string $table, array $columns, array|string $condition = '', array $params = []): static
    {
        $sql = $this->queryBuilder()->update($table, $columns, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns = true,
        array $params = []
    ): static {
        $sql = $this->queryBuilder()->upsert($table, $insertColumns, $updateColumns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Returns the query result.
     *
     * @param int $queryMode One from modes QUERY_MODE_*.
     *
     * @throws Exception
     * @throws Throwable
     */
    abstract protected function internalGetQueryResult(int $queryMode): mixed;

    /**
     * Executes a prepared statement.
     *
     * @param string|null $rawSql The rawSql if it has been created.
     *
     * @throws Exception
     * @throws Throwable
     */
    abstract protected function internalExecute(string|null $rawSql): void;

    protected function is(int $value, int $flag): bool
    {
        return ($value & $flag) === $flag;
    }

    /**
     * Logs the current database query if query logging is enabled and returns the profiling token if profiling is
     * enabled.
     */
    protected function logQuery(string $rawSql, string $category): void
    {
        $this->logger?->log(LogLevel::INFO, $rawSql, [$category]);
    }

    /**
     * The method is called after the query is executed.
     *
     * @param int $queryMode One from modes QUERY_MODE_*.
     *
     * @throws Exception
     * @throws Throwable
     */
    protected function queryInternal(int $queryMode): mixed
    {
        $rawSql = $this->getRawSql();
        $isReadMode = $this->isReadMode($queryMode);

        $logCategory = self::class . '::' . ($isReadMode ? 'query' : 'execute');
        $queryContext = new CommandContext(__METHOD__, $logCategory, $this->getSql(), $this->getParams());

        $this->logQuery($rawSql, $logCategory);
        $this->prepare($isReadMode);

        try {
            $this->profiler?->begin($rawSql, $queryContext);

            $this->internalExecute($rawSql);

            /** @psalm-var mixed $result */
            $result = $this->internalGetQueryResult($queryMode);

            $this->profiler?->end($rawSql, $queryContext);

            if (!$isReadMode) {
                $this->refreshTableSchema();
            }
        } catch (Exception $e) {
            $this->profiler?->end($rawSql, $queryContext->setException($e));
            throw $e;
        }

        return $result;
    }

    /**
     * Refreshes table schema, which was marked by {@see requireTableSchemaRefresh()}.
     */
    abstract protected function refreshTableSchema(): void;

    /**
     * Marks a specified table schema to be refreshed after command execution.
     *
     * @param string $name Name of the table, which schema should be refreshed.
     */
    protected function requireTableSchemaRefresh(string $name): static
    {
        $this->refreshTableName = $name;
        return $this;
    }

    /**
     * Marks the command to be executed in transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see \Yiisoft\Db\Transaction\TransactionInterface::begin()} for details.
     */
    protected function requireTransaction(string $isolationLevel = null): static
    {
        $this->isolationLevel = $isolationLevel;
        return $this;
    }

    /**
     * Resets the command object, so it can be reused to build another SQL statement.
     */
    protected function reset(): void
    {
        $this->sql = '';
        $this->params = [];
        $this->refreshTableName = null;
        $this->isolationLevel = null;
        $this->retryHandler = null;
    }

    /**
     * Checks if the query mode is a read mode.
     */
    private function isReadMode(int $queryMode): bool
    {
        return !$this->is($queryMode, self::QUERY_MODE_EXECUTE);
    }
}
