<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\InvalidArgumentException;

use function array_keys;

/**
 * TableSchema represents the metadata of a database table.
 *
 * @property array $columnNames List of column names. This property is read-only.
 */
abstract class TableSchema
{
    private ?string $schemaName = null;
    private string $name = '';
    private ?string $fullName = null;
    private ?string $sequenceName = null;
    private array $primaryKey = [];
    private array $columns = [];

    /**
     * Gets the named column metadata.
     *
     * This is a convenient method for retrieving a named column even if it does not exist.
     *
     * @param string $name column name
     *
     * @return ColumnSchema|null metadata of the named column. Null if the named column does not exist.
     */
    public function getColumn(string $name): ?ColumnSchema
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * Returns the names of all columns in this table.
     *
     * @return array list of column names
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Manually specifies the primary key for this table.
     *
     * @param array|string $keys the primary key (can be composite)
     *
     * @throws InvalidArgumentException if the specified key cannot be found in the table.
     */
    public function fixPrimaryKey($keys): void
    {
        $keys = (array) $keys;
        $this->primaryKey = $keys;

        foreach ($this->columns as $column) {
            $column->primaryKey(false);
        }

        foreach ($keys as $key) {
            if (isset($this->columns[$key])) {
                $this->columns[$key]->primaryKey(true);
            } else {
                throw new InvalidArgumentException("Primary key '$key' cannot be found in table '$this->name'.");
            }
        }
    }

    /**
     * @return string|null the name of the schema that this table belongs to.
     */
    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    /**
     * @return string the name of this table. The schema name is not included. Use {@see fullName} to get the name with
     * schema name prefix.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null the full name of this table, which includes the schema name prefix, if any. Note that if the
     * schema name is the same as the {@see Schema::defaultSchema|default schema name}, the schema name will not be
     * included.
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @return string|null sequence name for the primary key. Null if no sequence.
     */
    public function getSequenceName(): ?string
    {
        return $this->sequenceName;
    }

    /**
     * @return array primary keys of this table.
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * @return ColumnSchema[] column metadata of this table. Each array element is a {@see ColumnSchema} object, indexed
     * by column names.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function schemaName(?string $value): void
    {
        $this->schemaName = $value;
    }

    public function name(string $value): void
    {
        $this->name = $value;
    }

    public function fullName(?string $value): void
    {
        $this->fullName = $value;
    }

    public function sequenceName(?string $value): void
    {
        $this->sequenceName = $value;
    }

    public function primaryKey(string $value): void
    {
        $this->primaryKey[] = $value;
    }

    public function columns(string $index, ColumnSchema $value): void
    {
        $this->columns[$index] = $value;
    }
}
