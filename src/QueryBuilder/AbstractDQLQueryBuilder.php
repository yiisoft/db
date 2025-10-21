<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Expression\CompositeExpression;
use Yiisoft\Db\Expression\CompositeExpressionBuilder;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\Builder\ParamBuilder;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Value\Builder\ArrayValueBuilder;
use Yiisoft\Db\Expression\Value\ColumnName;
use Yiisoft\Db\Expression\Value\Builder\ColumnNameBuilder;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Builder\GreatestBuilder;
use Yiisoft\Db\Expression\Function\Builder\LeastBuilder;
use Yiisoft\Db\Expression\Function\Builder\LengthBuilder;
use Yiisoft\Db\Expression\Function\Builder\LongestBuilder;
use Yiisoft\Db\Expression\Function\Builder\ShortestBuilder;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\Builder\JsonValueBuilder;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Statement\Builder\CaseXBuilder;
use Yiisoft\Db\Expression\Value\StructuredValue;
use Yiisoft\Db\Expression\Value\Builder\StructuredValueBuilder;
use Yiisoft\Db\Expression\Value\Value;
use Yiisoft\Db\Expression\Value\Builder\ValueBuilder;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Expression\Value\Builder\DateTimeValueBuilder;
use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Simple;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryExpressionBuilder;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\QuoterInterface;

use function array_filter;
use function array_merge;
use function array_shift;
use function count;
use function implode;
use function is_array;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_split;
use function reset;
use function strtoupper;
use function trim;

/**
 * It's used to query data from a database.
 *
 * @link https://en.wikipedia.org/wiki/Data_query_language
 */
abstract class AbstractDQLQueryBuilder implements DQLQueryBuilderInterface
{
    protected string $separator = ' ';
    /**
     * @var array Map of condition aliases to condition classes. For example:
     *
     * ```php
     * return [
     *     'LIKE' => \Yiisoft\Db\QueryBuilder\Condition\Like::class,
     * ];
     * ```
     *
     * This property is used by {@see createConditionFromArray} method.
     *
     * See default condition classes list in {@see defaultConditionClasses()} method.
     *
     * In case you want to add custom conditions support, use the {@see setConditionClasses()} method.
     *
     * @see setConditonClasses()
     * @see defaultConditionClasses()
     *
     * @psalm-var array<string, class-string<ConditionInterface>> $conditionClasses
     */
    protected array $conditionClasses = [];
    /**
     * @var array Map of expression aliases to expression classes.
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
     *
     * @psalm-var array<class-string<ExpressionInterface>, class-string<ExpressionBuilderInterface>>
     */
    protected array $expressionBuilders = [];

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter
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
            $this->buildJoin($query->getJoins(), $params),
            $this->buildWhere($query->getWhere(), $params),
            $this->buildGroupBy($query->getGroupBy(), $params),
            $this->buildHaving($query->getHaving(), $params),
        ];
        $sql = implode($this->separator, array_filter($clauses));
        $sql = $this->buildOrderByAndLimit($sql, $query->getOrderBy(), $query->getLimit(), $query->getOffset(), $params);

        $for = $this->buildFor($query->getFor());
        if ($for !== '') {
            $sql .= $this->separator . $for;
        }

        $union = $this->buildUnion($query->getUnions(), $params);

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

            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
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
        if (empty($condition)) {
            if ($condition === '0') {
                return '0';
            }

            return '';
        }

        if (is_array($condition)) {
            $condition = $this->createConditionFromArray($condition);
        } elseif (is_string($condition)) {
            $condition = new Expression($condition, $params);
            $params = [];
        }

        return $this->buildExpression($condition, $params);
    }

    public function buildExpression(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder
            ->getExpressionBuilder($expression)
            ->build($expression, $params);
    }

    public function buildFor(array $values): string
    {
        if (empty($values)) {
            return '';
        }

        return 'FOR ' . implode($this->separator . 'FOR ', $values);
    }

    public function buildFrom(array|null $tables, array &$params): string
    {
        if (empty($tables)) {
            return '';
        }

        /** @psalm-var string[] $tables */
        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    public function buildGroupBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        /** @psalm-var array<string, ExpressionInterface|string> $columns */
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column, $params);
            } elseif (!str_contains($column, '(')) {
                $columns[$i] = $this->quoter->quoteColumnName($column);
            }
        }

        /** @psalm-var array<string, Expression|string> $columns */
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

        foreach ($joins as $i => $join) {
            [$joinType, $table] = $join;

            $tables = $this->quoteTableNames((array) $table, $params);

            /** @var string $table */
            $table = reset($tables);
            $joins[$i] = "$joinType $table";

            if (isset($join[2])) {
                if (is_array($join[2])) {
                    foreach ($join[2] as &$column) {
                        if (is_string($column)) {
                            $column = new ColumnName($column);
                        }
                    }
                    unset($column);
                }

                $condition = $this->buildCondition($join[2], $params);
                if ($condition !== '') {
                    $joins[$i] .= ' ON ' . $condition;
                }
            }
        }

        /** @psalm-var array<string> $joins */
        return implode($this->separator, $joins);
    }

    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql = 'LIMIT '
                . ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string) $limit);
        }

        if (!empty($offset)) {
            $sql .= ' OFFSET '
                . ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string) $offset);
        }

        return ltrim($sql);
    }

    public function buildOrderBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        $orders = [];

        /** @psalm-var array<string, ExpressionInterface|int|string> $columns */
        foreach ($columns as $name => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $orders[] = $this->buildExpression($direction, $params);
            } else {
                $orders[] = $this->quoter->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
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
        bool $distinct = false,
        ?string $selectOption = null
    ): string {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';

        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

        $quoter = $this->quoter;

        foreach ($columns as $i => $column) {
            $isIndexString = is_string($i);

            if (!is_string($column)) {
                $columns[$i] = $this->queryBuilder->buildValue($column, $params);
            } elseif (!str_contains($column, '(')) {
                if (!$isIndexString
                    && preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $column, $matches) === 1
                ) {
                    $columns[$i] = $quoter->quoteColumnName($matches[1])
                        . ' AS ' . $quoter->quoteSimpleColumnName($matches[2]);
                    continue;
                }

                $columns[$i] = $quoter->quoteColumnName($column);
            }

            if ($isIndexString && $i !== $column) {
                /**
                 * @var string $i
                 * @psalm-var string $columns[$i]
                 */
                $columns[$i] .= ' AS ' . $quoter->quoteColumnName($i);
            }
        }

        /** @psalm-var array<string, Expression|string> $columns */
        return $select . ' ' . implode(', ', $columns);
    }

    public function buildUnion(array $unions, array &$params): string
    {
        if (empty($unions)) {
            return '';
        }

        $result = '';

        /** @psalm-var array<array{query:string|Query, all:bool}> $unions */
        foreach ($unions as $union) {
            if ($union['query'] instanceof QueryInterface) {
                [$union['query'], $params] = $this->build($union['query'], $params);
            }

            $result .= 'UNION ' . ($union['all'] ? 'ALL ' : '') . '( ' . $union['query'] . ' ) ';
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

        /** @psalm-var array{query:string|Query, alias:ExpressionInterface|string, recursive:bool}[] $withs */
        foreach ($withs as $with) {
            if ($with['recursive']) {
                $recursive = true;
            }

            if ($with['query'] instanceof QueryInterface) {
                [$with['query'], $params] = $this->build($with['query'], $params);
            }

            $quotedAlias = $this->quoteCteAlias($with['alias']);

            $result[] = $quotedAlias . ' AS (' . $with['query'] . ')';
        }

        return 'WITH ' . ($recursive ? 'RECURSIVE ' : '') . implode(', ', $result);
    }

    public function createConditionFromArray(array $condition): ConditionInterface
    {
        /** operator format: operator, operand 1, operand 2, ... */
        if (isset($condition[0])) {
            $operator = strtoupper((string) array_shift($condition));
            $className = $this->conditionClasses[$operator] ?? Simple::class;
            return $className::fromArrayDefinition($operator, $condition);
        }

        $conditions = [];
        foreach ($condition as $column => $value) {
            if (!is_string($column)) {
                throw new InvalidArgumentException('Condition array must have string keys.');
            }
            if (is_iterable($value) || $value instanceof QueryInterface) {
                $conditions[] = new Condition\In($column, $value);
                continue;
            }
            $conditions[] = new Condition\Equals($column, $value);
        }

        return count($conditions) === 1 ? $conditions[0] : new Condition\AndX(...$conditions);
    }

    public function getExpressionBuilder(ExpressionInterface $expression): ExpressionBuilderInterface
    {
        $className = $expression::class;

        if (!isset($this->expressionBuilders[$className])) {
            throw new InvalidArgumentException(
                'Expression of class ' . $className . ' can not be built in ' . static::class
            );
        }

        return new $this->expressionBuilders[$className]($this->queryBuilder);
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT EXISTS(' . $rawSql . ') AS ' . $this->quoter->quoteSimpleColumnName('0');
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
     * @param string $separator The separator between different fragments of an SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    /**
     * Has an array of default condition classes.
     *
     * Extend this method if you want to change default condition classes for the query builder.
     *
     * See {@see conditionClasses} docs for details.
     *
     * @psalm-return array<string, class-string<ConditionInterface>>
     */
    protected function defaultConditionClasses(): array
    {
        return [
            'NOT' => Condition\Not::class,
            'AND' => Condition\AndX::class,
            'OR' => Condition\OrX::class,
            '=' => Condition\Equals::class,
            '!=' => Condition\NotEquals::class,
            '<>' => Condition\NotEquals::class,
            '>' => Condition\GreaterThan::class,
            '>=' => Condition\GreaterThanOrEqual::class,
            '<' => Condition\LessThan::class,
            '<=' => Condition\LessThanOrEqual::class,
            'BETWEEN' => Condition\Between::class,
            'NOT BETWEEN' => Condition\NotBetween::class,
            'IN' => Condition\In::class,
            'NOT IN' => Condition\NotIn::class,
            'LIKE' => Condition\Like::class,
            'NOT LIKE' => Condition\NotLike::class,
            'EXISTS' => Condition\Exists::class,
            'NOT EXISTS' => Condition\NotExists::class,
            'ARRAY OVERLAPS' => Condition\ArrayOverlaps::class,
            'JSON OVERLAPS' => Condition\JsonOverlaps::class,
        ];
    }

    /**
     * Has an array of default expression builders.
     *
     * Extend this method and override it if you want to change default expression builders for this query builder.
     *
     * See {@see expressionBuilders} docs for details.
     *
     * @psalm-return array<class-string<ExpressionInterface>, class-string<ExpressionBuilderInterface>>
     */
    protected function defaultExpressionBuilders(): array
    {
        return [
            Query::class => QueryExpressionBuilder::class,
            Param::class => ParamBuilder::class,
            Expression::class => ExpressionBuilder::class,
            CompositeExpression::class => CompositeExpressionBuilder::class,
            Condition\Not::class => Condition\Builder\NotBuilder::class,
            Condition\AndX::class => Condition\Builder\LogicalBuilder::class,
            Condition\OrX::class => Condition\Builder\LogicalBuilder::class,
            Condition\Between::class => Condition\Builder\BetweenBuilder::class,
            Condition\NotBetween::class => Condition\Builder\BetweenBuilder::class,
            Condition\In::class => Condition\Builder\InBuilder::class,
            Condition\NotIn::class => Condition\Builder\InBuilder::class,
            Condition\Like::class => Condition\Builder\LikeBuilder::class,
            Condition\NotLike::class => Condition\Builder\LikeBuilder::class,
            Condition\Equals::class => Condition\Builder\CompareBuilder::class,
            Condition\NotEquals::class => Condition\Builder\CompareBuilder::class,
            Condition\GreaterThan::class => Condition\Builder\CompareBuilder::class,
            Condition\GreaterThanOrEqual::class => Condition\Builder\CompareBuilder::class,
            Condition\LessThan::class => Condition\Builder\CompareBuilder::class,
            Condition\LessThanOrEqual::class => Condition\Builder\CompareBuilder::class,
            Condition\Exists::class => Condition\Builder\ExistsBuilder::class,
            Condition\NotExists::class => Condition\Builder\ExistsBuilder::class,
            Condition\All::class => Condition\Builder\AllBuilder::class,
            Condition\None::class => Condition\Builder\NoneBuilder::class,
            Simple::class => Condition\Builder\SimpleBuilder::class,
            JsonValue::class => JsonValueBuilder::class,
            ArrayValue::class => ArrayValueBuilder::class,
            StructuredValue::class => StructuredValueBuilder::class,
            CaseX::class => CaseXBuilder::class,
            ColumnName::class => ColumnNameBuilder::class,
            Value::class => ValueBuilder::class,
            DateTimeValue::class => DateTimeValueBuilder::class,
            Length::class => LengthBuilder::class,
            Greatest::class => GreatestBuilder::class,
            Least::class => LeastBuilder::class,
            Longest::class => LongestBuilder::class,
            Shortest::class => ShortestBuilder::class,
        ];
    }

    /**
     * Extracts table alias if there is one or returns false.
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array The list of table names with quote.
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
            } elseif ($table instanceof ExpressionInterface && is_string($i)) {
                $table = $this->buildExpression($table, $params);
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

    /**
     * Quotes an alias of Common Table Expressions (CTE)
     *
     * @param ExpressionInterface|string $name The alias name with or without column names to quote.
     *
     * @return string The quoted alias.
     */
    private function quoteCteAlias(ExpressionInterface|string $name): string
    {
        if ($name instanceof ExpressionInterface) {
            return $this->buildExpression($name);
        }

        if (!str_contains($name, '(')) {
            return $this->quoter->quoteTableName($name);
        }

        if (!str_ends_with($name, ')')) {
            return $name;
        }

        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        [$name, $columns] = explode('(', substr($name, 0, -1), 2);
        $name = trim($name);

        return $this->quoter->quoteTableName($name) . '(' . $this->buildColumns($columns) . ')';
    }
}
