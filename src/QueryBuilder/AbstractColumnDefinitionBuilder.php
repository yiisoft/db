<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function in_array;
use function strtolower;

/**
 * Builds column definition from {@see ColumnInterface} object. Column definition is a string that represents
 * the column type and all constraints associated with the column. For example: `VARCHAR(128) NOT NULL DEFAULT 'foo'`.
 */
abstract class AbstractColumnDefinitionBuilder implements ColumnDefinitionBuilderInterface
{
    /**
     * @var string The keyword used to specify that a column is auto incremented.
     */
    protected const AUTO_INCREMENT_KEYWORD = '';

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
     * @param ColumnInterface $column The column object.
     *
     * @return string The database column type.
     */
    abstract protected function getDbType(ColumnInterface $column): string;

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ColumnInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildUnsigned($column)
            . $this->buildNotNull($column)
            . $this->buildPrimaryKey($column)
            . $this->buildAutoIncrement($column)
            . $this->buildUnique($column)
            . $this->buildDefault($column)
            . $this->buildComment($column)
            . $this->buildCheck($column)
            . $this->buildReferences($column)
            . $this->buildExtra($column);
    }

    public function buildAlter(ColumnInterface $column): string
    {
        return $this->build($column);
    }

    /**
     * Builds the auto increment clause for the column.
     *
     * @return string A string containing the {@see AUTO_INCREMENT_KEYWORD} keyword.
     */
    protected function buildAutoIncrement(ColumnInterface $column): string
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
    protected function buildCheck(ColumnInterface $column): string
    {
        $check = $column->getCheck();

        return !empty($check) ? " CHECK ($check)" : '';
    }

    /**
     * Builds the comment clause for the column. Default is empty string.
     *
     * @return string A string containing the COMMENT keyword and the comment itself.
     */
    protected function buildComment(ColumnInterface $column): string
    {
        return '';
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string A string containing the DEFAULT keyword and the default value.
     */
    protected function buildDefault(ColumnInterface $column): string
    {
        $uuidExpression = $this->getDefaultUuidExpression();

        if (!empty($uuidExpression)
            && $column->getType() === ColumnType::UUID
            && $column->isAutoIncrement()
            && $column->getDefaultValue() === null
        ) {
            return " DEFAULT $uuidExpression";
        }

        if ($column->isAutoIncrement() && $column->getType() !== ColumnType::UUID
            || !$column->hasDefaultValue()
        ) {
            return '';
        }

        $defaultValue = $column->dbTypecast($column->getDefaultValue());
        $defaultValue = $this->queryBuilder->prepareValue($defaultValue);

        if ($defaultValue === '') {
            return '';
        }

        return " DEFAULT $defaultValue";
    }

    /**
     * Builds the custom string that's appended to column definition.
     *
     * @return string A string containing the custom SQL fragment appended to column definition.
     */
    protected function buildExtra(ColumnInterface $column): string
    {
        $extra = $column->getExtra();

        return !empty($extra) ? " $extra" : '';
    }

    /**
     * Builds the not null constraint for the column.
     *
     * @return string A string 'NOT NULL' if {@see ColumnInterface::isNotNull()} is `true`
     * or an empty string otherwise.
     */
    protected function buildNotNull(ColumnInterface $column): string
    {
        return match ($column->isNotNull()) {
            true => ' NOT NULL',
            false => ' NULL',
            default => '',
        };
    }

    /**
     * Builds the primary key clause for column.
     *
     * @return string A string containing the PRIMARY KEY keyword.
     */
    protected function buildPrimaryKey(ColumnInterface $column): string
    {
        return $column->isPrimaryKey() ? ' PRIMARY KEY' : '';
    }

    /**
     * Builds the references clause for the column.
     */
    protected function buildReferences(ColumnInterface $column): string
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
    protected function buildReferenceDefinition(ColumnInterface $column): string|null
    {
        $reference = $column->getReference();
        $table = $reference?->getForeignTableName();

        if ($table === null) {
            return null;
        }

        $quoter = $this->queryBuilder->quoter();
        $schema = $reference?->getForeignSchemaName();

        $sql = $quoter->quoteTableName($table);

        if ($schema !== null) {
            $sql = $quoter->quoteTableName($schema) . '.' . $sql;
        }

        $columns = $reference?->getForeignColumnNames();

        if (!empty($columns)) {
            $sql .= ' (' . $this->queryBuilder->buildColumns($columns) . ')';
        }

        if (null !== $onDelete = $reference?->getOnDelete()) {
            $sql .= $this->buildOnDelete($onDelete);
        }

        if (null !== $onUpdate = $reference?->getOnUpdate()) {
            $sql .= $this->buildOnUpdate($onUpdate);
        }

        return $sql;
    }

    /**
     * Builds the ON DELETE clause for the column reference.
     */
    protected function buildOnDelete(string $onDelete): string
    {
        return " ON DELETE $onDelete";
    }

    /**
     * Builds the ON UPDATE clause for the column reference.
     */
    protected function buildOnUpdate(string $onUpdate): string
    {
        return " ON UPDATE $onUpdate";
    }

    /**
     * Builds the type definition for the column. For example: `varchar(128)` or `decimal(10,2)`.
     *
     * @return string A string containing the column type definition.
     */
    protected function buildType(ColumnInterface $column): string
    {
        $dbType = $this->getDbType($column);

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
     * @return string A string 'UNIQUE' if {@see ColumnInterface::isUnique()} is true
     * or an empty string otherwise.
     */
    protected function buildUnique(ColumnInterface $column): string
    {
        if ($column->isPrimaryKey()) {
            return '';
        }

        return $column->isUnique() ? ' UNIQUE' : '';
    }

    /**
     * Builds the unsigned string for the column.
     *
     * @return string A string containing the UNSIGNED keyword.
     */
    protected function buildUnsigned(ColumnInterface $column): string
    {
        return $column->isUnsigned() ? ' UNSIGNED' : '';
    }

    /**
     * Get the expression used to generate a UUID as a default value.
     */
    protected function getDefaultUuidExpression(): string
    {
        return '';
    }
}
