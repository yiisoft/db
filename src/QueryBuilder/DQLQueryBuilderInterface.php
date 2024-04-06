<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Defines methods for building SQL statements for DQL (data query language).
 *
 * @link https://en.wikipedia.org/wiki/Data_query_language
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
interface DQLQueryBuilderInterface
{
    /**
     * Generates a `SELECT` SQL statement from a {@see \Yiisoft\Db\Query\Query} object.
     *
     * @param QueryInterface $query The {@see \Yiisoft\Db\Query\Query} object from which the SQL statement will
     * generated.
     * @param array $params The parameters to bind to the generated SQL statement.
     * These parameters will be included in the result, with the more parameters generated during the query building
     * process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array The generated SQL statement (the first array element) and the corresponding parameters to bind
     * to the SQL statement (the second array element). The parameters returned include those provided in `$params`.
     *
     * @psalm-param ParamsType $params
     * @psalm-return array{0: string, 1: array}
     */
    public function build(QueryInterface $query, array $params = []): array;

    /**
     * Processes columns and quotes them if necessary.
     *
     * It will join all columns into a string with comma as separators.
     *
     * @param array|string $columns The columns to process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function buildColumns(array|string $columns): string;

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition The condition specification.
     * Please refer to {@see \Yiisoft\Db\Query\Query::where()} on how to specify a condition.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException When $expression building isn't supported by this QueryBuilder.
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
     * @param array|null $tables The tables to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `FROM` clause built from {@see \Yiisoft\Db\Query\Query::from()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildFrom(array|null $tables, array &$params): string;

    /**
     * @param array $columns The columns to group by.
     * Each column can be a string representing a column name or an array representing a column specification.
     * Please refer to {@see Query::groupBy()} on how to specify this parameter.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `HAVING` clause built from {@see \Yiisoft\Db\Query\Query::having()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string;

    /**
     * @param array $joins The joins to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception If the `$joins` parameter isn't in proper format.
     *
     * @return string The `JOIN` clause built from {@see \Yiisoft\Db\Query\Query::join()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildJoin(array $joins, array &$params): string;

    /**
     * @param ExpressionInterface|int|null $limit The limit number.
     * {@see \Yiisoft\Db\Query\Query::limit()} For more details.
     * @param ExpressionInterface|int|null $offset The offset number.
     * {@see \Yiisoft\Db\Query\Query::offset()} For more details.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
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
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The `ORDER BY` clause built from {@see \Yiisoft\Db\Query\Query::orderBy()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildOrderBy(array $columns, array &$params = []): string;

    /**
     * Builds the ORDER BY and `LIMIT/OFFSET` clauses and appends them to the given SQL.
     *
     * @param string $sql The existing SQL (without `ORDER BY/LIMIT/OFFSET`).
     * @param array $orderBy The order by columns.
     * {@see \Yiisoft\Db\Query\Query::orderBy()} for more details on how to specify this parameter.
     * @param ExpressionInterface|int|null $limit The limit number.
     * {@see \Yiisoft\Db\Query\Query::limit()} For more details.
     * @param ExpressionInterface|int|null $offset The offset number.
     * {@see \Yiisoft\Db\Query\Query::offset()} For more details.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
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
        array &$params = []
    ): string;

    /**
     * @param array $columns The columns to select.
     * Each column can be a string representing a column name or an array representing a column specification.
     * Please refer to {@see \Yiisoft\Db\Query\Query::select()} on how to specify this parameter.
     * @param array $params The binding parameters to populate.
     * @param bool|null $distinct  Whether to add `DISTINCT` or not.
     * @param string|null $selectOption The `SELECT` option to use (for example, `SQL_CALC_FOUND_ROWS`).
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `SELECT` clause built from {@see \Yiisoft\Db\Query\Query::select()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildSelect(
        array $columns,
        array &$params,
        bool|null $distinct = false,
        string $selectOption = null
    ): string;

    /**
     * @param array $unions The `UNION` queries to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `UNION` clause built from {@see \Yiisoft\Db\Query\Query::union()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildUnion(array $unions, array &$params): string;

    /**
     * @param array|ConditionInterface|ExpressionInterface|string|null $condition The condition built from
     * {@see \Yiisoft\Db\Query\Query::where()}.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `WHERE` clause built from {@see \Yiisoft\Db\Query\Query::where()}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string;

    /**
     * @param array $withs The `WITH` queries to process.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The `WITH` clause built from {@see \Yiisoft\Db\Query\Query::with}.
     *
     * @psalm-param ParamsType $params
     */
    public function buildWithQueries(array $withs, array &$params): string;

    /**
     * Transforms one condition defined in array format (as described in {@see \Yiisoft\Db\Query\Query::where()} to
     * instance of {@see ConditionInterface}).
     *
     * @param array $condition The condition in array format.
     *
     * @throws InvalidArgumentException
     *
     * {@see ConditionInterface} According to conditions class map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface;

    /**
     * @throws InvalidArgumentException
     *
     * @return object Instance of {@see ExpressionBuilderInterface} for the given expression.
     *
     * @psalm-suppress InvalidStringClass
     */
    public function getExpressionBuilder(ExpressionInterface $expression): object;

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
     */
    public function setConditionClasses(array $classes): void;

    /**
     * Setter for {@see AbstractDQLQueryBuilder::expressionBuilders} property.
     *
     * @param string[] $builders Array of builders to merge with the pre-defined ones in property.
     *
     * @psalm-param array<string, class-string<ExpressionBuilderInterface>> $builders
     */
    public function setExpressionBuilders(array $builders): void;

    /**
     * @param string $separator The separator between different fragments of an SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void;
}
