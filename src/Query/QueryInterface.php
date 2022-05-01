<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Stringable;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * The QueryInterface defines the minimum set of methods to be implemented by a database query.
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
     * Adds additional group-by columns to the existing ones.
     *
     * @param array|ExpressionInterface|string $columns additional columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis (which means the
     * column contains a DB expression).
     *
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information. Otherwise, the method will not be able to correctly determine the group-by columns.
     *
     * {@see Expression} object can be passed to specify the GROUP BY part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @return $this the query object itself
     *
     * {@see groupBy()}
     */
    public function addGroupBy(array|string|ExpressionInterface $columns): self;

    /**
     * Adds additional ORDER BY columns to the query.
     *
     * @param array|ExpressionInterface|string $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     *
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if your order-by is an expression containing commas, you should always use an array
     * to represent the order-by information. Otherwise, the method will not be able to correctly determine
     * the order-by columns.
     *
     * Since {@see ExpressionInterface} object can be passed to specify the ORDER BY part explicitly in plain SQL.
     *
     * @return $this the query object itself
     *
     * {@see orderBy()}
     */
    public function addOrderBy(array|string|ExpressionInterface $columns): self;

    /**
     * Adds HAVING condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * This method is similar to {@see andHaving()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new HAVING condition. Please refer to {@see having()} on how to specify this
     * parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see filterHaving()}
     * {@see orFilterHaving()}
     */
    public function andFilterHaving(array $condition): self;

    /**
     * Adds HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see orHaving()}
     */
    public function andHaving(array|string|ExpressionInterface $condition, array $params = []): self;

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
     * This method will return a {@see BatchQueryResult} object which implements the {@see Iterator} interface and can
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
     * @return BatchQueryResult the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function batch(int $batchSize = 100): BatchQueryResult;

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
     * @return BatchQueryResult the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function each(int $batchSize = 100): BatchQueryResult;

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

    /**
     * Sets the HAVING part of the query but ignores {@see isEmpty()|empty operands}.
     *
     * This method is similar to {@see having()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * The following code shows the difference between this method and {@see having()}:
     *
     * ```php
     * // HAVING `age`=:age
     * $query->filterHaving(['name' => null, 'age' => 20]);
     * // HAVING `age`=:age
     * $query->having(['age' => 20]);
     * // HAVING `name` IS NULL AND `age`=:age
     * $query->having(['name' => null, 'age' => 20]);
     * ```
     *
     * Note that unlike {@see having()}, you cannot pass binding parameters to this method.
     *
     * @param array $condition the conditions that should be put in the HAVING part.
     * See {@see having()} on how to specify this parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see andFilterHaving()}
     * {@see orFilterHaving()}
     */
    public function filterHaving(array $condition): self;

    /**
     * Return index by key.
     */
    public function getIndexBy(): Closure|string|null;

    /**
     * Return select query string.
     */
    public function getSelect(): array;

    /**
     * Sets the GROUP BY part of the query.
     *
     * @param array|ExpressionInterface|string $columns the columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis (which means the
     * column contains a DB expression).
     *
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information. Otherwise, the method will not be able to correctly determine the group-by columns.
     *
     * {@see ExpressionInterface} object can be passed to specify the GROUP BY part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @return $this the query object itself.
     *
     * {@see addGroupBy()}
     */
    public function groupBy(array|string|ExpressionInterface $columns): self;

    /**
     * Sets the {@see indexBy} property.
     *
     * @param Closure|string|null $column the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given row data.
     * The signature of the callable should be:
     *
     * ```php
     * function ($row)
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     *
     * @return QueryInterface the query object itself.
     */
    public function indexBy(string|Closure|null $column): self;

    /**
     * Sets the HAVING part of the query.
     *
     * @param array|ExpressionInterface|string|null $condition the conditions to be put after HAVING.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see andHaving()}
     * {@see orHaving()}
     */
    public function having(array|ExpressionInterface|string|null $condition, array $params = []): self;

    /**
     * Sets the LIMIT part of the query.
     *
     * @param Expression|int|null $limit the limit. Use null or negative value to disable limit.
     *
     * @return $this the query object itself
     */
    public function limit(Expression|int|null $limit): self;

    /**
     * Sets the OFFSET part of the query.
     *
     * @param Expression|int|null $offset $offset the offset. Use null or negative value to disable offset.
     *
     * @return QueryInterface the query object itself
     */
    public function offset(Expression|int|null $offset): self;

    /**
     * Executes the query and returns a single row of result.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return mixed the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one(): mixed;

    /**
     * Sets the ORDER BY part of the query.
     *
     * @param array|ExpressionInterface|string $columns the columns (and the directions) to be ordered by.
     *
     * Columns can be specified in either a string (e.g. `"id ASC, name DESC"`) or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     *
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if your order-by is an expression containing commas, you should always use an array
     * to represent the order-by information. Otherwise, the method will not be able to correctly determine
     * the order-by columns.
     *
     * Since {@see ExpressionInterface} object can be passed to specify the ORDER BY part explicitly in plain SQL.
     *
     * @return $this the query object itself
     *
     * {@see addOrderBy()}
     */
    public function orderBy(array|string|ExpressionInterface $columns): self;

    /**
     * Adds HAVING condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * This method is similar to {@see orHaving()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new HAVING condition. Please refer to {@see having()} on how to specify this
     * parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see filterHaving()}
     * {@see andFilterHaving()}
     */
    public function orFilterHaving(array $condition): self;

    /**
     * Adds HAVING condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see andHaving()}
     */
    public function orHaving(array|string|ExpressionInterface $condition, array $params = []): self;

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
     * @param QueryBuilder $builder
     *
     * @return Query A prepared query instance which will be used by {@see QueryBuilder} to build the SQL.
     */
    public function prepare(QueryBuilder $builder): Query;

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
}
