<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Generator;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function count;
use function preg_match;
use function preg_replace;

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
     * Defines a UNIQUE index type for {@see createIndex()}.
     */
    public const INDEX_UNIQUE = 'UNIQUE';

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

    public function __construct(
        private QuoterInterface $quoter,
        private SchemaInterface $schema,
        private DDLQueryBuilder $ddlBuilder,
        private DMLQueryBuilder $dmlBuilder,
        private DQLQueryBuilder $dqlBuilder
    ) {
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

    public function alterColumn(string $table, string $column, ColumnSchemaBuilder|string $type): string
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
        return $this->dqlBuilder->build($query, $params);
    }

    public function buildColumns(array|string $columns): string
    {
        return $this->dqlBuilder->buildColumns($columns);
    }

    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string
    {
        return $this->dqlBuilder->buildCondition($condition, $params);
    }

    public function buildExpression(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->dqlBuilder->buildExpression($expression, $params);
    }

    public function buildFrom(?array $tables, array &$params): string
    {
        return $this->dqlBuilder->buildFrom($tables, $params);
    }

    public function buildGroupBy(array $columns, array &$params = []): string
    {
        return $this->dqlBuilder->buildGroupBy($columns, $params);
    }

    public function buildHaving(array|ExpressionInterface|string|null $condition, array &$params = []): string
    {
        return $this->dqlBuilder->buildHaving($condition, $params);
    }

    public function buildJoin(array $joins, array &$params): string
    {
        return $this->dqlBuilder->buildJoin($joins, $params);
    }

    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string
    {
        return $this->dqlBuilder->buildLimit($limit, $offset);
    }

    public function buildOrderBy(array $columns, array &$params = []): string
    {
        return $this->dqlBuilder->buildOrderBy($columns, $params);
    }

    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|int|null $limit,
        Expression|int|null $offset,
        array &$params = []
    ): string {
        return $this->dqlBuilder->buildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    public function buildSelect(
        array $columns,
        array &$params,
        ?bool $distinct = false,
        string $selectOption = null
    ): string {
        return $this->dqlBuilder->buildSelect($columns, $params, $distinct, $selectOption);
    }

    public function buildUnion(array $unions, array &$params): string
    {
        return $this->dqlBuilder->buildUnion($unions, $params);
    }

    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string {
        return $this->dqlBuilder->buildWhere($condition, $params);
    }

    public function buildWithQueries(array $withs, array &$params): string
    {
        return $this->dqlBuilder->buildWithQueries($withs, $params);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return $this->ddlBuilder->checkIntegrity($schema, $table, $check);
    }

    public function createConditionFromArray(array $condition): ConditionInterface
    {
        return $this->dqlBuilder->createConditionFromArray($condition);
    }

    public function createIndex(string $name, string $table, array|string $columns, ?string $indexType = null, ?string $indexMethod = null): string
    {
        return $this->ddlBuilder->createIndex($name, $table, $columns, $indexType, $indexMethod);
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

    public function getExpressionBuilder(ExpressionInterface $expression): object
    {
        return $this->dqlBuilder->getExpressionBuilder($expression);
    }

    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insert($table, $columns, $params);
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function insertEx(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insertEx($table, $columns, $params);
    }

    public function quoter(): QuoterInterface
    {
        return $this->quoter;
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

    public function schema(): SchemaInterface
    {
        return $this->schema;
    }

    public function selectExists(string $rawSql): string
    {
        return $this->dqlBuilder->selectExists($rawSql);
    }

    public function setConditionClasses(array $classes): void
    {
        $this->dqlBuilder->setConditionClasses($classes);
    }

    public function setExpressionBuilders(array $builders): void
    {
        $this->dqlBuilder->setExpressionBuilders($builders);
    }

    public function setSeparator(string $separator): void
    {
        $this->dqlBuilder->setSeparator($separator);
    }

    public function truncateTable(string $table): string
    {
        return $this->dmlBuilder->truncateTable($table);
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
}
