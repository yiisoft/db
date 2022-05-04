<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use DateInterval;
use JsonException;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\AwareTrait\LoggerAwareTrait;
use Yiisoft\Db\AwareTrait\ProfilerAwareTrait;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

use function explode;
use function get_resource_type;
use function is_array;
use function is_bool;
use function is_object;
use function is_resource;
use function is_string;
use function stream_get_contents;
use function strncmp;
use function strtr;

/**
 * Command represents a SQL statement to be executed against a database.
 *
 * A command object is usually created by calling {@see ConnectionInterface::createCommand()}.
 *
 * The SQL statement it represents can be set via the {@see sql} property.
 *
 * To execute a non-query SQL (such as INSERT, DELETE, UPDATE), call {@see execute()}.
 * To execute a SQL statement that returns a result data set (such as SELECT), use {@see queryAll()},
 * {@see queryOne()}, {@see queryColumn()}, {@see queryScalar()}, or {@see query()}.
 *
 * For example,
 *
 * ```php
 * $users = $connectionInterface->createCommand('SELECT * FROM user')->queryAll();
 * ```
 *
 * Command supports SQL statement preparation and parameter binding.
 *
 * Call {@see bindValue()} to bind a value to a SQL parameter;
 * Call {@see bindParam()} to bind a PHP variable to a SQL parameter.
 *
 * When binding a parameter, the SQL statement is automatically prepared. You may also call {@see prepare()} explicitly
 * to prepare a SQL statement.
 *
 * Command also supports building SQL statements by providing methods such as {@see insert()}, {@see update()}, etc.
 *
 * For example, the following code will create and execute an INSERT SQL statement:
 *
 * ```php
 * $connectionInterface->createCommand()->insert('user', [
 *     'name' => 'Sam',
 *     'age' => 30,
 * ])->execute();
 * ```
 *
 * To build SELECT SQL statements, please use {@see QueryInterface} instead.
 *
 * For more details and usage information on Command, see the [guide article on Database Access Objects](guide:db-dao).
 *
 * @property string $rawSql The raw SQL with parameter values inserted into the corresponding placeholders in
 * {@see sql}.
 * @property string $sql The SQL statement to be executed.
 */
abstract class Command implements CommandInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    public const QUERY_MODE_NONE = 0;
    public const QUERY_MODE_ROW = 1;
    public const QUERY_MODE_ALL = 2;
    public const QUERY_MODE_CURSOR = 3;
    public const QUERY_MODE_COLUMN = 7;

    protected ?string $isolationLevel = null;
    protected ?string $refreshTableName = null;
    /** @var callable|null */
    protected $retryHandler = null;
    protected ?int $queryCacheDuration = null;
    private string $sql = '';
    protected ?Dependency $queryCacheDependency = null;
    protected array $params = [];

    public function __construct(protected QueryCache $queryCache)
    {
    }

    /**
     * Returns the cache key for the query.
     *
     * @param string $rawSql the raw SQL with parameter values inserted into the corresponding placeholders.
     *
     * @throws JsonException
     *
     * @return array the cache key.
     */
    abstract protected function getCacheKey(int $queryMode, string $rawSql): array;

    public function addCheck(string $name, string $table, string $expression): self
    {
        $sql = $this->queryBuilder()->addCheck($name, $table, $expression);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addColumn(string $table, string $column, string $type): self
    {
        $sql = $this->queryBuilder()->addColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws \Exception
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): self
    {
        $sql = $this->queryBuilder()->addCommentOnColumn($table, $column, $comment);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws \Exception
     */
    public function addCommentOnTable(string $table, string $comment): self
    {
        $sql = $this->queryBuilder()->addCommentOnTable($table, $comment);
        return $this->setSql($sql);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): self
    {
        $sql = $this->queryBuilder()->addDefaultValue($name, $table, $column, $value);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): self {
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

    public function addPrimaryKey(string $name, string $table, array|string $columns): self
    {
        $sql = $this->queryBuilder()->addPrimaryKey($name, $table, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addUnique(string $name, string $table, array|string $columns): self
    {
        $sql = $this->queryBuilder()->addUnique($name, $table, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function alterColumn(string $table, string $column, string $type): self
    {
        $sql = $this->queryBuilder()->alterColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function batchInsert(string $table, array $columns, iterable $rows): self
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

    public function cache(?int $duration = null, Dependency $dependency = null): self
    {
        $this->queryCacheDuration = $duration ?? $this->queryCache->getDuration();
        $this->queryCacheDependency = $dependency;
        return $this;
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function checkIntegrity(string $schema, string $table, bool $check = true): self
    {
        $sql = $this->queryBuilder()->checkIntegrity($schema, $table, $check);
        return $this->setSql($sql);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): self
    {
        $sql = $this->queryBuilder()->createIndex($name, $table, $columns, $unique);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function createTable(string $table, array $columns, ?string $options = null): self
    {
        $sql = $this->queryBuilder()->createTable($table, $columns, $options);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws Exception|InvalidConfigException|NotSupportedException
     */
    public function createView(string $viewName, QueryInterface|string $subquery): self
    {
        $sql = $this->queryBuilder()->createView($viewName, $subquery);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function delete(string $table, array|string $condition = '', array $params = []): self
    {
        $sql = $this->queryBuilder()->delete($table, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function dropCheck(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropCheck($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropColumn(string $table, string $column): self
    {
        $sql = $this->queryBuilder()->dropColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromColumn(string $table, string $column): self
    {
        $sql = $this->queryBuilder()->dropCommentFromColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromTable(string $table): self
    {
        $sql = $this->queryBuilder()->dropCommentFromTable($table);
        return $this->setSql($sql);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function dropDefaultValue(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropDefaultValue($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropForeignKey(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropForeignKey($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropIndex(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropIndex($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropPrimaryKey(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropPrimaryKey($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropTable(string $table): self
    {
        $sql = $this->queryBuilder()->dropTable($table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropUnique(string $name, string $table): self
    {
        $sql = $this->queryBuilder()->dropUnique($name, $table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropView(string $viewName): self
    {
        $sql = $this->queryBuilder()->dropView($viewName);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function executeResetSequence(string $table, array|int|string|null $value = null): self
    {
        return $this->resetSequence($table, $value);
    }

    public function getParams(): array
    {
        $buildParams = [];

        /** @psalm-var ParamInterface|array $value */
        foreach ($this->params as $name => $value) {
            if ($value instanceof ParamInterface) {
                /** @var mixed */
                $buildParams[$name] = $value->getValue();
            } else {
                /** @var mixed */
                $buildParams[$name] = $value;
            }
        }

        return $buildParams;
    }

    /**
     * @throws \Exception
     */
    public function getRawSql(): string
    {
        if (empty($this->params)) {
            return $this->sql;
        }

        $params = [];

        /** @var mixed $value */
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }

            if ($value instanceof ParamInterface) {
                /** @var mixed */
                $value = $value->getValue();
            }

            if (is_string($value)) {
                /** @var mixed */
                $params[$name] = $this->queryBuilder()->quoter()->quoteValue($value);
            } elseif (is_bool($value)) {
                /** @var string */
                $params[$name] = $value ? 'TRUE' : 'FALSE';
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif ((!is_object($value) && !is_resource($value)) || $value instanceof Expression) {
                /** @var mixed */
                $params[$name] = $value;
            }
        }

        if (!isset($params[1])) {
            return strtr($this->sql, $params);
        }

        $sql = '';

        foreach (explode('?', $this->sql) as $i => $part) {
            $sql .= (string) $params[$i] . $part;
        }

        return $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function insert(string $table, QueryInterface|array $columns): self
    {
        $params = [];
        $sql = $this->queryBuilder()->insert($table, $columns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    abstract public function insertEx(string $table, array $columns): bool|array;

    public function noCache(): self
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Executes the SQL statement.
     *
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     *
     * @throws Throwable
     * @throws Exception execution failed.
     *
     * @return int number of rows affected by the execution.
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function execute(): int
    {
        $sql = $this->getSql();

        if ($sql === '') {
            return 0;
        }

        return $this->queryInternal((int) static::QUERY_MODE_NONE);
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function query(): DataReader
    {
        return $this->queryInternal((int) static::QUERY_MODE_CURSOR);
    }

    public function queryAll(): array
    {
        /** @psalm-var array<array-key, array<mixed>>|null */
        $results = $this->queryInternal((int) static::QUERY_MODE_ALL);

        if (is_array($results)) {
            return $results;
        }

        return [];
    }

    public function queryColumn(): array|false
    {
        /** @psalm-var array<array-key, array<mixed>>|null */
        $results = $this->queryInternal((int) static::QUERY_MODE_COLUMN);

        if (is_array($results) && count($results)) {
            return $results;
        }

        return false;
    }

    public function queryOne(): array|false
    {
        /** @psalm-var array<array-key, array<mixed>>|null */
        $results = $this->queryInternal((int) static::QUERY_MODE_ROW);

        if (is_array($results) && count($results)) {
            return $results;
        }

        return false;
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function queryScalar(): bool|string|null|int|float
    {
        $firstRow = $this->queryInternal((int) static::QUERY_MODE_ROW);

        if (!is_array($firstRow)) {
            return false;
        }

        /** @var mixed */
        $result = current($firstRow);

        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return $result;
    }

    /**
     * @param int $queryMode - one from modes QUERY_MODE_*
     *
     * @throws Exception|Throwable
     *
     * @return mixed
     */
    protected function queryInternal(int $queryMode): mixed
    {
        if ($queryMode === static::QUERY_MODE_NONE || $queryMode === static::QUERY_MODE_CURSOR) {
            return $this->queryWithoutCache($this->getRawSql(), $queryMode);
        }

        return $this->queryWithCache($queryMode);
    }

    /**
     * Performs the actual DB query of a SQL statement.
     *
     * @param int $queryMode -  one from modes QUERY_MODE_*
     *
     * @throws Exception|Throwable If the query causes any problem.
     *
     * @return mixed The method execution result.
     */
    protected function queryWithCache(int $queryMode): mixed
    {
        $rawSql = $this->getRawSql();

        $cacheKey = $this->getCacheKey($queryMode, $rawSql);
        /** @psalm-var array{CacheInterface, DateInterval|int|null, Dependency|null} */
        $info = $this->queryCache->info($this->queryCacheDuration, $this->queryCacheDependency);
        /** @var mixed */
        $cacheResult = $this->getFromCacheInfo($info, $cacheKey);

        if ($cacheResult) {
            $this->logger?->log(LogLevel::DEBUG, 'Get query result from cache', [__CLASS__ . '::query']);
            return $cacheResult;
        }

        /** @var mixed */
        $result = $this->queryWithoutCache($rawSql, $queryMode);
        $this->setToCacheInfo($info, $cacheKey, $result);

        return $result;
    }

    protected function queryWithoutCache(string $rawSql, int $queryMode): mixed
    {
        $isReadMode = $this->isReadMode($queryMode);
        $logCategory = __CLASS__ . '::' . ($isReadMode ? 'query' : 'execute');

        $this->logQuery($rawSql, $logCategory);

        $this->prepare($isReadMode);
        try {
            $this->profiler?->begin($rawSql, [$logCategory]);

            $this->internalExecute($rawSql);

            /** @var mixed */
            $result = $this->internalGetQueryResult($queryMode);

            $this->profiler?->end($rawSql, [$logCategory]);

            if (!$isReadMode) {
                $this->refreshTableSchema();
            }
        } catch (Exception $e) {
            $this->profiler?->end($rawSql, [$logCategory]);
            throw $e;
        }

        return $result;
    }

    /**
     * Executes a prepared statement.
     *
     * @param string|null $rawSql the rawSql if it has been created.
     *
     * @throws Exception|Throwable
     */
    abstract protected function internalExecute(?string $rawSql): void;

    abstract protected function internalGetQueryResult(int $queryMode): mixed;

    public function renameColumn(string $table, string $oldName, string $newName): self
    {
        $sql = $this->queryBuilder()->renameColumn($table, $oldName, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function renameTable(string $table, string $newName): self
    {
        $sql = $this->queryBuilder()->renameTable($table, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function resetSequence(string $table, array|int|string|null $value = null): self
    {
        $sql = $this->queryBuilder()->resetSequence($table, $value);
        return $this->setSql($sql);
    }

    public function setParams(array $value): void
    {
        $this->params = $value;
    }

    public function setRawSql(string $sql): self
    {
        if ($sql !== $this->sql) {
            $this->cancel();
            $this->reset();
            $this->sql = $sql;
        }

        return $this;
    }

    public function setSql(string $sql): self
    {
        $this->cancel();
        $this->reset();
        $this->sql = $this->queryBuilder()->quoter()->quoteSql($sql);

        return $this;
    }

    public function truncateTable(string $table): self
    {
        $sql = $this->queryBuilder()->truncateTable($table);
        return $this->setSql($sql);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function update(string $table, array $columns, array|string $condition = '', array $params = []): self
    {
        $sql = $this->queryBuilder()->update($table, $columns, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * @throws Exception|InvalidConfigException|JsonException|NotSupportedException
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns = true,
        array $params = []
    ): self {
        $sql = $this->queryBuilder()->upsert($table, $insertColumns, $updateColumns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Logs the current database query if query logging is enabled and returns the profiling token if profiling is
     * enabled.
     *
     * @param string $rawSql
     * @param string $category The log category.
     */
    protected function logQuery(string $rawSql, string $category): void
    {
        $this->logger?->log(LogLevel::INFO, $rawSql, [$category]);
    }

    /**
     * Refreshes table schema, which was marked by {@see requireTableSchemaRefresh()}.
     */
    protected function refreshTableSchema(): void
    {
        if ($this->refreshTableName !== null) {
            $this->queryBuilder()->schema()->refreshTableSchema($this->refreshTableName);
        }
    }

    /**
     * Marks a specified table schema to be refreshed after command execution.
     *
     * @param string $name Name of the table, which schema should be refreshed.
     *
     * @return static
     */
    protected function requireTableSchemaRefresh(string $name): self
    {
        $this->refreshTableName = $name;
        return $this;
    }

    /**
     * Marks the command to be executed in transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see TransactionInterface::begin()} for details.
     *
     * @return static
     */
    protected function requireTransaction(?string $isolationLevel = null): self
    {
        $this->isolationLevel = $isolationLevel;
        return $this;
    }

    protected function reset(): void
    {
        $this->sql = '';
        $this->params = [];
        $this->refreshTableName = null;
        $this->isolationLevel = null;
        $this->retryHandler = null;
    }

    /**
     * Sets a callable (e.g. anonymous function) that is called when {@see Exception} is thrown when executing the
     * command. The signature of the callable should be:.
     *
     * ```php
     * function (Exceptions $e, $attempt)
     * {
     *     // return true or false (whether to retry the command or rethrow $e)
     * }
     * ```
     *
     * The callable will receive a database exception thrown and a current attempt (to execute the command) number
     * starting from 1.
     *
     * @param callable|null $handler A PHP callback to handle database exceptions.
     *
     * @return static
     */
    protected function setRetryHandler(?callable $handler): self
    {
        $this->retryHandler = $handler;
        return $this;
    }

    /**
     * @throws \JsonException
     *
     * @psalm-param array{CacheInterface, DateInterval|int|null, Dependency|null} $info
     */
    private function getFromCacheInfo(?array $info, array $cacheKey): mixed
    {
        if (!is_array($info)) {
            return null;
        }

        $cache = $info[0];

        /** @var mixed */
        $result = $cache->getOrSet(
            $cacheKey,
            static fn () => null,
        );

        if (is_array($result) && isset($result[0])) {
            $this->logger?->log(LogLevel::DEBUG, 'Query result served from cache', [__CLASS__ . '::query']);

            return $result[0];
        }

        return null;
    }

    /**
     * @throws \JsonException
     *
     * @psalm-param array{CacheInterface, DateInterval|int|null, Dependency|null} $info
     */
    private function setToCacheInfo(?array $info, array $cacheKey, mixed $result): void
    {
        if (!is_array($info)) {
            return;
        }

        $cache = $info[0];

        $cache->getOrSet(
            $cacheKey,
            static fn (): array => [$result],
            $info[1],
            $info[2]
        );

        $this->logger?->log(LogLevel::DEBUG, 'Saved query result in cache', [__CLASS__ . '::query']);
    }

    private function isReadMode(int $queryMode): bool
    {
        return $queryMode !== static::QUERY_MODE_NONE;
    }
}
