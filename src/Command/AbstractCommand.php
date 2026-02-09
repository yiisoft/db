<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Closure;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Query\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\DMLQueryBuilderInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_map;
use function explode;
use function get_resource_type;
use function is_array;
use function is_int;
use function is_resource;
use function is_scalar;
use function stream_get_contents;

/**
 * Represents an SQL statement to execute in a database.
 *
 * It's usually created by calling {@see ConnectionInterface::createCommand()}.
 *
 * You can get the SQL statement it represents via the {@see getSql()} method.
 *
 * To execute a non-query SQL (such as `INSERT`, `DELETE`, `UPDATE`), call {@see execute()}.
 *
 * To execute a SQL statement that returns a result (such as `SELECT`), use {@see queryAll()}, {@see queryOne()},
 * {@see queryColumn()}, {@see queryScalar()}, or {@see query()}.
 *
 * For example,
 *
 * ```php
 * $users = $connectionInterface->createCommand('SELECT * FROM user')->queryAll();
 * ```
 *
 * Abstract command supports SQL prepared statements and parameter binding.
 *
 * Call {@see bindValue()} to bind a value to a SQL parameter.
 * Call {@see bindParam()} to bind a PHP variable to a SQL parameter.
 *
 * When binding a parameter, the SQL statement is automatically prepared. You may also call {@see prepare()} explicitly
 * to do it.
 *
 * Abstract command supports building some SQL statements using methods such as {@see insert()}, {@see update()}, {@see delete()},
 * etc.
 *
 * For example, the following code will create and execute an `INSERT` SQL statement:
 *
 * ```php
 * $connectionInterface->createCommand()->insert(
 *     'user',
 *     ['name' => 'Sam', 'age' => 30],
 * )->execute();
 * ```
 *
 * To build `SELECT` SQL statements, please use {@see QueryInterface} and its implementations instead.
 *
 * @psalm-import-type BatchValues from DMLQueryBuilderInterface
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Command in this query mode returns count of affected rows.
     *
     * @see execute()
     */
    protected const QUERY_MODE_EXECUTE = 1;
    /**
     * Command in this query mode returns the first row of selected data.
     *
     * @see queryOne()
     */
    protected const QUERY_MODE_ROW = 2;
    /**
     * Command in this query mode returns all rows of selected data.
     *
     * @see queryAll()
     */
    protected const QUERY_MODE_ALL = 4;
    /**
     * Command in this query mode returns all rows with the first column of selected data.
     *
     * @see queryColumn()
     */
    protected const QUERY_MODE_COLUMN = 8;
    /**
     * Command in this query mode returns {@see DataReaderInterface}, an abstraction for database cursor for
     * selected data.
     *
     * @see query()
     */
    protected const QUERY_MODE_CURSOR = 16;
    /**
     * Command in this query mode returns the first column in the first row of the query result
     *
     * @see queryScalar()
     */
    protected const QUERY_MODE_SCALAR = 32;

    /**
     * @var Param[] Parameters to use.
     */
    protected array $params = [];

    /**
     * @var string|null Name of the table to refresh schema for. Null means not to refresh the schema.
     */
    protected ?string $refreshTableName = null;
    protected ?Closure $retryHandler = null;
    protected bool $dbTypecasting = true;
    protected bool $phpTypecasting = false;
    /**
     * @var string The SQL statement to execute.
     */
    private string $sql = '';

    /**
     * @param ConnectionInterface $db The database connection to use.
     */
    public function __construct(
        protected readonly ConnectionInterface $db,
    ) {}

    public function addCheck(string $table, string $name, string $expression): static
    {
        $sql = $this->getQueryBuilder()->addCheck($table, $name, $expression);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addColumn(string $table, string $column, ColumnInterface|string $type): static
    {
        $sql = $this->getQueryBuilder()->addColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): static
    {
        $sql = $this->getQueryBuilder()->addCommentOnColumn($table, $column, $comment);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addCommentOnTable(string $table, string $comment): static
    {
        $sql = $this->getQueryBuilder()->addCommentOnTable($table, $comment);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addDefaultValue(string $table, string $name, string $column, mixed $value): static
    {
        $sql = $this->getQueryBuilder()->addDefaultValue($table, $name, $column, $value);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        ?string $delete = null,
        ?string $update = null,
    ): static {
        $sql = $this->getQueryBuilder()->addForeignKey(
            $table,
            $name,
            $columns,
            $referenceTable,
            $referenceColumns,
            $delete,
            $update,
        );
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addPrimaryKey(string $table, string $name, array|string $columns): static
    {
        $sql = $this->getQueryBuilder()->addPrimaryKey($table, $name, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function addUnique(string $table, string $name, array|string $columns): static
    {
        $sql = $this->getQueryBuilder()->addUnique($table, $name, $columns);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function alterColumn(string $table, string $column, ColumnInterface|string $type): static
    {
        $sql = $this->getQueryBuilder()->alterColumn($table, $column, $type);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function insertBatch(string $table, iterable $rows, array $columns = []): static
    {
        $table = $this->getQueryBuilder()->getQuoter()->getRawTableName($table);

        $params = [];
        $sql = $this->getQueryBuilder()->insertBatch($table, $rows, $columns, $params);

        $this->setRawSql($sql);
        $this->bindValues($params);

        return $this;
    }

    abstract public function bindValue(int|string $name, mixed $value, ?int $dataType = null): static;

    abstract public function bindValues(array $values): static;

    public function checkIntegrity(string $schema, string $table, bool $check = true): static
    {
        $sql = $this->getQueryBuilder()->checkIntegrity($schema, $table, $check);
        return $this->setSql($sql);
    }

    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        ?string $indexType = null,
        ?string $indexMethod = null,
    ): static {
        $sql = $this->getQueryBuilder()->createIndex($table, $name, $columns, $indexType, $indexMethod);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function createTable(string $table, array $columns, ?string $options = null): static
    {
        $sql = $this->getQueryBuilder()->createTable($table, $columns, $options);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): static
    {
        $sql = $this->getQueryBuilder()->createView($viewName, $subQuery);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    public function delete(string $table, array|string $condition = '', array $params = []): static
    {
        $sql = $this->getQueryBuilder()->delete($table, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function dropCheck(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropCheck($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropColumn(string $table, string $column): static
    {
        $sql = $this->getQueryBuilder()->dropColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromColumn(string $table, string $column): static
    {
        $sql = $this->getQueryBuilder()->dropCommentFromColumn($table, $column);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropCommentFromTable(string $table): static
    {
        $sql = $this->getQueryBuilder()->dropCommentFromTable($table);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropDefaultValue(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropDefaultValue($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropForeignKey(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropForeignKey($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropIndex(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropIndex($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropPrimaryKey(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropPrimaryKey($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): static
    {
        $sql = $this->getQueryBuilder()->dropTable($table, $ifExists, $cascade);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropUnique(string $table, string $name): static
    {
        $sql = $this->getQueryBuilder()->dropUnique($table, $name);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function dropView(string $viewName): static
    {
        $sql = $this->getQueryBuilder()->dropView($viewName);
        return $this->setSql($sql)->requireTableSchemaRefresh($viewName);
    }

    public function getParams(bool $asValues = true): array
    {
        if (!$asValues) {
            return $this->params;
        }

        $buildParams = [];

        foreach ($this->params as $name => $value) {
            $buildParams[$name] = $value->value;
        }

        return $buildParams;
    }

    public function getRawSql(): string
    {
        if (empty($this->params)) {
            return $this->sql;
        }

        $queryBuilder = $this->getQueryBuilder();
        $params = array_map($queryBuilder->prepareParam(...), $this->params);

        if (!isset($params[0])) {
            return $queryBuilder->replacePlaceholders($this->sql, $params);
        }

        // Support unnamed placeholders should be dropped
        $sql = '';

        foreach (explode('?', $this->sql) as $i => $part) {
            $sql .= $part . ($params[$i] ?? '');
        }

        return $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function insert(string $table, array|QueryInterface $columns): static
    {
        $params = [];
        $sql = $this->getQueryBuilder()->insert($table, $columns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function insertReturningPks(string $table, array|QueryInterface $columns): array
    {
        if (empty($this->db->getSchema()->getTableSchema($table)?->getPrimaryKey())) {
            $this->insert($table, $columns)->execute();
            return [];
        }

        $params = [];
        $sql = $this->getQueryBuilder()->insertReturningPks($table, $columns, $params);

        $this->setSql($sql)->bindValues($params);

        /** @psalm-var array<string, mixed> */
        return $this->queryInternal(self::QUERY_MODE_ROW | self::QUERY_MODE_EXECUTE);
    }

    public function execute(): int
    {
        $sql = $this->getSql();

        if ($sql === '') {
            return 0;
        }

        /** @var bool|int $execute */
        $execute = $this->queryInternal(self::QUERY_MODE_EXECUTE);

        return is_int($execute) ? $execute : 0;
    }

    public function query(): DataReaderInterface
    {
        /** @var DataReaderInterface */
        return $this->queryInternal(self::QUERY_MODE_CURSOR);
    }

    public function queryAll(): array
    {
        /** @psalm-var list<array>|null $results */
        $results = $this->queryInternal(self::QUERY_MODE_ALL);
        return $results ?? [];
    }

    public function queryColumn(): array
    {
        $results = $this->queryInternal(self::QUERY_MODE_COLUMN);
        return is_array($results) ? $results : [];
    }

    public function queryOne(): ?array
    {
        /** @psalm-var array<string,mixed>|false $results */
        $results = $this->queryInternal(self::QUERY_MODE_ROW);
        return is_array($results) ? $results : null;
    }

    public function queryScalar(): bool|string|int|float|null
    {
        $result = $this->queryInternal(self::QUERY_MODE_SCALAR);

        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return is_scalar($result) ? $result : null;
    }

    public function renameColumn(string $table, string $oldName, string $newName): static
    {
        $sql = $this->getQueryBuilder()->renameColumn($table, $oldName, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function renameTable(string $table, string $newName): static
    {
        $sql = $this->getQueryBuilder()->renameTable($table, $newName);
        return $this->setSql($sql)->requireTableSchemaRefresh($table);
    }

    public function resetSequence(string $table, int|string|null $value = null): static
    {
        $sql = $this->getQueryBuilder()->resetSequence($table, $value);
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
        $this->sql = $this->getQueryBuilder()->getQuoter()->quoteSql($sql);
        return $this;
    }

    public function setRetryHandler(?Closure $handler): static
    {
        $this->retryHandler = $handler;
        return $this;
    }

    public function truncateTable(string $table): static
    {
        $sql = $this->getQueryBuilder()->truncateTable($table);
        return $this->setSql($sql);
    }

    public function update(
        string $table,
        array $columns,
        array|ExpressionInterface|string $condition = '',
        array|ExpressionInterface|string|null $from = null,
        array $params = [],
    ): static {
        $sql = $this->getQueryBuilder()->update($table, $columns, $condition, $from, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        ?array $constraintColumns = null,
    ): static {
        $params = [];
        $sql = $this->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $constraintColumns, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        ?array $constraintColumns = null,
        ?array $returnColumns = null,
    ): array {
        if ($returnColumns === []) {
            $this->upsert($table, $insertColumns, $updateColumns, $constraintColumns)->execute();
            return [];
        }

        $params = [];
        $sql = $this->getQueryBuilder()
            ->upsertReturning($table, $insertColumns, $updateColumns, $constraintColumns, $returnColumns, $params);

        $this->setSql($sql)->bindValues($params);

        /** @psalm-var array<string, mixed> */
        return $this->queryInternal(self::QUERY_MODE_ROW | self::QUERY_MODE_EXECUTE);
    }

    public function upsertReturningPks(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        ?array $constraintColumns = null,
    ): array {
        $primaryKeys = $this->db->getSchema()->getTableSchema($table)?->getPrimaryKey() ?? [];
        return $this->upsertReturning($table, $insertColumns, $updateColumns, $constraintColumns, $primaryKeys);
    }

    public function withDbTypecasting(bool $dbTypecasting = true): static
    {
        $new = clone $this;
        $new->dbTypecasting = $dbTypecasting;
        return $new;
    }

    public function withPhpTypecasting(bool $phpTypecasting = true): static
    {
        $new = clone $this;
        $new->phpTypecasting = $phpTypecasting;
        return $new;
    }

    public function withTypecasting(bool $typecasting = true): static
    {
        $new = clone $this;
        $new->dbTypecasting = $typecasting;
        $new->phpTypecasting = $typecasting;
        return $new;
    }

    /**
     * @return QueryBuilderInterface The query builder instance.
     */
    abstract protected function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Returns the query result.
     *
     * @param int $queryMode Query mode, `QUERY_MODE_*`.
     *
     * @throws Exception
     * @throws Throwable
     */
    abstract protected function internalGetQueryResult(int $queryMode): mixed;

    /**
     * Executes a prepared statement.
     *
     * @throws Exception
     * @throws Throwable
     */
    abstract protected function internalExecute(): void;

    /**
     * Check if the value has a given flag.
     *
     * @param int $value Flags value to check.
     * @param int $flag Flag to look for in the value.
     *
     * @return bool Whether the value has a given flag.
     */
    protected function is(int $value, int $flag): bool
    {
        return ($value & $flag) === $flag;
    }

    /**
     * The method is called after the query is executed.
     *
     * @param int $queryMode Query mode, `QUERY_MODE_*`.
     *
     * @throws Exception
     * @throws Throwable
     */
    protected function queryInternal(int $queryMode): mixed
    {
        $isReadMode = $this->isReadMode($queryMode);
        $this->prepare($isReadMode);

        $this->internalExecute();

        $result = $this->internalGetQueryResult($queryMode);

        if ($isReadMode) {
            return $result;
        }

        if ($this->refreshTableName !== null) {
            $this->db->getSchema()->refreshTableSchema($this->refreshTableName);
        }
        return $result;
    }

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
     * Resets the command object, so it can be reused to build another SQL statement.
     */
    protected function reset(): void
    {
        $this->sql = '';
        $this->params = [];
        $this->refreshTableName = null;
    }

    /**
     * Checks if the query mode is a read mode.
     */
    private function isReadMode(int $queryMode): bool
    {
        return !$this->is($queryMode, self::QUERY_MODE_EXECUTE);
    }
}
