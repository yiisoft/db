<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * Represents the metadata of a column in a database table.
 *
 * It provides information about the column's name, type, size, precision, and other details.
 *
 * The `ColumnSchema` class is used to store and retrieve metadata about a column in a database table.
 *
 * It's typically used in conjunction with the TableSchema class, which represents the metadata of a database table as a
 * whole.
 *
 * Here is an example of how to use the `ColumnSchema` class:
 *
 * ```php
 * use Yiisoft\Db\Schema\ColumnSchema;
 *
 * $column = (new ColumnSchema())
 *     ->name('id')
 *     ->allowNull(false)
 *     ->dbType('int(11)')
 *     ->phpType('integer')
 *     ->type('integer')
 *     ->defaultValue(0)
 *     ->autoIncrement()
 *     ->primaryKey();
 * ```
 */
abstract class AbstractColumnSchema implements ColumnSchemaInterface
{
    private bool $allowNull = false;
    private bool $autoIncrement = false;
    private string|null $comment = null;
    private bool $computed = false;
    private string|null $dbType = null;
    private mixed $defaultValue = null;
    private array|null $enumValues = null;
    private string|null $extra = null;
    private bool $isPrimaryKey = false;
    private string|null $name = null;
    private int|null $precision = null;
    private int|null $scale = null;
    private int|null $size = null;
    private bool $unsigned = false;

    public function __construct(
        private string $type,
        private string|null $phpType = null,
    ) {
    }

    public function allowNull(bool $allowNull = true): static
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    public function autoIncrement(bool $autoIncrement = true): static
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function comment(string|null $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function computed(bool $computed = true): static
    {
        $this->computed = $computed;
        return $this;
    }

    public function dbType(string|null $dbType): static
    {
        $this->dbType = $dbType;
        return $this;
    }

    public function defaultValue(mixed $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function enumValues(array|null $enumValues): static
    {
        $this->enumValues = $enumValues;
        return $this;
    }

    public function extra(string|null $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function getComment(): string|null
    {
        return $this->comment;
    }

    public function getDbType(): string|null
    {
        return $this->dbType;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function getEnumValues(): array|null
    {
        return $this->enumValues;
    }

    public function getExtra(): string|null
    {
        return $this->extra;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getPrecision(): int|null
    {
        return $this->precision;
    }

    public function getPhpType(): string|null
    {
        return $this->phpType;
    }

    public function getScale(): int|null
    {
        return $this->scale;
    }

    public function getSize(): int|null
    {
        return $this->size;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isAllowNull(): bool
    {
        return $this->allowNull;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isComputed(): bool
    {
        return $this->computed;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function name(string|null $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function phpType(string|null $phpType): static
    {
        $this->phpType = $phpType;
        return $this;
    }

    public function precision(int|null $precision): static
    {
        $this->precision = $precision;
        return $this;
    }

    public function primaryKey(bool $isPrimaryKey = true): static
    {
        $this->isPrimaryKey = $isPrimaryKey;
        return $this;
    }

    public function scale(int|null $scale): static
    {
        $this->scale = $scale;
        return $this;
    }

    public function size(int|null $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function unsigned(bool $unsigned = true): static
    {
        $this->unsigned = $unsigned;
        return $this;
    }
}
