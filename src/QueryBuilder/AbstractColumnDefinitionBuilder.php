<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function gettype;
use function in_array;
use function strtolower;

/**
 * Builds column definition from {@see ColumnSchemaInterface} object. Column definition is a string that represents
 * the column type and all constraints associated with the column. For example: `VARCHAR(128) NOT NULL DEFAULT 'foo'`.
 */
abstract class AbstractColumnDefinitionBuilder implements ColumnDefinitionBuilderInterface
{
    /**
     * @var string The keyword used to specify that a column is auto incremented.
     */
    protected const AUTO_INCREMENT_KEYWORD = '';

    /**
     * @var string[] The list of clauses that are used in column definition.
     */
    protected const CLAUSES = [
        'type',
        'unsigned',
        'not_null',
        'primary_key',
        'auto_increment',
        'unique',
        'default',
        'comment',
        'check',
        'references',
        'extra',
    ];

    /**
     * @var string The expression used to generate a UUID value.
     */
    protected const GENERATE_UUID_EXPRESSION = '';

    /**
     * @var string[] The list of database column types (in lower case) that allow size specification.
     */
    protected const TYPES_WITH_SIZE = [];

    /**
     * @var string[] The list of database column types (in lower case) that allow scale specification.
     */
    protected const TYPES_WITH_SCALE = [];

    /**
     * Get the database column type for the given column.
     *
     * @param ColumnSchemaInterface $column The column object.
     *
     * @return string The database column type.
     */
    abstract protected function getDbType(ColumnSchemaInterface $column): string;

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ColumnSchemaInterface $column): string
    {
        $result = '';

        foreach (static::CLAUSES as $clause) {
            $result .= match ($clause) {
                'auto_increment' => $this->buildAutoIncrement($column),
                'check' => $this->buildCheck($column),
                'comment' => $this->buildComment($column),
                'default' => $this->buildDefault($column),
                'extra' => $this->buildExtra($column),
                'not_null' => $this->buildNotNull($column),
                'primary_key' => $this->buildPrimaryKey($column),
                'references' => $this->buildReferences($column),
                'type' => $this->buildType($column),
                'unique' => $this->buildUnique($column),
                'unsigned' => $this->buildUnsigned($column),
                default => '',
            };
        }

        return $result;
    }

    /**
     * Builds the auto increment clause for column.
     *
     * @return string A string containing the {@see AUTO_INCREMENT_KEYWORD} keyword.
     */
    protected function buildAutoIncrement(ColumnSchemaInterface $column): string
    {
        if (empty(static::AUTO_INCREMENT_KEYWORD) || !$column->isAutoIncrement()) {
            return '';
        }

        return match ($column->getType()) {
            ColumnType::TINYINT,
            ColumnType::SMALLINT,
            ColumnType::INTEGER,
            ColumnType::BIGINT => ' ' . static::AUTO_INCREMENT_KEYWORD,
            default => '',
        };
    }

    /**
     * Builds the check constraint for the column.
     *
     * @return string A string containing the CHECK constraint.
     */
    protected function buildCheck(ColumnSchemaInterface $column): string
    {
        $check = $column->getCheck();

        return !empty($check) ? " CHECK ($check)" : '';
    }

    /**
     * Builds the comment clause for the column. Default is empty string.
     *
     * @return string A string containing the COMMENT keyword and the comment itself.
     */
    protected function buildComment(ColumnSchemaInterface $column): string
    {
        return '';
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string A string containing the DEFAULT keyword and the default value.
     */
    protected function buildDefault(ColumnSchemaInterface $column): string
    {
        if (!empty(static::GENERATE_UUID_EXPRESSION)
            && $column->getType() === ColumnType::UUID
            && $column->isAutoIncrement()
            && $column->getDefaultValue() === null
        ) {
            return ' DEFAULT ' . static::GENERATE_UUID_EXPRESSION;
        }

        if ($column->isAutoIncrement() && $column->getType() !== ColumnType::UUID
            || $column->getDefaultValue() === null
        ) {
            return '';
        }

        $defaultValue = $this->buildDefaultValue($column);

        if ($defaultValue === null) {
            return '';
        }

        return " DEFAULT $defaultValue";
    }

    /**
     * Return the default value for the column.
     *
     * @return string|null string with default value of column.
     */
    protected function buildDefaultValue(ColumnSchemaInterface $column): string|null
    {
        $value = $column->dbTypecast($column->getDefaultValue());

        if ($value === null) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value);
        }

        /** @var string */
        return match (gettype($value)) {
            GettypeResult::INTEGER => (string) $value,
            GettypeResult::DOUBLE => (string) $value,
            GettypeResult::BOOLEAN => $value ? 'TRUE' : 'FALSE',
            default => $this->queryBuilder->quoter()->quoteValue((string) $value),
        };
    }

    /**
     * Builds the custom string that's appended to column definition.
     *
     * @return string A string containing the custom SQL fragment appended to column definition.
     */
    protected function buildExtra(ColumnSchemaInterface $column): string
    {
        $extra = $column->getExtra();

        return !empty($extra) ? " $extra" : '';
    }

    /**
     * Builds the not null constraint for the column.
     *
     * @return string A string 'NOT NULL' if {@see ColumnSchemaInterface::isNotNull()} is `true` or an empty string
     * otherwise.
     */
    protected function buildNotNull(ColumnSchemaInterface $column): string
    {
        return $column->isNotNull() ? ' NOT NULL' : '';
    }

    /**
     * Builds the primary key clause for column.
     *
     * @return string A string containing the PRIMARY KEY keyword.
     */
    protected function buildPrimaryKey(ColumnSchemaInterface $column): string
    {
        return $column->isPrimaryKey() ? ' PRIMARY KEY' : '';
    }

    /**
     * Builds the references clause for the column.
     */
    protected function buildReferences(ColumnSchemaInterface $column): string
    {
        $reference = $this->buildReferenceDefinition($column);

        if ($reference === null) {
            return '';
        }

        return " REFERENCES $reference";
    }

    /**
     * Builds the reference definition for the column.
     */
    protected function buildReferenceDefinition(ColumnSchemaInterface $column): string|null
    {
        $reference = $column->getReference();
        $table = $reference?->getForeignTableName();

        if ($table === null) {
            return null;
        }

        $quoter = $this->queryBuilder->quoter();
        $schema = $reference?->getForeignSchemaName();

        if ($schema !== null) {
            $sql = $quoter->quoteTableName($schema) . '.' . $quoter->quoteTableName($table);
        } else {
            $sql = $quoter->quoteTableName($table);
        }

        $columns = $reference?->getForeignColumnNames();

        if (!empty($columns)) {
            $sql .= ' (' . $this->queryBuilder->buildColumns($columns) . ')';
        }

        if (null !== $onDelete = $reference?->getOnDelete()) {
            $sql .= ' ON DELETE ' . $onDelete;
        }

        if (null !== $onUpdate = $reference?->getOnUpdate()) {
            $sql .= ' ON UPDATE ' . $onUpdate;
        }

        return $sql;
    }

    /**
     * Builds the type definition for the column. For example: `varchar(128)` or `decimal(10,2)`.
     *
     * @return string A string containing the column type definition.
     */
    protected function buildType(ColumnSchemaInterface $column): string
    {
        $dbType = $column->getDbType();

        if ($dbType === null) {
            $dbType = $this->getDbType($column);
        }

        if (empty($dbType)
            || $dbType[-1] === ')'
            || !in_array(strtolower($dbType), static::TYPES_WITH_SIZE, true)
        ) {
            return $dbType;
        }

        $size = $column->getSize();

        if ($size === null) {
            return $dbType;
        }

        $scale = $column->getScale();

        if ($scale === null || !in_array(strtolower($dbType), static::TYPES_WITH_SCALE, true)) {
            return "$dbType($size)";
        }

        return "$dbType($size,$scale)";
    }

    /**
     * Builds the unique constraint for the column.
     *
     * @return string A string 'UNIQUE' if {@see isUnique} is true, otherwise it returns an empty string.
     */
    protected function buildUnique(ColumnSchemaInterface $column): string
    {
        if ($column->isPrimaryKey()) {
            return '';
        }

        return $column->isUnique() ? ' UNIQUE' : '';
    }

    /**
     * Builds the unsigned string for column. Default is empty string.
     *
     * @return string A string containing the UNSIGNED keyword.
     */
    protected function buildUnsigned(ColumnSchemaInterface $column): string
    {
        return $column->isUnsigned() ? ' UNSIGNED' : '';
    }
}
