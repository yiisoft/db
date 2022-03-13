<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use JsonException;
use PDO;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
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
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

use function array_map;
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
    use CommandPdoTrait;
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    protected ?string $isolationLevel = null;
    protected ?string $refreshTableName = null;
    /** @var callable|null */
    protected $retryHandler = null;
    private int $fetchMode = PDO::FETCH_ASSOC;
    private ?int $queryCacheDuration = null;
    private string $sql = '';
    private ?Dependency $queryCacheDependency = null;

    public function __construct(private QueryCache $queryCache)
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
    abstract protected function getCacheKey(string $rawSql): array;

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
        $columns = array_map(fn ($column) => $this->queryBuilder()->quoter()->quoteSql($column), $columns);
        $params = [];
        $sql = $this->queryBuilder()->batchInsert($table, $columns, $rows, $params);

        $this->setRawSql($sql);
        $this->bindValues($params);

        return $this;
    }

    public function bindValue(int|string $name, mixed $value, ?int $dataType = null): self
    {
        if ($dataType === null) {
            $dataType = $this->queryBuilder()->schema()->getPdoType($value);
        }

        $this->params[$name] = new Param($name, $value, $dataType);

        return $this;
    }

    public function bindValues(array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        foreach ($values as $name => $value) {
            if ($value instanceof ParamInterface) {
                $this->params[$value->getName()] = $value;
            } elseif (is_array($value)) { // TODO: Drop in Yii 2.1
                $this->params[$name] = new Param($name, ...$value);
            } elseif ($value instanceof PdoValue && is_int($value->getType())) {
                $this->params[$name] = new Param($name, $value->getValue(), $value->getType());
            } else {
                $type = $this->queryBuilder()->schema()->getPdoType($value);
                $this->params[$name] = new Param($name, $value, $type);
            }
        }

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
     * Executes the SQL statement.
     *
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     *
     * @throws Throwable
     * @throws Exception execution failed.
     *
     * @return int number of rows affected by the execution.
     */
    public function execute(): int
    {
        $sql = $this->getSql();

        [, $rawSql] = $this->logQuery(__METHOD__);

        if ($sql === '') {
            return 0;
        }

        $this->prepare(false);

        try {
            $this->profiler?->begin((string)$rawSql, [__METHOD__]);

            $this->internalExecute($rawSql);
            $n = $this->pdoStatement?->rowCount();

            $this->profiler?->end((string)$rawSql, [__METHOD__]);

            $this->refreshTableSchema();

            return $n ?? 0;
        } catch (Exception $e) {
            $this->profiler?->end((string)$rawSql, [__METHOD__]);
            throw $e;
        }
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function executeResetSequence(string $table, mixed $value = null): self
    {
        return $this->resetSequence($table, $value);
    }

    public function getParams(): array
    {
        return array_map(static fn (mixed $value): mixed => $value->getValue(), $this->params);
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

        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }

            if ($value instanceof ParamInterface) {
                $value = $value->getValue();
            }

            if (is_string($value)) {
                $params[$name] = $this->queryBuilder()->quoter()->quoteValue($value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif ((!is_object($value) && !is_resource($value)) || $value instanceof Expression) {
                $params[$name] = $value;
            }
        }

        if (!isset($params[1])) {
            return strtr($this->sql, $params);
        }

        $sql = '';

        foreach (explode('?', $this->sql) as $i => $part) {
            $sql .= ($params[$i] ?? '') . $part;
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

    public function query(): DataReader
    {
        return $this->queryInternal(true);
    }

    public function queryAll(): array
    {
        return $this->queryInternal();
    }

    public function queryColumn(): array
    {
        $results = $this->queryInternal();

        $columnName = array_keys($results[0] ?? [])[0] ?? null;

        if ($columnName) {
            return ArrayHelper::getColumn($results,  $columnName);
        }

        return [];
    }

    public function queryOne(): mixed
    {
        return current($this->queryInternal());
    }

    public function queryScalar(): bool|string|null|int
    {
        $firstRow = current($this->queryInternal());
        if (!is_array($firstRow)) {
            return false;
        }

        $result = current($firstRow);

        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return $result;
    }

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
    public function resetSequence(string $table, mixed $value = null): self
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
     * @param string $category The log category.
     *
     * @throws \Exception
     *
     * @return array Two elements, the first is boolean of whether profiling is enabled or not. The second is
     * the rawSql if it has been created.
     */
    protected function logQuery(string $category): array
    {
        if ($this->logger !== null) {
            $rawSql = $this->getRawSql();
            $this->logger->log(LogLevel::INFO, $rawSql, [$category]);
        }

        if ($this->profiler === null) {
            return [false, $rawSql ?? null];
        }

        return [true, $rawSql ?? $this->getRawSql()];
    }

    /**
     * Performs the actual DB query of a SQL statement.
     *
     * @param bool $returnDataReader - return results as DataReader
     *
     * @throws Exception|Throwable If the query causes any problem.
     *
     * @return mixed The method execution result.
     */
    protected function queryInternal(bool $returnDataReader = false): mixed
    {
        [, $rawSql] = $this->logQuery(__CLASS__ . '::query');

        if (!$returnDataReader) {
            $info = $this->queryCache->info($this->queryCacheDuration, $this->queryCacheDependency);

            if (is_array($info)) {
                /* @var $cache CacheInterface */
                $cache = $info[0];
                $rawSql = $rawSql ?: $this->getRawSql();
                $cacheKey = $this->getCacheKey($rawSql);
                $result = $cache->getOrSet(
                    $cacheKey,
                    static fn () => null,
                );

                if (is_array($result) && isset($result[0])) {
                    $this->logger?->log(LogLevel::DEBUG, 'Query result served from cache', [__CLASS__ . '::query']);

                    return $result[0];
                }
            }
        }

        $this->prepare(true);

        try {
            $this->profiler?->begin((string)$rawSql, [__CLASS__ . '::query']);

            $this->internalExecute($rawSql);

            if ($returnDataReader) {
                $result = new DataReader($this);
            } else {
                $result = $this->pdoStatement?->fetchAll($this->fetchMode);
                $this->pdoStatement?->closeCursor();
            }

            $this->profiler?->end((string)$rawSql, [__CLASS__ . '::query']);
        } catch (Exception $e) {
            $this->profiler?->end((string)$rawSql, [__CLASS__ . '::query']);
            throw $e;
        }

        if (isset($cache, $cacheKey, $info)) {
            $cache->getOrSet(
                $cacheKey,
                static fn (): array => [$result],
                $info[1],
                $info[2]
            );

            $this->logger?->log(LogLevel::DEBUG, 'Saved query result in cache', [__CLASS__ . '::query']);
        }

        return $result;
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
}
