<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConditionInterface;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function count;
use function preg_match;
use function preg_replace;

/**
 * Builds a SELECT SQL statement based on the specification given as a {@see QueryInterface} object.
 *
 * SQL statements are created from {@see QueryInterface} objects using the
 * {@see AbstractDQLQueryBuilder::build()}-method.
 *
 * AbstractQueryBuilder is also used by {@see CommandInterface} to build SQL statements such as {@see insert()},
 * {@see update()}, {@see delete()} and {@see createTable()}.
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    public const PARAM_PREFIX = ':qp';
    /**
     * @psalm-var string[] The abstract column types mapped to physical column types.
     *
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications. Child
     * classes should override this property to declare supported type mappings.
     */
    protected array $typeMap = [];

    public function __construct(
        private QuoterInterface $quoter,
        private SchemaInterface $schema,
        private AbstractDDLQueryBuilder $ddlBuilder,
        private AbstractDMLQueryBuilder $dmlBuilder,
        private AbstractDQLQueryBuilder $dqlBuilder
    ) {
    }

    public function addCheck(string $table, string $name, string $expression): string
    {
        return $this->ddlBuilder->addCheck($table, $name, $expression);
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

    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        return $this->ddlBuilder->addDefaultValue($table, $name, $column, $value);
    }

    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        string $delete = null,
        string $update = null
    ): string {
        return $this->ddlBuilder->addForeignKey(
            $table,
            $name,
            $columns,
            $referenceTable,
            $referenceColumns,
            $delete,
            $update,
        );
    }

    public function addPrimaryKey(string $table, string $name, array|string $columns): string
    {
        return $this->ddlBuilder->addPrimaryKey($table, $name, $columns);
    }

    public function addUnique(string $table, string $name, array|string $columns): string
    {
        return $this->ddlBuilder->addUnique($table, $name, $columns);
    }

    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        return $this->ddlBuilder->alterColumn($table, $column, $type);
    }

    public function batchInsert(string $table, array $columns, iterable $rows, array &$params = []): string
    {
        return $this->dmlBuilder->batchInsert($table, $columns, $rows, $params);
    }

    public function bindParam(mixed $value, array &$params = []): string
    {
        $phName = self::PARAM_PREFIX . count($params);

        $additionalCount = 0;
        while (isset($params[$phName])) {
            $phName = self::PARAM_PREFIX . count($params) . '_' . $additionalCount;
            ++$additionalCount;
        }

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

    public function buildFrom(array|null $tables, array &$params): string
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

    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
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
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = []
    ): string {
        return $this->dqlBuilder->buildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    public function buildSelect(
        array $columns,
        array &$params,
        bool|null $distinct = false,
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

    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): string {
        return $this->ddlBuilder->createIndex($table, $name, $columns, $indexType, $indexMethod);
    }

    public function createTable(string $table, array $columns, string $options = null): string
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

    public function dropCheck(string $table, string $name): string
    {
        return $this->ddlBuilder->dropCheck($table, $name);
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

    public function dropDefaultValue(string $table, string $name): string
    {
        return $this->ddlBuilder->dropDefaultValue($table, $name);
    }

    public function dropForeignKey(string $table, string $name): string
    {
        return $this->ddlBuilder->dropForeignKey($table, $name);
    }

    public function dropIndex(string $table, string $name): string
    {
        return $this->ddlBuilder->dropIndex($table, $name);
    }

    public function dropPrimaryKey(string $table, string $name): string
    {
        return $this->ddlBuilder->dropPrimaryKey($table, $name);
    }

    public function dropTable(string $table): string
    {
        return $this->ddlBuilder->dropTable($table);
    }

    public function dropUnique(string $table, string $name): string
    {
        return $this->ddlBuilder->dropUnique($table, $name);
    }

    public function dropView(string $viewName): string
    {
        return $this->ddlBuilder->dropView($viewName);
    }

    public function getColumnType(ColumnInterface|string $type): string
    {
        if ($type instanceof ColumnInterface) {
            $type = $type->asString();
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

    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insertWithReturningPks($table, $columns, $params);
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

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        return $this->dmlBuilder->resetSequence($table, $value);
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
}
