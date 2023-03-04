<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface DQLQueryBuilderInterface
{
    /**
     * Generates a SELECT SQL statement from a {@see Query} object.
     *
     * @param QueryInterface $query The {@see Query} object from which the SQL statement will be generated.
     * @param array $params The parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array The generated SQL statement (the first array element) and the corresponding parameters to be bound
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
     * @param array|string $columns The columns to be processed.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function buildColumns(array|string $columns): string;

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition The condition specification.
     * Please refer to {@see Query::where()} on how to specify a condition.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string;

    /**
     * Builds given $expression.
     *
     * @param ExpressionInterface $expression The expression to be built
     * @param array $params The parameters to be bound to the generated SQL statement. These parameters will be
     * included in the result with the additional parameters generated during the expression building process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException When $expression building is not supported by this QueryBuilder.
     *
     * @return string The SQL statement that will not be neither quoted nor encoded before passing to DBMS.
     *
     * @see ExpressionInterface
     * @see ExpressionBuilderInterface
     * @see expressionBuilders
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string;

    /**
     * @param array|null $tables The tables to be processed.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The FROM clause built from {@see Query::$from}.
     */
    public function buildFrom(array|null $tables, array &$params): string;

    /**
     * @param array $columns The columns to be grouped by. Each column can be a string representing a column name or an
     * array representing a column specification. Please refer to {@see Query::groupBy()} on how to specify this
     * parameter.
     * @param array $params The binding parameters to be populated
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The GROUP BY clause
     */
    public function buildGroupBy(array $columns, array &$params = []): string;

    /**
     * @param array|ExpressionInterface|string|null $condition The condition specification.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The HAVING clause built from {@see Query::$having}.
     */
    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string;

    /**
     * @param array $joins The joins to be processed.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception If the $joins parameter is not in proper format.
     *
     * @return string The JOIN clause built from {@see Query::$join}.
     */
    public function buildJoin(array $joins, array &$params): string;

    /**
     * @param ExpressionInterface|int|null $limit The limit number. See {@see Query::limit} for more details.
     * @param ExpressionInterface|int|null $offset The offset number. See {@see Query::offset} for more details.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return string The LIMIT and OFFSET clauses.
     */
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string;

    /**
     * @param array $columns The columns to be ordered by. Each column can be a string representing a column name or an
     * array representing a column specification. Please refer to {@see Query::orderBy()} on how to specify this
     * parameter.
     * @param array $params The binding parameters to be populated
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The ORDER BY clause built from {@see Query::$orderBy}.
     */
    public function buildOrderBy(array $columns, array &$params = []): string;

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     *
     * @param string $sql The existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy The order by columns. See {@see Query::orderBy} for more details on how to specify this
     * parameter.
     * @param ExpressionInterface|int|null $limit the limit number. See {@see Query::limit} for more details.
     * @param ExpressionInterface|int|null $offset the offset number. See {@see Query::offset} for more details.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = []
    ): string;

    /**
     * @param array $columns The columns to be selected. Each column can be a string representing a column name or an
     * array representing a column specification. Please refer to {@see Query::select()} on how to specify this
     * parameter.
     * @param array $params The binding parameters to be populated.
     * @param bool|null $distinct  Whether to add DISTINCT or not.
     * @param string|null $selectOption The SELECT option to use (e.g. SQL_CALC_FOUND_ROWS).
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The SELECT clause built from {@see Query::$select}.
     */
    public function buildSelect(
        array $columns,
        array &$params,
        bool|null $distinct = false,
        string $selectOption = null
    ): string;

    /**
     * @param array $unions The UNION queries to be processed.
     * @param array $params The binding parameters to be populated
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The UNION clause built from {@see Query::$union}.
     */
    public function buildUnion(array $unions, array &$params): string;

    /**
     * @param array|ConditionInterface|ExpressionInterface|string|null $condition The condition built from
     * {@see Query::$where}.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The WHERE clause built from {@see Query::$where}.
     */
    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string;

    /**
     * @param array $withs The WITH queries to be processed.
     * @param array $params The binding parameters to be populated
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The WITH clause built from {@see Query::$with}.
     */
    public function buildWithQueries(array $withs, array &$params): string;

    /**
     * Transforms $condition defined in array format (as described in {@see Query::where()} to instance
     * of {@see ConditionInterface}).
     *
     * @param array $condition The condition in array format.
     *
     * @throws InvalidArgumentException
     *
     * {@see ConditionInterface} according to conditions class map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface;

    /**
     * @throws InvalidArgumentException
     *
     * @psalm-suppress InvalidStringClass
     *
     * @return object Instance of {@see ExpressionBuilderInterface} for the given expression.
     */
    public function getExpressionBuilder(ExpressionInterface $expression): object;

    /**
     * Creates a SELECT EXISTS() SQL statement.
     *
     * @param string $rawSql The sub-query in a raw form to select from.
     *
     * @return string The SELECT EXISTS() SQL statement.
     */
    public function selectExists(string $rawSql): string;

    /**
     * Setter for {@see conditionClasses} property.
     *
     * @param string[] $classes Map of condition aliases to condition classes. For example:
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
     * @param string[] $builders Array of builders that should be merged with the pre-defined ones in property.
     *
     * See {@see expressionBuilders} docs for details.
     *
     * @psalm-param array<string, class-string<ExpressionBuilderInterface>> $builders
     */
    public function setExpressionBuilders(array $builders): void;

    /**
     * @param string $separator The separator between different fragments of a SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void;
}
