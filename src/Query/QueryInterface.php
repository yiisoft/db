<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Defines several methods for building and executing database queries, including methods for selecting
 * data, inserting data, updating data, and deleting data.
 *
 * It also defines methods for specifying the conditions for a query, as well as methods for pagination and sorting.
 *
 * It has support for getting {@see one()} instance or {@see all()}.
 *
 * Allows pagination via {@see limit()} and {@see offset()}.
 *
 * Sorting is supported via {@see orderBy()} and items can be limited to match some conditions using {@see where()}.
 *
 * @psalm-type IndexBy = Closure(array):array-key|string
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-import-type SelectValue from QueryPartsInterface
 * @psalm-type ResultCallback = Closure(list<array>):list<array|object>
 */
interface QueryInterface extends ExpressionInterface, QueryPartsInterface, QueryFunctionsInterface
{
    /**
     * Adds more parameters to bind to the query.
     *
     * @param array $params The list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @psalm-param ParamsType $params
     *
     * @see params()
     */
    public function addParams(array $params): static;

    /**
     * Executes the query and returns all results as an array.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array[]|object[] All rows of the query result. Each array element is an `array` or `object` representing
     * a row of data. Empty array if the query results in nothing.
     */
    public function all(): array;

    /**
     * Starts a batch query.
     *
     * A batch query supports fetching data in batches, which can keep the memory usage under a limit.
     *
     * This method will return a {@see BatchQueryResultInterface} object which implements the {@see \Iterator} interface
     * and can be traversed to retrieve the data in batches.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     *
     * foreach ($query->batch() as $rows) {
     *     // $rows is an array of 100 or fewer rows from user table
     * }
     * ```
     *
     * @param int $batchSize The number of records to fetch in each batch.
     *
     * @return BatchQueryResultInterface The batch query result. It implements the {@see \Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function batch(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Sets the callback, to be called on all rows of the query result before returning them.
     *
     * For example:
     *
     * ```php
     * $users = (new Query($db))
     *     ->from('user')
     *     ->resultCallback(function (array $rows): array {
     *         foreach ($rows as &$row) {
     *             $row['name'] = strtoupper($row['name']);
     *         }
     *         return $rows;
     *     })
     *     ->all();
     * ```
     *
     * @psalm-param ResultCallback|null $resultCallback
     */
    public function resultCallback(Closure|null $resultCallback): static;

    /**
     * Executes the query and returns the first column of the result.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     *
     * @return array The first column of the query result. It returns an empty array if the query results in nothing.
     */
    public function column(): array;

    /**
     * Creates a DB command to execute the query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return CommandInterface The created DB command instance.
     */
    public function createCommand(): CommandInterface;

    /**
     * Starts a batch query and retrieves data row by row.
     *
     * This method is similar to {@see batch()} except that in each iteration of the result,
     * it returns only one row of data.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     *
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param int $batchSize The number of records to fetch in each batch.
     *
     * @return BatchQueryResultInterface The batch query result. It implements the {@see \Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function each(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Sets whether to emulate query execution without actually executing a query.
     *
     * When enabled, methods returning results such as {@see one()}, {@see all()}, or {@see exists()}
     * will return empty or `false` values.
     *
     * You should use this method in case your program logic requires that a query shouldn't return any results.
     *
     * @param bool $value Whether to emulate query execution.
     */
    public function emulateExecution(bool $value = true): static;

    /**
     * Returns a value indicating whether the query result has any row of data.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return bool whether the query result has any row of data.
     */
    public function exists(): bool;

    /**
     * Returns the callback to be called on all rows of the query result.
     * `null` will be returned if the callback is not set.
     *
     * @psalm-return ResultCallback|null
     */
    public function getResultCallback(): Closure|null;

    /**
     * @return bool|null The "distinct" value.
     */
    public function getDistinct(): bool|null;

    /**
     * @return array The "from" value.
     */
    public function getFrom(): array;

    /**
     * @return array The "group by" value.
     */
    public function getGroupBy(): array;

    /**
     * @return array|ExpressionInterface|string|null The "having" value.
     */
    public function getHaving(): string|array|ExpressionInterface|null;

    /**
     * @return Closure|string|null The "index by" value.
     *
     * @psalm-return IndexBy|null
     */
    public function getIndexBy(): Closure|string|null;

    /**
     * @return array The "join" value.
     *
     * The format is:
     *
     * ```
     * [
     *     ['INNER JOIN', 'table1', 'table1.id = table2.id'],
     *     ['LEFT JOIN', 'table3', 'table1.id = table3.id'],
     * ]
     * ```
     */
    public function getJoins(): array;

    /**
     * @return ExpressionInterface|int|null The "limit" value.
     */
    public function getLimit(): ExpressionInterface|int|null;

    /**
     * @return ExpressionInterface|int|null The "offset" value.
     */
    public function getOffset(): ExpressionInterface|int|null;

    /**
     * @return array The "order by" value.
     */
    public function getOrderBy(): array;

    /**
     * @return array The "params" value.
     */
    public function getParams(): array;

    /**
     * @return array The "select" value.
     * @psalm-return SelectValue
     */
    public function getSelect(): array;

    /**
     * @return string|null The "select option" value.
     */
    public function getSelectOption(): string|null;

    /**
     * Returns table names used in {@see from()} indexed by aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @throws InvalidArgumentException
     *
     * @return array The table names indexed by aliases.
     * @psalm-return array<string, ExpressionInterface|string>
     */
    public function getTablesUsedInFrom(): array;

    /**
     * @return array The "union" values.
     *
     * The format is:
     *
     * ```php
     * ['SELECT * FROM table1', 'SELECT * FROM table2']
     * ```
     */
    public function getUnions(): array;

    /**
     * @return array|ExpressionInterface|string|null The "where" value.
     */
    public function getWhere(): array|string|ExpressionInterface|null;

    /**
     * @return array The withQueries value.
     */
    public function getWithQueries(): array;

    /**
     * Executes the query and returns a single row of a result.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array|object|null The first row as an `array` or as an `object` of the query result. `null` if the query
     * results in nothing.
     */
    public function one(): array|object|null;

    /**
     * Sets the parameters to bind to the query.
     *
     * @param array $params List of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @psalm-param ParamsType $params
     *
     * @see addParams()
     */
    public function params(array $params): static;

    /**
     * Prepare for building SQL.
     *
     * {@see QueryBuilderInterface} uses this method when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into an SQL statement.
     *
     * @param QueryBuilderInterface $builder The query builder.
     */
    public function prepare(QueryBuilderInterface $builder): self;

    /**
     * Returns the query results as a scalar value.
     * The value returned will be the first column in the first row of the query results.
     * Do not use this method for `boolean` values as it returns `false` if the query result is empty.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return bool|float|int|string|null The value of the first column in the first row of the query result. False is
     * returned if the query result is empty.
     */
    public function scalar(): bool|int|null|string|float;

    /**
     * @return bool Whether to emulate query execution.
     */
    public function shouldEmulateExecution(): bool;
}
