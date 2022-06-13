<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface DQLQueryBuilderInterface
{
    /**
     * Generates a SELECT SQL statement from a {@see Query} object.
     *
     * @param QueryInterface $query the {@see Query} object from which the SQL statement will be generated.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array the generated SQL statement (the first array element) and the corresponding parameters to be bound
     * to the SQL statement (the second array element). The parameters returned include those provided in `$params`.
     *
     * @psalm-return array{0: string, 1: array}
     */
    public function build(QueryInterface $query, array $params = []): array;

    /**
     * Processes columns and properly quotes them if necessary.
     *
     * It will join all columns into a string with comma as separators.
     *
     * @param array|string $columns the columns to be processed.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the processing result.
     */
    public function buildColumns(array|string $columns): string;

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition the condition specification.
     * Please refer to {@see Query::where()} on how to specify a condition.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the generated SQL expression.
     */
    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string;

    /**
     * Builds given $expression.
     *
     * @param ExpressionInterface $expression the expression to be built
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be
     * included in the result with the additional parameters generated during the expression building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException when $expression building
     * is not supported by this QueryBuilder.
     *
     * @return string the SQL statement that will not be neither quoted nor encoded before passing to DBMS.
     *
     * @see ExpressionInterface
     * @see ExpressionBuilderInterface
     * @see expressionBuilders
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string;

    /**
     * @param array|null $tables
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the FROM clause built from {@see Query::$from}.
     */
    public function buildFrom(?array $tables, array &$params): string;

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the GROUP BY clause
     */
    public function buildGroupBy(array $columns, array &$params = []): string;

    /**
     * @param array|ExpressionInterface|string|null $condition the condition specification.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the HAVING clause built from {@see Query::$having}.
     */
    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string;

    /**
     * @param array $joins
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception if the $joins parameter is not in proper format.
     *
     * @return string the JOIN clause built from {@see Query::$join}.
     */
    public function buildJoin(array $joins, array &$params): string;

    /**
     * @param Expression|int|null $limit
     * @param Expression|int|null $offset
     *
     * @return string the LIMIT and OFFSET clauses.
     */
    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string;

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the ORDER BY clause built from {@see Query::$orderBy}.
     */
    public function buildOrderBy(array $columns, array &$params = []): string;

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy the order by columns. See {@see Query::orderBy} for more details on how to specify this
     * parameter.
     * @param Expression|int|null $limit the limit number. See {@see Query::limit} for more details.
     * @param Expression|int|null $offset the offset number. See {@see Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|int|null $limit,
        Expression|int|null $offset,
        array &$params = []
    ): string;

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated.
     * @param bool|null $distinct
     * @param string|null $selectOption
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the SELECT clause built from {@see Query::$select}.
     */
    public function buildSelect(
        array $columns,
        array &$params,
        ?bool $distinct = false,
        ?string $selectOption = null
    ): string;

    /**
     * @param array $unions
     * @param array $params the binding parameters to be populated
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the UNION clause built from {@see Query::$union}.
     */
    public function buildUnion(array $unions, array &$params): string;

    /**
     * @param array|ConditionInterface|ExpressionInterface|string|null $condition
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the WHERE clause built from {@see Query::$where}.
     */
    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string;

    /**
     * @param array $withs
     * @param array $params
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    public function buildWithQueries(array $withs, array &$params): string;

    /**
     * Transforms $condition defined in array format (as described in {@see Query::where()} to instance of
     *
     * @param array $condition.
     *
     * @throws InvalidArgumentException
     *
     * @return ConditionInterface
     *
     * {@see ConditionInterface|ConditionInterface} according to {@see conditionClasses} map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface;

    /**
     * @throws InvalidArgumentException
     *
     * @psalm-suppress InvalidStringClass
     */
    public function getExpressionBuilder(ExpressionInterface $expression): object;

    /**
     * Creates a SELECT EXISTS() SQL statement.
     *
     * @param string $rawSql the sub-query in a raw form to select from.
     *
     * @return string the SELECT EXISTS() SQL statement.
     */
    public function selectExists(string $rawSql): string;

    /**
     * Setter for {@see conditionClasses} property.
     *
     * @param string[] $classes map of condition aliases to condition classes. For example:
     *
     * ```php
     * ['LIKE' => \Yiisoft\Db\Condition\LikeCondition::class]
     * ```
     *
     * See {@see conditionClasses} docs for details.
     */
    public function setConditionClasses(array $classes): void;

    /**
     * Setter for {@see expressionBuilders property.
     *
     * @param string[] $builders array of builders that should be merged with the pre-defined ones in property.
     *
     * See {@see expressionBuilders} docs for details.
     *
     * @psalm-param array<string, class-string<ExpressionBuilderInterface>> $builders
     */
    public function setExpressionBuilders(array $builders): void;

    /**
     * @param string the separator between different fragments of a SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void;
}
