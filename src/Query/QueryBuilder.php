<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Generator;
use JsonException;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Pdo\PdoValueBuilder;
use Yiisoft\Db\Query\Conditions\HashCondition;
use Yiisoft\Db\Query\Conditions\Interface\ConditionInterface;
use Yiisoft\Db\Query\Conditions\SimpleCondition;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function ctype_digit;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_encode;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_split;
use function reset;
use function strtoupper;
use function trim;

/**
 * QueryBuilder builds a SELECT SQL statement based on the specification given as a {@see Query} object.
 *
 * SQL statements are created from {@see Query} objects using the {@see build()}-method.
 *
 * QueryBuilder is also used by {@see Command} to build SQL statements such as INSERT, UPDATE, DELETE, CREATE TABLE.
 *
 * For more details and usage information on QueryBuilder:
 * {@see [guide article on query builders](guide:db-query-builder)}.
 *
 * @property string[] $conditionClasses Map of condition aliases to condition classes. This property is write-only.
 *
 * For example:
 * ```php
 *     ['LIKE' => \Yiisoft\Db\Condition\LikeCondition::class]
 * ```
 * @property string[] $expressionBuilders Array of builders that should be merged with the pre-defined one's in
 * {@see expressionBuilders} property. This property is write-only.
 */
abstract class QueryBuilder implements QueryBuilderInterface
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    public const PARAM_PREFIX = ':qp';

    /**
     * @var array the abstract column types mapped to physical column types.
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications.
     * Child classes should override this property to declare supported type mappings.
     *
     * @psalm-var string[]
     */
    protected array $typeMap = [];

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
     * @psalm-var string[] maps expression class to expression builder class.
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
    protected string $separator = ' ';
    protected DDLQueryBuilder $ddlBuilder;
    protected DMLQueryBuilder $dmlBuilder;

    public function __construct(
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        $this->expressionBuilders = $this->defaultExpressionBuilders();
        $this->conditionClasses = $this->defaultConditionClasses();
    }

    public function addCheck(string $name, string $table, string $expression): string
    {
        return $this->ddlBuilder->addCheck($name, $table, $expression);
    }

    public function addColumn(string $table, string $column, string $type): string
    {
        return $this->ddlBuilder->addColumn($table, $column, $type);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnColumn($table, $column, $comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnTable($table, $comment);
    }

    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        return $this->ddlBuilder->addDefaultValue($name, $table, $column, $value);
    }

    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): string {
        return $this->ddlBuilder->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
    }

    public function addPrimaryKey(string $name, string $table, array|string $columns): string
    {
        return $this->ddlBuilder->addPrimaryKey($name, $table, $columns);
    }

    public function addUnique(string $name, string $table, array|string $columns): string
    {
        return $this->ddlBuilder->addUnique($name, $table, $columns);
    }

    public function alterColumn(string $table, string $column, string $type): string
    {
        return $this->ddlBuilder->alterColumn($table, $column, $type);
    }

    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        return $this->dmlBuilder->batchInsert($table, $columns, $rows, $params);
    }

    public function bindParam(mixed $value, array &$params = []): string
    {
        $phName = self::PARAM_PREFIX . count($params);
        /** @psalm-var mixed */
        $params[$phName] = $value;

        return $phName;
    }

    public function build(QueryInterface $query, array $params = []): array
    {
        $query = $query->prepare($this);
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
            foreach ($query->getOrderBy() as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        if (!empty($query->getGroupBy())) {
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

        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column);
            } elseif (!str_contains($column, '(')) {
                $columns[$i] = $this->quoter->quoteColumnName($column);
            }
        }

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
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string
    {
        $builder = $this->getExpressionBuilder($expression);
        return (string) $builder->build($expression, $params);
    }

    public function buildFrom(?array $tables, array &$params): string
    {
        if (empty($tables)) {
            return '';
        }

        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    public function buildGroupBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

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

    public function buildHaving(array|string|null $condition, array &$params = []): string
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
        string $selectOption = null
    ): string {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';

        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

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
                    $columns[$i] = $this->quoter->quoteColumnName(
                        $matches[1]
                    ) . ' AS ' . $this->quoter->quoteColumnName($matches[2]);
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

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return $this->ddlBuilder->checkIntegrity($schema, $table, $check);
    }

    public function createConditionFromArray(array $condition): ConditionInterface
    {
        /** operator format: operator, operand 1, operand 2, ... */
        if (isset($condition[0])) {
            $operator = strtoupper((string) array_shift($condition));

            $className = $this->conditionClasses[$operator] ?? SimpleCondition::class;

            /** @var ConditionInterface $className */
            return $className::fromArrayDefinition($operator, $condition);
        }

        /** hash format: 'column1' => 'value1', 'column2' => 'value2', ... */
        return new HashCondition($condition);
    }

    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return $this->ddlBuilder->createIndex($name, $table, $columns, $unique);
    }

    public function createTable(string $table, array $columns, ?string $options = null): string
    {
        return $this->ddlBuilder->createTable($table, $columns, $options);
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): string
    {
        return $this->ddlBuilder->createView($viewName, $subQuery);
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        return $this->dmlBuilder->delete($table, $condition, $params);
    }

    public function dropCheck(string $name, string $table): string
    {
        return $this->ddlBuilder->dropCheck($name, $table);
    }

    public function dropColumn(string $table, string $column): string
    {
        return $this->ddlBuilder->dropColumn($table, $column);
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->ddlBuilder->dropCommentFromColumn($table, $column);
    }

    public function dropCommentFromTable(string $table): string
    {
        return $this->ddlBuilder->dropCommentFromTable($table);
    }

    public function dropDefaultValue(string $name, string $table): string
    {
        return $this->ddlBuilder->dropDefaultValue($name, $table);
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return $this->ddlBuilder->dropForeignKey($name, $table);
    }

    public function dropIndex(string $name, string $table): string
    {
        return $this->ddlBuilder->dropIndex($name, $table);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return $this->ddlBuilder->dropPrimaryKey($name, $table);
    }

    public function dropTable(string $table): string
    {
        return $this->ddlBuilder->dropTable($table);
    }

    public function dropUnique(string $name, string $table): string
    {
        return $this->ddlBuilder->dropUnique($name, $table);
    }

    public function dropView(string $viewName): string
    {
        return $this->ddlBuilder->dropView($viewName);
    }

    public function getColumnType(ColumnSchemaBuilder|string $type): string
    {
        if ($type instanceof ColumnSchemaBuilder) {
            $type = $type->__toString();
        }

        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        }

        if (preg_match('/^(\w+)\((.+?)\)(.*)$/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace(
                    '/\(.+\)/',
                    '(' . $matches[2] . ')',
                    $this->typeMap[$matches[1]]
                ) . $matches[3];
            }
        } elseif (preg_match('/^(\w+)\s+/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace('/^\w+/', $this->typeMap[$matches[1]], $type);
            }
        }

        return $type;
    }

    public function getExpressionBuilder(ExpressionInterface $expression): ExpressionBuilderInterface
    {
        $className = get_class($expression);

        if (!isset($this->expressionBuilders[$className])) {
            throw new InvalidArgumentException(
                'Expression of class ' . $className . ' can not be built in ' . static::class
            );
        }

        return new $this->expressionBuilders[$className]($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter;
    }

    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insert($table, $columns, $params);
    }

    public function insertEx(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insertEx($table, $columns, $params);
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return $this->ddlBuilder->renameColumn($table, $oldName, $newName);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return $this->ddlBuilder->renameTable($oldName, $newName);
    }

    public function resetSequence(string $tableName, array|int|string|null $value = null): string
    {
        return $this->dmlBuilder->resetSequence($tableName, $value);
    }

    public function selectExists(string $rawSql): string
    {
        return $this->dmlBuilder->selectExists($rawSql);
    }

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
    public function setConditionClasses(array $classes): void
    {
        $this->conditionClasses = array_merge($this->conditionClasses, $classes);
    }

    /**
     * Setter for {@see expressionBuilders property.
     *
     * @param string[] $builders array of builders that should be merged with the pre-defined ones in property.
     *
     * See {@see expressionBuilders} docs for details.
     */
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

    public function truncateTable(string $table): string
    {
        return $this->ddlBuilder->truncateTable($table);
    }

    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        return $this->dmlBuilder->update($table, $columns, $condition, $params);
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
    ): string {
        return $this->dmlBuilder->upsert($table, $insertColumns, $updateColumns, $params);
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
     */
    protected function defaultExpressionBuilders(): array
    {
        return [
            Query::class => QueryExpressionBuilder::class,
            PdoValue::class => PdoValueBuilder::class,
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
     * Prepare select-subquery and field names for INSERT INTO ... SELECT SQL statement.
     *
     * @param QueryInterface $columns Object, which represents select query.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array array of column names, values and params.
     */
    protected function prepareInsertSelectSubQuery(QueryInterface $columns, array $params = []): array
    {
        if (empty($columns->getSelect()) || in_array('*', $columns->getSelect(), true)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->build($columns, $params);

        $names = [];
        $values = ' ' . $values;

        foreach ($columns->getSelect() as $title => $field) {
            if (is_string($title)) {
                $names[] = $this->quoter->quoteColumnName($title);
            } elseif (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                $names[] = $this->quoter->quoteColumnName($matches[2]);
            } else {
                $names[] = $this->quoter->quoteColumnName($field);
            }
        }

        return [$names, $values, $params];
    }

    public function prepareInsertValues(string $table, QueryInterface|array $columns, array $params = []): array
    {
        $tableSchema = $this->schema->getTableSchema($table);
        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];
        $names = [];
        $placeholders = [];
        $values = ' DEFAULT VALUES';

        if ($columns instanceof QueryInterface) {
            [$names, $values, $params] = $this->prepareInsertSelectSubQuery($columns, $params);
        } else {
            foreach ($columns as $name => $value) {
                $names[] = $this->quoter->quoteColumnName($name);
                $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->buildExpression($value, $params);
                } elseif ($value instanceof QueryInterface) {
                    [$sql, $params] = $this->build($value, $params);
                    $placeholders[] = "($sql)";
                } else {
                    $placeholders[] = $this->bindParam($value, $params);
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    public function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $tableSchema = $this->schema->getTableSchema($table);

        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];

        $sets = [];

        foreach ($columns as $name => $value) {
            /** @psalm-var mixed $value */
            $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
            if ($value instanceof ExpressionInterface) {
                $placeholder = $this->buildExpression($value, $params);
            } else {
                $placeholder = $this->bindParam($value, $params);
            }

            $sets[] = $this->quoter->quoteColumnName($name) . '=' . $placeholder;
        }

        return [$sets, $params];
    }

    public function prepareUpsertColumns(
        string $table,
        QueryInterface|array $insertColumns,
        QueryInterface|bool|array $updateColumns,
        array &$constraints = []
    ): array {
        if ($insertColumns instanceof QueryInterface) {
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            $insertNames = array_map([$this->quoter, 'quoteColumnName'], array_keys($insertColumns));
        }

        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);
        $uniqueNames = array_map([$this->quoter, 'quoteColumnName'], $uniqueNames);

        if ($updateColumns !== true) {
            return [$uniqueNames, $insertNames, null];
        }

        return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
    }

    /**
     * Quotes table names passed.
     *
     * @param array $tables
     * @param array $params
     *
     * @psalm-param array<array-key, array|QueryInterface|string> $tables
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return array
     */
    private function quoteTableNames(array $tables, array &$params): array
    {
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

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which did not cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string[] $columns source column list.
     * @param Constraint[] $constraints this parameter optionally receives a matched constraint list. The constraints
     * will be unique by their column names.
     *
     * @throws JsonException
     *
     * @return array column list.
     */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$constraints = []): array
    {
        $constraints = [];
        $primaryKey = $this->schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        foreach ($this->schema->getTableIndexes($name) as $constraint) {
            if ($constraint->isUnique()) {
                $constraints[] = $constraint;
            }
        }

        $constraints = array_merge($constraints, $this->schema->getTableUniques($name));

        /** Remove duplicates */
        $constraints = array_combine(
            array_map(
                static function (Constraint $constraint) {
                    $columns = $constraint->getColumnNames() ?? [];
                    $columns = is_array($columns) ? $columns : [$columns];
                    sort($columns, SORT_STRING);
                    return json_encode($columns, JSON_THROW_ON_ERROR);
                },
                $constraints
            ),
            $constraints
        );

        $columnNames = [];
        $quoter = $this->quoter;

        /** Remove all constraints which do not cover the specified column list */
        $constraints = array_values(
            array_filter(
                $constraints,
                static function (Constraint $constraint) use ($quoter, $columns, &$columnNames) {
                    $getColumnNames = $constraint->getColumnNames() ?? [];
                    $constraintColumnNames = array_map(
                        [$quoter, 'quoteColumnName'],
                        is_array($getColumnNames) ? $getColumnNames : [$getColumnNames]
                    );
                    $result = !array_diff($constraintColumnNames, $columns);

                    if ($result) {
                        $columnNames = array_merge($columnNames, $constraintColumnNames);
                    }

                    return $result;
                }
            )
        );

        return array_unique($columnNames);
    }
}
