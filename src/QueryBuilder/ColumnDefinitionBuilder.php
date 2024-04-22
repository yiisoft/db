<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;

/**
 * Builds column definition from {@see ColumnInterface} object. Column definition is a string that represents
 * the column type and all constraints associated with the column. For example: `VARCHAR(128) NOT NULL DEFAULT 'foo'`.
 *
 * You can use {@see ColumnDefinitionBuilder} class in the following way:
 * `(string) (new ColumnDefinitionBuilder($column));`
 */
class ColumnDefinitionBuilder implements ColumnDefinitionBuilderInterface
{
    protected array $clauses = [
        'type',
        'unsigned',
        'null',
        'primary_key',
        'auto_increment',
        'unique',
        'default',
        'comment',
        'check',
        'references',
        'extra',
    ];

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ColumnInterface $column): string
    {
        $result = '';

        foreach ($this->clauses as $clause) {
            $result .= match ($clause) {
                'type' => $this->buildType($column),
                'unsigned' => $this->buildUnsigned($column),
                'null' => $this->buildNull($column),
                'primary_key' => $this->buildPrimaryKey($column),
                'auto_increment' => $this->buildAutoIncrement($column),
                'unique' => $this->buildUnique($column),
                'default' => $this->buildDefault($column),
                'comment' => $this->buildComment($column),
                'check' => $this->buildCheck($column),
                'references' => $this->buildReferences($column),
                'extra' => $this->buildExtra($column),
                default => '',
            };
        }

        return $result;
    }

    protected function buildType(ColumnInterface $column): string
    {
        if ($column->getDbType() === null) {
            $column = clone $column;
            $column->dbType($this->getDbType($column->getType()));
        }

        return (string) $column->getFullDbType();
    }

    /**
     * Builds the null or not null constraint for the column.
     *
     * @return string A string 'NOT NULL' if {@see ColumnInterface::allowNull} is false,
     * 'NULL' if {@see ColumnInterface::allowNull} is true or an empty string otherwise.
     */
    protected function buildNull(ColumnInterface $column): string
    {
        if ($column->isPrimaryKey()) {
            return '';
        }

        return match ($column->isAllowNull()) {
            true => ' NULL',
            false => ' NOT NULL',
            default => '',
        };
    }

    /**
     * Builds the primary key clause for column.
     *
     * @return string A string containing the PRIMARY KEY keyword.
     */
    public function buildPrimaryKey(ColumnInterface $column): string
    {
        return $column->isPrimaryKey() ? ' PRIMARY KEY' : '';
    }

    /**
     * Builds the auto increment clause for column. Default is empty string.
     *
     * @return string A string containing the AUTOINCREMENT keyword.
     */
    public function buildAutoIncrement(ColumnInterface $column): string
    {
        return '';
    }

    /**
     * Builds the unique constraint for the column.
     *
     * @return string A string 'UNIQUE' if {@see isUnique} is true, otherwise it returns an empty string.
     */
    protected function buildUnique(ColumnInterface $column): string
    {
        if ($column->isPrimaryKey()) {
            return '';
        }

        return $column->isUnique() ? ' UNIQUE' : '';
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string A string containing the DEFAULT keyword and the default value.
     */
    protected function buildDefault(ColumnInterface $column): string
    {
        if ($column->isAutoIncrement()) {
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
    protected function buildDefaultValue(ColumnInterface $column): string|null
    {
        $value = $column->dbTypecast($column->getDefaultValue());

        if ($value === null) {
            return $column->isAllowNull() === true ? 'NULL' : null;
        }

        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value);
        }

        /** @var string */
        return match (gettype($value)) {
            'integer', 'double' => (string) $value,
            'boolean' => $value ? 'TRUE' : 'FALSE',
            default => $this->queryBuilder->quoter()->quoteValue((string) $value),
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
     * Builds the unsigned string for column. Default is empty string.
     *
     * @return string A string containing the UNSIGNED keyword.
     */
    protected function buildUnsigned(ColumnInterface $column): string
    {
        return $column->isUnsigned() ? ' UNSIGNED' : '';
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
     * Builds the comment clause for the column. Default is empty string.
     *
     * @return string A string containing the COMMENT keyword and the comment itself.
     */
    protected function buildComment(ColumnInterface $column): string
    {
        return '';
    }

    /**
     * Builds the references clause for the column.
     */
    private function buildReferences(ColumnInterface $column): string
    {
        $reference = $this->buildReferenceDefinition($column);

        if ($reference === null) {
            return '';
        }

        return "REFERENCES $reference";
    }

    /**
     * Builds the reference definition for the column.
     */
    protected function buildReferenceDefinition(ColumnInterface $column): string|null
    {
        /** @var ForeignKeyConstraint|null $reference */
        $reference = $column->getReference();
        $table = $reference?->getForeignTableName();

        if ($table === null) {
            return null;
        }

        $quoter = $this->queryBuilder->quoter();

        if (null !== $schema = $reference->getForeignSchemaName()) {
            $sql = $quoter->quoteTableName($schema) . '.' . $quoter->quoteTableName($table);
        } else {
            $sql = $quoter->quoteTableName($table);
        }

        $columns = $reference->getForeignColumnNames();

        if (!empty($columns)) {
            $sql .= ' (' . $this->queryBuilder->buildColumns($columns) . ')';
        }

        if (null !== $onDelete = $reference->getOnDelete()) {
            $sql .= ' ON DELETE ' . $onDelete;
        }

        if (null !== $onUpdate = $reference->getOnUpdate()) {
            $sql .= ' ON UPDATE ' . $onUpdate;
        }

        return $sql;
    }

    /**
     * Get the database column type from an abstract database type.
     *
     * @param string $type The abstract database type.
     *
     * @return string The database column type.
     */
    protected function getDbType(string $type): string
    {
        return match ($type) {
            SchemaInterface::TYPE_UUID => 'uuid',
            SchemaInterface::TYPE_CHAR => 'char',
            SchemaInterface::TYPE_STRING => 'varchar',
            SchemaInterface::TYPE_TEXT => 'text',
            SchemaInterface::TYPE_BINARY => 'binary',
            SchemaInterface::TYPE_BOOLEAN => 'boolean',
            SchemaInterface::TYPE_TINYINT => 'tinyint',
            SchemaInterface::TYPE_SMALLINT => 'smallint',
            SchemaInterface::TYPE_INTEGER => 'integer',
            SchemaInterface::TYPE_BIGINT => 'bigint',
            SchemaInterface::TYPE_FLOAT => 'float',
            SchemaInterface::TYPE_DOUBLE => 'double',
            SchemaInterface::TYPE_DECIMAL => 'decimal',
            SchemaInterface::TYPE_MONEY => 'money',
            SchemaInterface::TYPE_DATETIME => 'datetime',
            SchemaInterface::TYPE_TIMESTAMP => 'timestamp',
            SchemaInterface::TYPE_TIME => 'time',
            SchemaInterface::TYPE_DATE => 'date',
            SchemaInterface::TYPE_JSON => 'json',
            default => 'varchar',
        };
    }
}
