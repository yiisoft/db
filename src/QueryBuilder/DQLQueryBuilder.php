<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamBuilder;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\HashCondition;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\ConditionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\SimpleCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryExpressionBuilder;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_filter;
use function array_merge;
use function array_shift;
use function ctype_digit;
use function get_class;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_split;
use function reset;
use function strtoupper;
use function trim;

abstract class DQLQueryBuilder implements DQLQueryBuilderInterface
{
    protected string $separator = ' ';

    /**
     * @var array map of condition aliases to condition classes. For example:
     *
     * ```php
     * return [
     *     'LIKE' => \Yiisoft\Db\Condition\LikeCondition::class,
     * ];
     * ```
     *
     * This property is used by {@see createConditionFromArray} method.
     * See default condition classes list in {@see defaultConditionClasses()} method.
     *
     * In case you want to add custom conditions support, use the {@see setConditionClasses()} method.
     *
     * @see setConditonClasses()
     * @see defaultConditionClasses()
     */
    protected array $conditionClasses = [];

    /**
     * @psalm-var array<string, class-string<ExpressionBuilderInterface>> maps expression class to expression builder
     * class.
     *
     * For example:
     *
     * ```php
     * [
     *    Expression::class => ExpressionBuilder::class
     * ]
     * ```
     * This property is mainly used by {@see buildExpression()} to build SQL expressions form expression objects.
     * See default values in {@see defaultExpressionBuilders()} method.
     *
     * {@see setExpressionBuilders()}
     * {@see defaultExpressionBuilders()}
     */
    protected array $expressionBuilders = [];

    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        $this->expressionBuilders = $this->defaultExpressionBuilders();
        $this->conditionClasses = $this->defaultConditionClasses();
    }

    public function build(QueryInterface $query, array $params = []): array
    {
        $query = $query->prepare($this->queryBuilder);
        $params = empty($params) ? $query->getParams() : array_merge($params, $query->getParams());
        $clauses = [
            $this->buildSelect($query->getSelect(), $params, $query->getDistinct(), $query->getSelectOption()),
            $this->buildFrom($query->getFrom(), $params),
            $this->buildJoin($query->getJoin(), $params),
            $this->buildWhere($query->getWhere(), $params),
            $this->buildGroupBy($query->getGroupBy(), $params),
            $this->buildHaving($query->getHaving(), $params),
        ];
        $sql = implode($this->separator, array_filter($clauses));
        $sql = $this->buildOrderByAndLimit($sql, $query->getOrderBy(), $query->getLimit(), $query->getOffset());

        if (!empty($query->getOrderBy())) {
            /** @psalm-var array<string, ExpressionInterface|string> */
            foreach ($query->getOrderBy() as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        if (!empty($query->getGroupBy())) {
            /** @psalm-var array<string, ExpressionInterface|string> */
            foreach ($query->getGroupBy() as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        $union = $this->buildUnion($query->getUnion(), $params);

        if ($union !== '') {
            $sql = "($sql)$this->separator$union";
        }

        $with = $this->buildWithQueries($query->getWithQueries(), $params);

        if ($with !== '') {
            $sql = "$with$this->separator$sql";
        }

        return [$sql, $params];
    }

    public function buildColumns(array|string $columns): string
    {
        if (!is_array($columns)) {
            if (str_contains($columns, '(')) {
                return $columns;
            }

            $rawColumns = $columns;
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);

            if ($columns === false) {
                throw new InvalidArgumentException("$rawColumns is not valid columns.");
            }
        }

        /** @psalm-var array<array-key, ExpressionInterface|string> $columns */
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column);
            } elseif (!str_contains($column, '(')) {
                $columns[$i] = $this->quoter->quoteColumnName($column);
            }
        }

        /** @psalm-var string[] $columns */
        return implode(', ', $columns);
    }

    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string
    {
        if (is_array($condition)) {
            if (empty($condition)) {
                return '';
            }

            $condition = $this->createConditionFromArray($condition);
        }

        if ($condition instanceof ExpressionInterface) {
            return $this->buildExpression($condition, $params);
        }

        return $condition ?? '';
    }

    /**
     * @throws InvalidArgumentException
     *
     * @psalm-suppress UndefinedInterfaceMethod
     * @psalm-suppress MixedMethodCall
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string
    {
        $builder = $this->queryBuilder->getExpressionBuilder($expression);
        return (string) $builder->build($expression, $params);
    }

    public function buildFrom(?array $tables, array &$params): string
    {
        if (empty($tables)) {
            return '';
        }

        /** @psalm-var string[] */
        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    public function buildGroupBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        /** @psalm-var array<string, Expression|string> $columns */
        foreach ($columns as $i => $column) {
            if ($column instanceof Expression) {
                $columns[$i] = $this->buildExpression($column);
                $params = array_merge($params, $column->getParams());
            } elseif (!str_contains($column, '(')) {
                $columns[$i] = $this->quoter->quoteColumnName($column);
            }
        }

        return 'GROUP BY ' . implode(', ', $columns);
    }

    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string
    {
        $having = $this->buildCondition($condition, $params);

        return ($having === '') ? '' : ('HAVING ' . $having);
    }

    public function buildJoin(array $joins, array &$params): string
    {
        if (empty($joins)) {
            return '';
        }

        /**
         * @psalm-var array<
         *   array-key,
         *   array{
         *     0?:string,
         *     1?:array<array-key, Query|string>|string,
         *     2?:array|ExpressionInterface|string|null
         *   }|null
         * > $joins
         */
        foreach ($joins as $i => $join) {
            if (!is_array($join) || !isset($join[0], $join[1])) {
                throw new Exception(
                    'A join clause must be specified as an array of join type, join table, and optionally join '
                    . 'condition.'
                );
            }

            /* 0:join type, 1:join table, 2:on-condition (optional) */
            [$joinType, $table] = $join;

            $tables = $this->quoteTableNames((array) $table, $params);

            /** @var string $table */
            $table = reset($tables);
            $joins[$i] = "$joinType $table";

            if (isset($join[2])) {
                $condition = $this->buildCondition($join[2], $params);
                if ($condition !== '') {
                    $joins[$i] .= ' ON ' . $condition;
                }
            }
        }

        /** @psalm-var array<string> $joins */
        return implode($this->separator, $joins);
    }

    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . (string) $limit;
        }

        if ($this->hasOffset($offset)) {
            $sql .= ' OFFSET ' . (string) $offset;
        }

        return ltrim($sql);
    }

    public function buildOrderBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        $orders = [];

        /** @psalm-var array<string, Expression|int|string> $columns */
        foreach ($columns as $name => $direction) {
            if ($direction instanceof Expression) {
                $orders[] = $this->buildExpression($direction);
                $params = array_merge($params, $direction->getParams());
            } else {
                $orders[] = $this->quoter->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|int|null $limit,
        Expression|int|null $offset,
        array &$params = []
    ): string {
        $orderBy = $this->buildOrderBy($orderBy, $params);
        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }
        $limit = $this->buildLimit($limit, $offset);
        if ($limit !== '') {
            $sql .= $this->separator . $limit;
        }

        return $sql;
    }

    public function buildSelect(
        array $columns,
        array &$params,
        ?bool $distinct = false,
        ?string $selectOption = null
    ): string {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';

        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

        /** @psalm-var array<array-key, ExpressionInterface|Query|string> $columns */
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                if (is_int($i)) {
                    $columns[$i] = $this->buildExpression($column, $params);
                } else {
                    $columns[$i] = $this->buildExpression($column, $params) . ' AS '
                        . $this->quoter->quoteColumnName($i);
                }
            } elseif ($column instanceof QueryInterface) {
                [$sql, $params] = $this->build($column, $params);
                $columns[$i] = "($sql) AS " . $this->quoter->quoteColumnName((string) $i);
            } elseif (is_string($i) && $i !== $column) {
                if (!str_contains($column, '(')) {
                    $column = $this->quoter->quoteColumnName($column);
                }
                $columns[$i] = "$column AS " . $this->quoter->quoteColumnName($i);
            } elseif (!str_contains($column, '(')) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $column, $matches)) {
                    $columns[$i] = $this->quoter->quoteColumnName($matches[1])
                        . ' AS ' . $this->quoter->quoteColumnName($matches[2]);
                } else {
                    $columns[$i] = $this->quoter->quoteColumnName($column);
                }
            }
        }

        return $select . ' ' . implode(', ', $columns);
    }

    public function buildUnion(array $unions, array &$params): string
    {
        if (empty($unions)) {
            return '';
        }

        $result = '';

        /** @psalm-var array<array{query:Query|string, all:bool}> $unions */
        foreach ($unions as $i => $union) {
            $query = $union['query'];
            if ($query instanceof QueryInterface) {
                [$unions[$i]['query'], $params] = $this->build($query, $params);
            }

            $result .= 'UNION ' . ($union['all'] ? 'ALL ' : '') . '( ' . $unions[$i]['query'] . ' ) ';
        }

        return trim($result);
    }

    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string {
        $where = $this->buildCondition($condition, $params);
        return ($where === '') ? '' : ('WHERE ' . $where);
    }

    public function buildWithQueries(array $withs, array &$params): string
    {
        if (empty($withs)) {
            return '';
        }

        $recursive = false;
        $result = [];

        /** @psalm-var array<array-key, array{query:string|Query, alias:string, recursive:bool}> $withs */
        foreach ($withs as $with) {
            if ($with['recursive']) {
                $recursive = true;
            }

            $query = $with['query'];
            if ($query instanceof QueryInterface) {
                [$with['query'], $params] = $this->build($query, $params);
            }

            $result[] = $with['alias'] . ' AS (' . $with['query'] . ')';
        }

        return 'WITH ' . ($recursive ? 'RECURSIVE ' : '') . implode(', ', $result);
    }

    public function createConditionFromArray(array $condition): ConditionInterface
    {
        /** operator format: operator, operand 1, operand 2, ... */
        if (isset($condition[0])) {
            $operator = strtoupper((string) array_shift($condition));

            /** @var string $className */
            $className = $this->conditionClasses[$operator] ?? SimpleCondition::class;

            /** @var ConditionInterface $className */
            return $className::fromArrayDefinition($operator, $condition);
        }

        /** hash format: 'column1' => 'value1', 'column2' => 'value2', ... */
        return new HashCondition($condition);
    }

    public function getExpressionBuilder(ExpressionInterface $expression): object
    {
        $className = get_class($expression);

        if (!isset($this->expressionBuilders[$className])) {
            throw new InvalidArgumentException(
                'Expression of class ' . $className . ' can not be built in ' . static::class
            );
        }

        return new $this->expressionBuilders[$className]($this->queryBuilder);
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT EXISTS(' . $rawSql . ')';
    }

    public function setConditionClasses(array $classes): void
    {
        $this->conditionClasses = array_merge($this->conditionClasses, $classes);
    }

    public function setExpressionBuilders(array $builders): void
    {
        $this->expressionBuilders = array_merge($this->expressionBuilders, $builders);
    }

    /**
     * @param string the separator between different fragments of a SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    /**
     * Contains array of default condition classes. Extend this method, if you want to change default condition classes
     * for the query builder.
     *
     * @return array
     *
     * See {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return [
            'NOT' => Conditions\NotCondition::class,
            'AND' => Conditions\AndCondition::class,
            'OR' => Conditions\OrCondition::class,
            'BETWEEN' => Conditions\BetweenCondition::class,
            'NOT BETWEEN' => Conditions\BetweenCondition::class,
            'IN' => Conditions\InCondition::class,
            'NOT IN' => Conditions\InCondition::class,
            'LIKE' => Conditions\LikeCondition::class,
            'NOT LIKE' => Conditions\LikeCondition::class,
            'OR LIKE' => Conditions\LikeCondition::class,
            'OR NOT LIKE' => Conditions\LikeCondition::class,
            'EXISTS' => Conditions\ExistsCondition::class,
            'NOT EXISTS' => Conditions\ExistsCondition::class,
        ];
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see expressionBuilders} docs for details.
     *
     * @psalm-return array<string, class-string<ExpressionBuilderInterface>>
     */
    protected function defaultExpressionBuilders(): array
    {
        return [
            Query::class => QueryExpressionBuilder::class,
            Param::class => ParamBuilder::class,
            Expression::class => ExpressionBuilder::class,
            Conditions\ConjunctionCondition::class => Conditions\Builder\ConjunctionConditionBuilder::class,
            Conditions\NotCondition::class => Conditions\Builder\NotConditionBuilder::class,
            Conditions\AndCondition::class => Conditions\Builder\ConjunctionConditionBuilder::class,
            Conditions\OrCondition::class => Conditions\Builder\ConjunctionConditionBuilder::class,
            Conditions\BetweenCondition::class => Conditions\Builder\BetweenConditionBuilder::class,
            Conditions\InCondition::class => Conditions\Builder\InConditionBuilder::class,
            Conditions\LikeCondition::class => Conditions\Builder\LikeConditionBuilder::class,
            Conditions\ExistsCondition::class => Conditions\Builder\ExistsConditionBuilder::class,
            Conditions\SimpleCondition::class => Conditions\Builder\SimpleConditionBuilder::class,
            Conditions\HashCondition::class => Conditions\Builder\HashConditionBuilder::class,
            Conditions\BetweenColumnsCondition::class => Conditions\Builder\BetweenColumnsConditionBuilder::class,
        ];
    }

    /**
     * Extracts table alias if there is one or returns false.
     *
     * @param string $table
     *
     * @return array|bool
     *
     * @psalm-return string[]|bool
     */
    protected function extractAlias(string $table): array|bool
    {
        if (preg_match('/^(.*?)(?i:\s+as|)\s+([^ ]+)$/', $table, $matches)) {
            return $matches;
        }

        return false;
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit the given limit.
     *
     * @return bool whether the limit is effective.
     */
    protected function hasLimit(mixed $limit): bool
    {
        return ($limit instanceof ExpressionInterface) || ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset the given offset.
     *
     * @return bool whether the offset is effective.
     */
    protected function hasOffset(mixed $offset): bool
    {
        return ($offset instanceof ExpressionInterface) || (ctype_digit((string)$offset) && (string)$offset !== '0');
    }

    /**
     * Quotes table names passed.
     *
     * @param array $tables
     * @param array $params
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return array
     */
    private function quoteTableNames(array $tables, array &$params): array
    {
        /** @psalm-var array<array-key, array|QueryInterface|string> $tables */
        foreach ($tables as $i => $table) {
            if ($table instanceof QueryInterface) {
                [$sql, $params] = $this->build($table, $params);
                $tables[$i] = "($sql) " . $this->quoter->quoteTableName((string) $i);
            } elseif (is_string($table) && is_string($i)) {
                if (!str_contains($table, '(')) {
                    $table = $this->quoter->quoteTableName($table);
                }
                $tables[$i] = "$table " . $this->quoter->quoteTableName($i);
            } elseif (is_string($table) && !str_contains($table, '(')) {
                $tableWithAlias = $this->extractAlias($table);
                if (is_array($tableWithAlias)) { // with alias
                    $tables[$i] = $this->quoter->quoteTableName($tableWithAlias[1]) . ' '
                        . $this->quoter->quoteTableName($tableWithAlias[2]);
                } else {
                    $tables[$i] = $this->quoter->quoteTableName($table);
                }
            }
        }

        return $tables;
    }
}
