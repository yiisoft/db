<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Stringable;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * This interface defines several methods for building and executing database queries, including methods for selecting
 * data, inserting data, updating data, and deleting data.
 *
 * It also defines methods for specifying the conditions for a query, as well as methods for pagination and sorting.
 *
 * It has support for getting {@see one()} instance or {@see all()}.
 *
 * Allows pagination via {@see limit()} and {@see offset()}.
 *
 * Sorting is supported via {@see orderBy()} and items can be limited to match some conditions using {@see where()}.
 */
interface QueryInterface extends ExpressionInterface, QueryPartsInterface, QueryFunctionsInterface, Stringable
{
    /**
     * Adds more parameters to be bound to the query.
     *
     * @param array $params The list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @see params()
     */
    public function addParams(array $params): static;

    /**
     * Executes the query and returns all results as an array.
     *
     * If this parameter isn't given, the `db` application part will be used.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array The query results. If the query results in nothing, an empty array will be returned.
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
     * @param int $batchSize The number of records to be fetched in each batch.
     *
     * @return BatchQueryResultInterface The batch query result. It implements the {@see \Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function batch(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Executes the query and returns the first column of the result.
     *
     * If this parameter isn't given, the `db` application part will be used.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     *
     * @return array The first column of the query result. An empty array is returned if the query results in nothing.
     */
    public function column(): array;

    /**
     * Creates a DB command that can be used to execute this query.
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
     * This method is similar to {@see batch()} except that in each iteration of the result, only one row of data is
     * returned. For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     *
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param int $batchSize The number of records to be fetched in each batch.
     *
     * @return BatchQueryResultInterface The batch query result. It implements the {@see \Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function each(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Sets whether to emulate query execution, preventing any interaction with data storage.
     *
     * After this mode is enabled, methods, returning query results like {@see one()}, {@see all()}, {@see exists()}
     * and so on, will return empty or false values.
     *
     * You should use this method in case your program logic indicates a query shouldn't return any results, like in
     * case you set false where condition like `0=1`.
     *
     * @param bool $value Whether to prevent query execution.
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
     * @return bool|null The distinct value.
     */
    public function getDistinct(): bool|null;

    /**
     * @return array The from value.
     */
    public function getFrom(): array;

    /**
     * @return array The group by value.
     */
    public function getGroupBy(): array;

    /**
     * @return array|ExpressionInterface|string|null The having value.
     */
    public function getHaving(): string|array|ExpressionInterface|null;

    /**
     * @return Closure|string|null The indexBy value.
     */
    public function getIndexBy(): Closure|string|null;

    /**
     * @return array The join value.
     */
    public function getJoin(): array;

    /**
     * @return ExpressionInterface|int|null The limit value.
     */
    public function getLimit(): ExpressionInterface|int|null;

    /**
     * @return ExpressionInterface|int|null The offset value.
     */
    public function getOffset(): ExpressionInterface|int|null;

    /**
     * @return array The order by value.
     */
    public function getOrderBy(): array;

    /**
     * @return array The params value.
     */
    public function getParams(): array;

    /**
     * @return array The select value.
     */
    public function getSelect(): array;

    /**
     * @return string|null The select option value.
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
     */
    public function getTablesUsedInFrom(): array;

    /**
     * @return array The union value.
     */
    public function getUnion(): array;

    /**
     * @return array|ExpressionInterface|string|null The where value.
     */
    public function getWhere(): array|string|ExpressionInterface|null;

    /**
     * @return array The withQueries value.
     */
    public function getWithQueries(): array;

    /**
     * Executes the query and returns a single row of a result.
     *
     * If this parameter isn't given, the `db` application part will be used.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array|null The first row (in terms of an array) of the query result. Null is returned if the query
     * results in nothing.
     */
    public function one(): array|null;

    /**
     * Sets the parameters to be bound to the query.
     *
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @see addParams()
     */
    public function params(array $params): static;

    /**
     * Prepares for building SQL.
     *
     * This method is called by {@see QueryBuilderInterface} when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into an SQL statement.
     *
     * @param QueryBuilderInterface $builder The query builder.
     */
    public function prepare(QueryBuilderInterface $builder): static;

    /**
     * Returns the query results as a scalar value.
     *
     * The value returned will be the first column in the first row of the query results.
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
