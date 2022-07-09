<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Stringable;
use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * The `QueryInterface` defines the minimum set of methods to be implemented by a database query.
 *
 * The default implementation of this interface is provided by {@see QueryTrait}.
 *
 * It has support for getting {@see one} instance or {@see all}.
 * Allows pagination via {@see limit} and {@see offset}.
 * Sorting is supported via {@see orderBy} and items can be limited to match some conditions using {@see where}.
 */
interface QueryInterface extends ExpressionInterface, QueryPartsInterface, QueryFunctionsInterface, Stringable
{
    /**
     * Adds additional parameters to be bound to the query.
     *
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @return $this the query object itself.
     *
     * {@see params()}
     */
    public function addParams(array $params): self;

    /**
     * Executes the query and returns all results as an array.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all(): array;

    /**
     * Starts a batch query.
     *
     * A batch query supports fetching data in batches, which can keep the memory usage under a limit.
     *
     * This method will return a {@see BatchQueryResultInterface} object which implements the {@see Iterator} interface and can
     * be traversed to retrieve the data in batches.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->batch() as $rows) {
     *     // $rows is an array of 100 or fewer rows from user table
     * }
     * ```
     *
     * @param int $batchSize the number of records to be fetched in each batch.
     *
     * @return BatchQueryResultInterface the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function batch(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Enables query cache for this Query.
     *
     * @param int|null $duration the number of seconds that query results can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     * Use a negative number to indicate that query cache should not be used.
     * @param Dependency|null $dependency the cache dependency associated with the cached result.
     *
     * @return $this the Query object itself.
     */
    public function cache(?int $duration = 3600, ?Dependency $dependency = null): self;

    /**
     * Executes the query and returns the first column of the result.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The first column of the query result. An empty array is returned if the query results in nothing.
     */
    public function column(): array;

    /**
     * Creates a DB command that can be used to execute this query.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException
     *
     * @return CommandInterface the created DB command instance.
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
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param int $batchSize the number of records to be fetched in each batch.
     *
     * @return BatchQueryResultInterface the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function each(int $batchSize = 100): BatchQueryResultInterface;

    /**
     * Sets whether to emulate query execution, preventing any interaction with data storage.
     * After this mode is enabled, methods, returning query results like {@see one()}, {@see all()}, {@see exists()}
     * and so on, will return empty or false values.
     * You should use this method in case your program logic indicates query should not return any results, like in case
     * you set false where condition like `0=1`.
     *
     * @param bool $value whether to prevent query execution.
     *
     * @return QueryInterface the query object itself.
     */
    public function emulateExecution(bool $value = true): self;

    /**
     * Returns a value indicating whether the query result contains any row of data.
     *
     * @return bool whether the query result contains any row of data.
     */
    public function exists(): bool;

    public function getDistinct(): ?bool;

    public function getFrom(): array|null;

    public function getGroupBy(): array;

    public function getHaving(): string|array|ExpressionInterface|null;

    /**
     * Return index by key.
     */
    public function getIndexBy(): Closure|string|null;

    public function getJoin(): array;

    public function getLimit(): Expression|int|null;

    public function getOffset(): Expression|int|null;

    public function getOrderBy(): array;

    public function getParams(): array;

    /**
     * Return select query string.
     */
    public function getSelect(): array;

    public function getSelectOption(): ?string;

    /**
     * Returns table names used in {@see from} indexed by aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @throws InvalidArgumentException
     *
     * @return array table names indexed by aliases
     */
    public function getTablesUsedInFrom(): array;

    public function getUnion(): array;

    public function getWhere(): array|string|ExpressionInterface|null;

    public function getWithQueries(): array;

    /**
     * Disables query cache for this Query.
     *
     * @return $this the Query object itself.
     */
    public function noCache(): self;

    /**
     * Executes the query and returns a single row of result.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array|object|null The first row (in terms of an array) of the query result. Null is returned if the query
     * results in nothing.
     */
    public function one(): array|object|null;

    /**
     * Sets the parameters to be bound to the query.
     *
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @return $this the query object itself.
     *
     * {@see addParams()}
     */
    public function params(array $params): self;

    /**
     * Converts the raw query results into the format as specified by this query.
     *
     * This method is internally used to convert the data fetched from database into the format as required by this
     * query.
     *
     * @param array $rows the raw query result from database.
     *
     * @return array the converted query result.
     */
    public function populate(array $rows): array;

    /**
     * Prepares for building SQL.
     *
     * This method is called by {@see QueryBuilderInterface} when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into a SQL statement.
     *
     * @param QueryBuilderInterface $builder
     *
     * @return QueryInterface A prepared query instance which will be used by {@see QueryBuilder} to build the SQL.
     */
    public function prepare(QueryBuilderInterface $builder): self;

    /**
     * Returns the query result as a scalar value.
     *
     * The value returned will be the first column in the first row of the query results.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|float|int|string|null the value of the first column in the first row of the query result. False is
     * returned if the query result is empty.
     */
    public function scalar(): bool|int|null|string|float;

    public function shouldEmulateExecution(): bool;
}
