<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use JsonException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Defines methods for building SQL statements for DML (data manipulation language).
 *
 * @link https://en.wikipedia.org/wiki/Data_manipulation_language
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-type BatchValues = iterable<iterable<array-key, mixed>>
 */
interface DMLQueryBuilderInterface
{
    /**
     * Generates a batch `INSERT` SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->insertBatch('user', [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ], ['name', 'age']);
     * ```
     *
     * or as associative arrays where the keys are column names
     *
     * ```php
     * $queryBuilder->insertBatch('user', [
     *     ['name' => 'Tom', 'age' => 30],
     *     ['name' => 'Jane', 'age' => 20],
     *     ['name' => 'Linda', 'age' => 25],
     * ]);
     * ```
     *
     * @param string $table The table to insert new rows into.
     * @param iterable $rows The rows to batch-insert into the table.
     * @param string[] $columns The column names of the table.
     * @param array $params The binding parameters. This parameter exists.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The batch INSERT SQL statement.
     *
     * @psalm-param BatchValues $rows
     * @psalm-param ParamsType $params
     *
     * Note:
     * - That the values in each row must match the corresponding column names.
     * - The method will escape the column names, and quote the values to insert.
     */
    public function insertBatch(string $table, iterable $rows, array $columns = [], array &$params = []): string;

    /**
     * Creates a `DELETE` SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->delete('user', 'status = 0');
     * ```
     *
     * @param string $table The table to delete the data from.
     * @param array|string $condition The condition to put in the `WHERE` part.
     * Please refer to {@see Query::where()} On how to specify condition.
     * @param array $params The binding parameters to change by this method to bind to DB command later.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @return string The `DELETE` SQL.
     *
     * @psalm-param ParamsType $params
     *
     * Note: The method will escape the table and column names.
     */
    public function delete(string $table, array|string $condition, array &$params): string;

    /**
     * Creates an `INSERT` SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ], $params);
     * ```
     *
     * @param string $table The table to insert new rows into.
     * @param array|QueryInterface $columns The column data (name => value) to insert into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * Passing of {@see Query}.
     * @param array $params The binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @return string The INSERT SQL.
     *
     * @psalm-param ParamsType $params
     *
     * Note: The method will escape the table and column names.
     */
    public function insert(string $table, array|QueryInterface $columns, array &$params = []): string;

    /**
     * Creates an INSERT SQL statement with returning inserted primary key values.
     *
     * @param string $table The table to insert new rows into.
     * @param array|QueryInterface $columns The column data (name => value) to insert into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array $params The binding parameters that will be generated by this method.
     *
     * @throws Exception
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @psalm-param ParamsType $params
     *
     * Note: The method will escape the table and column names.
     */
    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string;

    /**
     * Returns whether type casting is enabled for the query builder.
     *
     * @see withTypecasting()
     */
    public function isTypecastingEnabled(): bool;

    /**
     * Creates an SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $table The name of the table whose primary key sequence will be reset.
     * @param int|string|null $value The value for the primary key of the next new row inserted.
     * If this isn't set, the next new row's primary key will have value 1.
     *
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     * @throws Exception
     * @return string The SQL statement for a resetting sequence.
     *
     * Note: The method will escape the table and column names.
     */
    public function resetSequence(string $table, int|string|null $value = null): string;

    /**
     * Creates an UPDATE SQL statement.
     *
     * For example,
     *
     * ```php
     * $params = [];
     * $sql = $queryBuilder->update('user', ['status' => 1], 'age > 30', $params);
     * ```
     *
     * @param string $table The table to update.
     * @param array $columns The column data (name => value) to update the table.
     * @param array|string $condition The condition to put in the `WHERE` part. Please refer to
     * {@see Query::where()} On how to specify condition.
     * @param array $params The binding parameters that will be modified by this method so that they can be bound to
     * DB command later.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The UPDATE SQL.
     *
     * @psalm-param ParamsType $params
     *
     * Note: The method will escape the table and column names.
     */
    public function update(string $table, array $columns, array|string $condition, array &$params = []): string;

    /**
     * Creates an SQL statement to insert rows into a database table if they don't already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert('pages', [
     *     'name' => 'Front page',
     *     'url' => 'http://example.com/', // url is unique
     *     'visits' => 0,
     * ], [
     *     'visits' => new Expression('visits + 1'),
     * ], $params);
     * ```
     *
     * @param string $table The table to insert rows into or update new rows in.
     * @param array|QueryInterface $insertColumns The column data (name => value) to insert into the table or
     * instance of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns The column data (name => value) to update if they already exist. If `true`
     * is passed, the column data will be updated to match the insert column data. If `false` is passed, no update will
     * be performed if the column data already exist.
     * @param array $params The binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     * @psalm-param ParamsType $params
     *
     * Note: The method will escape the table and column names.
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string;

    /**
     * Creates an SQL statement to insert rows into a database table if they don't already exist (matching unique
     * constraints), or update them if they do, with returning values from the specified columns.
     * The method will quote the `table`, `insertColumns`, `updateColumns` and `returnColumns` parameters before using
     * it in the generated SQL.
     *
     * @param string $table The table to insert rows into or update new rows in.
     * @param array|QueryInterface $insertColumns The column data (name => value) to insert into the table or
     * instance of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns The column data (name => value) to update if they already exist. If `true`
     * is passed, the column data will be updated to match the insert column data. If `false` is passed, no update will
     * be performed if the column data already exist.
     * @param string[]|null $returnColumns The column names to return values from. `null` means all columns.
     * @param array $params The binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     * @psalm-param ParamsType $params
     */
    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string;

    /**
     * Clones the current instance and sets whether type casting is required for the query builder.
     *
     * @param bool $typecasting Whether type casting is required. Defaults to `true`.
     *
     * @see isTypecastingEnabled()
     */
    public function withTypecasting(bool $typecasting = true): static;
}
