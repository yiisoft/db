<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;

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
        protected QuoterInterface $quoter,
    ) {
    }

    public function build(ColumnInterface $column): string
    {
        $result = '';

        foreach ($this->clauses as $clause) {
            $result .= match ($clause) {
                'type' => $this->buildType($column),
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
        return match (get_debug_type($value)) {
            'int' => (string) $value,
            'float' => DbStringHelper::normalizeFloat((string) $value),
            'bool' => $value ? 'TRUE' : 'FALSE',
            default => $this->quoter->quoteValue((string) $value),
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

        return !empty($check) ? " CHECK($check)" : '';
    }

    /**
     * Builds the unsigned string for column. Default is empty string.
     *
     * @return string A string containing the UNSIGNED keyword.
     */
    protected function buildUnsigned(ColumnInterface $column): string
    {
        return '';
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

    private function buildReferences(ColumnInterface $column): string
    {
        $reference = $this->buildReferenceDefinition($column);

        if ($reference === null) {
            return '';
        }

        return "REFERENCES $reference";
    }

    protected function buildReferenceDefinition(ColumnInterface $column): string|null
    {
        /** @var ForeignKeyConstraint|null $reference */
        $reference = $column->getReference();
        $table = $reference?->getForeignTableName();

        if ($table === null) {
            return null;
        }

        if (null !== $schema = $reference->getForeignSchemaName()) {
            $sql = $this->quoter->quoteTableName($schema) . '.' . $this->quoter->quoteTableName($table);
        } else {
            $sql = $this->quoter->quoteTableName($table);
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
}
