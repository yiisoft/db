<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConditionInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;

use function bin2hex;
use function count;
use function get_resource_type;
use function gettype;
use function is_string;
use function stream_get_contents;

/**
 * Builds a SELECT SQL statement based on the specification given as a {@see QueryInterface} object.
 *
 * SQL statements are created from {@see QueryInterface} objects using the
 * {@see AbstractDQLQueryBuilder::build()}-method.
 *
 * AbstractQueryBuilder is also used by {@see CommandInterface} to build SQL statements such as {@see insert()},
 * {@see update()}, {@see delete()} and {@see createTable()}.
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-import-type BatchValues from DMLQueryBuilderInterface
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    public const PARAM_PREFIX = ':qp';

    /**
     * @var string SQL value of the PHP `false` value.
     */
    protected const FALSE_VALUE = 'FALSE';
    /**
     * @var string SQL value of the PHP `true` value.
     */
    protected const TRUE_VALUE = 'TRUE';

    /**
     * @psalm-var string[] The abstract column types mapped to physical column types.
     *
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications. Child
     * classes should override this property to declare supported type mappings.
     */
    protected array $typeMap = [];

    public function __construct(
        private ConnectionInterface $db,
        private AbstractDDLQueryBuilder $ddlBuilder,
        private AbstractDMLQueryBuilder $dmlBuilder,
        private AbstractDQLQueryBuilder $dqlBuilder,
        private AbstractColumnDefinitionBuilder $columnDefinitionBuilder,
    ) {
    }

    public function addCheck(string $table, string $name, string $expression): string
    {
        return $this->ddlBuilder->addCheck($table, $name, $expression);
    }

    public function addColumn(string $table, string $column, ColumnInterface|string $type): string
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
        ?string $delete = null,
        ?string $update = null
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

    /**
     * @param string[] $columns
     *
     * @psalm-param BatchValues $rows
     * @psalm-param ParamsType $params
     *
     * @deprecated Use {@see insertBatch()} instead. It will be removed in version 3.0.0.
     */
    public function batchInsert(string $table, array $columns, iterable $rows): array
    {
        return $this->dmlBuilder->insertBatch($table, $rows, $columns);
    }

    public function insertBatch(string $table, iterable $rows, array $columns = [], int $rowsAtOnceLimit = 0): array
    {
        return $this->dmlBuilder->insertBatch($table, $rows, $columns, $rowsAtOnceLimit);
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

    public function buildColumnDefinition(ColumnInterface|string $column): string
    {
        if (is_string($column)) {
            $column = $this->db->getColumnFactory()->fromDefinition($column);
        }

        return $this->columnDefinitionBuilder->build($column);
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
        ?string $selectOption = null
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
        ?string $indexType = null,
        ?string $indexMethod = null
    ): string {
        return $this->ddlBuilder->createIndex($table, $name, $columns, $indexType, $indexMethod);
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

    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): string
    {
        return $this->ddlBuilder->dropTable($table, $ifExists, $cascade);
    }

    public function dropUnique(string $table, string $name): string
    {
        return $this->ddlBuilder->dropUnique($table, $name);
    }

    public function dropView(string $viewName): string
    {
        return $this->ddlBuilder->dropView($viewName);
    }

    public function getColumnDefinitionBuilder(): ColumnDefinitionBuilderInterface
    {
        return $this->columnDefinitionBuilder;
    }

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return $this->db->getColumnFactory();
    }

    public function getExpressionBuilder(ExpressionInterface $expression): object
    {
        return $this->dqlBuilder->getExpressionBuilder($expression);
    }

    public function getServerInfo(): ServerInfoInterface
    {
        return $this->db->getServerInfo();
    }

    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insert($table, $columns, $params);
    }

    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->dmlBuilder->insertWithReturningPks($table, $columns, $params);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->db->getQuoter();
    }

    public function prepareParam(ParamInterface $param): string
    {
        return match ($param->getType()) {
            DataType::BOOLEAN => $param->getValue() ? static::TRUE_VALUE : static::FALSE_VALUE,
            DataType::INTEGER => (string) (int) $param->getValue(),
            DataType::LOB => $this->prepareBinary((string) $param->getValue()),
            DataType::NULL => 'NULL',
            default => $this->prepareValue($param->getValue()),
        };
    }

    public function prepareValue(mixed $value): string
    {
        $quoter = $this->db->getQuoter();

        /** @psalm-suppress MixedArgument */
        return match (gettype($value)) {
            GettypeResult::BOOLEAN => $value ? static::TRUE_VALUE : static::FALSE_VALUE,
            GettypeResult::DOUBLE => (string) $value,
            GettypeResult::INTEGER => (string) $value,
            GettypeResult::NULL => 'NULL',
            GettypeResult::OBJECT => match (true) {
                $value instanceof Expression => (string) $value,
                $value instanceof ParamInterface => $this->prepareParam($value),
                default => $quoter->quoteValue((string) $value),
            },
            GettypeResult::RESOURCE => $this->prepareResource($value),
            GettypeResult::RESOURCE_CLOSED => throw new InvalidArgumentException('Resource is closed.'),
            default => $quoter->quoteValue((string) $value),
        };
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

    /**
     * Converts a resource value to its SQL representation or throws an exception if conversion is not possible.
     *
     * @param resource $value
     */
    protected function prepareResource(mixed $value): string
    {
        if (get_resource_type($value) !== 'stream') {
            throw new InvalidArgumentException('Supported only stream resource type.');
        }

        /** @var string $binary */
        $binary = stream_get_contents($value);

        return $this->prepareBinary($binary);
    }

    /**
     * Converts a binary value to its SQL representation using hexadecimal encoding.
     */
    protected function prepareBinary(string $binary): string
    {
        return '0x' . bin2hex($binary);
    }

    public function extractColumnNames(iterable $rows, array $columns): array
    {
        return $this->dmlBuilder->extractColumnNames($rows, $columns);
    }

    public function getParametersLimit(): int
    {
        return $this->db->getParametersLimit();
    }
}
