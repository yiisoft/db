<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Connection\ConnectionInterface;
use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryPartsInterface;
use Yiisoft\Db\Query\WithQuery;
use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Defines methods for building SQL statements for DQL (data query language).
 *
 * @link https://en.wikipedia.org/wiki/Data_query_language
 *
 * @psalm-import-type Join from QueryInterface
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-import-type SelectValue from QueryPartsInterface
 */
interface DQLQueryBuilderInterface
{
    /**
     * Generates a `SELECT` SQL statement from a {@see Query} object.
     *
     * @param QueryInterface $query The {@see Query} object from which the SQL statement will
     * generated.
     * @param array $params The parameters to bind to the generated SQL statement.
     * These parameters will be included in the result, with the more parameters generated during the query building
     * process.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return array The generated SQL statement (the first array element) and the corresponding parameters to bind
     * to the SQL statement (the second array element). The parameters returned include those provided in `$params`.
     *
     * @psalm-param ParamsType $params
     * @psalm-return array{0: string, 1: ParamsType}
     */
    public function build(QueryInterface $query, array $params = []): array;

    /**
     * Processes columns and quotes them if necessary.
     *
     * It will join all columns into a string with comma as separators.
     *
     * @param array|string $columns The columns to process.
     *
     * @throws NotSupportedException
     *
     * @psalm-param array<ExpressionInterface|string>|string $columns
     */
    public function buildColumns(array|string $columns): string;

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition The condition specification.
     * Please refer to {@see Query::where()} on how to specify a condition.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @psalm-param ParamsType $params
     */
    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string;

    /**
     * Builds given $expression.
     *
     * @param ExpressionInterface $expression The expression to build.
     * @param array $params The parameters to bind to the generated SQL statement.
     * These parameters will be included in the result with the more parameters generated during the expression building
     * process.
     *
     * @throws NotSupportedException When $expression building isn't supported by the {@see QueryBuilderInterface} implementation.
     *
     * @return string The SQL statement that won't be neither quoted nor encoded before passing to DBMS.
     *
     * @psalm-param ParamsType $params
     *
     * @see ExpressionInterface
     * @see ExpressionBuilderInterface
     * @see AbstractDQLQueryBuilder::expressionBuilders
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string;

    /**
     * Builds a SQL for `FOR` clause.
     *
     * @param array $values The value to build.
     *
     * @throws NotSupportedException When the `FOR` clause is not supported.
     *
     * @return string The result SQL.
     *
     * @psalm-param list<string> $values
     */
    public function buildFor(array $values): string;

    /**
     * @param array $tables The tables to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws NotSupportedException
     *
     * @return string The `FROM` clause built from {@see Query::from()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildFrom(array $tables, array &$params): string;

    /**
     * @param array $columns The columns to group by.
     * Each column can be a string representing a column name or an array representing a column specification.
     * Please refer to {@see Query::groupBy()} on how to specify this parameter.
     * @param array $params The binding parameters to populate.
     *
     * @throws NotSupportedException
     *
     * @return string The `GROUP BY` clause
     *
     * @psalm-param ParamsType $params
     */
    public function buildGroupBy(array $columns, array &$params = []): string;

    /**
     * @param array|ExpressionInterface|string|null $condition The condition specification.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return string The `HAVING` clause built from {@see Query::having()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string;

    /**
     * @param array $joins The joins to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException If the `$joins` parameter isn't in proper format.
     * @throws NotSupportedException
     *
     * @return string The `JOIN` clause built from {@see Query::join()}.
     *
     * @psalm-param list<Join> $joins
     * @psalm-param ParamsType $params
     */
    public function buildJoin(array $joins, array &$params): string;

    /**
     * @param ExpressionInterface|int|null $limit The limit number.
     * @see Query::limit() For more details.
     * @param ExpressionInterface|int|null $offset The offset number.
     * @see Query::offset() For more details.
     *
     * @throws NotSupportedException
     *
     * @return string The `LIMIT` and `OFFSET` clauses.
     */
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string;

    /**
     * @param array $columns The columns to order by.
     * Each column can be a string representing a column name or an array representing a column specification.
     * Please refer to {@see Query::orderBy()} on how to specify this parameter.
     * @param array $params The binding parameters to populate.
     *
     * @throws NotSupportedException
     *
     * @return string The `ORDER BY` clause built from {@see Query::orderBy()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildOrderBy(array $columns, array &$params = []): string;

    /**
     * Builds the ORDER BY and `LIMIT/OFFSET` clauses and appends them to the given SQL.
     *
     * @param string $sql The existing SQL (without `ORDER BY/LIMIT/OFFSET`).
     * @param array $orderBy The order by columns.
     * {@see Query::orderBy()} for more details on how to specify this parameter.
     * @param ExpressionInterface|int|null $limit The limit number.
     * {@see Query::limit()} For more details.
     * @param ExpressionInterface|int|null $offset The offset number.
     * {@see Query::offset()} For more details.
     * @param array $params The binding parameters to populate.
     *
     * @throws NotSupportedException
     *
     * @return string The SQL completed with `ORDER BY/LIMIT/OFFSET` (if any).
     *
     * @psalm-param ParamsType $params
     */
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = [],
    ): string;

    /**
     * @param array $columns The columns to select.
     * Each column can be a string representing a column name or an array representing a column specification.
     * Please refer to {@see Query::select()} on how to specify this parameter.
     * @param array $params The binding parameters to populate.
     * @param bool $distinct Whether to add `DISTINCT` or not.
     * @param string|null $selectOption The `SELECT` option to use (for example, `SQL_CALC_FOUND_ROWS`).
     *
     * @throws InvalidArgumentException
     *
     * @return string The `SELECT` clause built from {@see Query::select()}.
     *
     * @psalm-param SelectValue $columns
     * @psalm-param ParamsType $params
     */
    public function buildSelect(
        array $columns,
        array &$params,
        bool $distinct = false,
        ?string $selectOption = null,
    ): string;

    /**
     * @param array $unions The `UNION` queries to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return string The `UNION` clause built from {@see Query::union()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildUnion(array $unions, array &$params): string;

    /**
     * @param array|ConditionInterface|ExpressionInterface|string|null $condition The condition built from
     * {@see Query::where()}.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return string The `WHERE` clause built from {@see Query::where()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = [],
    ): string;

    /**
     * @param WithQuery[] $withQueries The `WITH` queries to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return string The `WITH` clause built from {@see Query::withQuery()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildWithQueries(array $withQueries, array &$params): string;

    /**
     * Transforms one condition defined in array format (as described in {@see Query::where()} to
     * instance of {@see ConditionInterface}).
     *
     * @param array $condition The condition in array format.
     *
     * @throws InvalidArgumentException
     *
     * @see ConditionInterface According to conditions class map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface;

    /**
     * @throws NotSupportedException
     *
     * @return ExpressionBuilderInterface Instance of {@see ExpressionBuilderInterface} for the given expression.
     */
    public function getExpressionBuilder(ExpressionInterface $expression): ExpressionBuilderInterface;

    /**
     * Creates a `SELECT EXISTS()` SQL statement.
     *
     * @param string $rawSql The sub-query in a raw form to select from.
     *
     * @return string The `SELECT EXISTS()` SQL statement.
     */
    public function selectExists(string $rawSql): string;

    /**
     * Setter for {@see AbstractDQLQueryBuilder::conditionClasses} property.
     *
     * @param string[] $classes Map of condition aliases to condition classes. For example:
     *
     * ```php
     * ['LIKE' => \Yiisoft\Db\Condition\LikeCondition::class]
     * ```
     *
     * @psalm-param array<string, class-string<ConditionInterface>> $classes
     */
    public function setConditionClasses(array $classes): void;

    /**
     * Setter for {@see AbstractDQLQueryBuilder::expressionBuilders} property.
     *
     * @param string[] $builders Array of builders to merge with the pre-defined ones in property.
     *
     * @psalm-param array<class-string<ExpressionInterface>, class-string<ExpressionBuilderInterface>> $builders
     */
    public function setExpressionBuilders(array $builders): void;

    /**
     * @param string $separator The separator between different fragments of an SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void;
}
