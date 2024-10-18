<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;

use Yiisoft\Db\Constraint\ForeignKeyConstraint;

use function property_exists;

/**
 * Represents the metadata of a column in a database table.
 *
 * It provides information about the column's type, size, scale, and other details.
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
 * $column = (new IntegerColumnSchema())
 *     ->notNull()
 *     ->dbType('int')
 *     ->size(11)
 *     ->defaultValue(0)
 *     ->autoIncrement()
 *     ->primaryKey();
 * ```
 */
abstract class AbstractColumnSchema implements ColumnSchemaInterface
{
    /**
     * @var string The default column abstract type
     * @psalm-var ColumnType::*
     */
    protected const DEFAULT_TYPE = ColumnType::STRING;

    /**
     * @var string The column abstract type
     * @psalm-var ColumnType::*
     */
    private string $type;

    /**
     * @param string|null $type The column's abstract type.
     * @param bool $autoIncrement Whether the column is auto-incremental.
     * @param string|null $check The check constraint for the column.
     * @param string|null $comment The column's comment.
     * @param bool $computed Whether the column is a computed column.
     * @param string|null $dbType The column's database type.
     * @param mixed $defaultValue The default value of the column.
     * @param array|null $enumValues The list of possible values for an ENUM column.
     * @param string|null $extra Any extra information that needs to be appended to the column's definition.
     * @param bool $primaryKey Whether the column is a primary key.
     * @param string|null $name The column's name.
     * @param bool $notNull Whether the column is not nullable.
     * @param ForeignKeyConstraint|null $reference The foreign key constraint.
     * @param int|null $scale The number of digits to the right of the decimal point.
     * @param int|null $size The column's size.
     * @param bool $unique Whether the column is unique.
     * @param bool $unsigned Whether the column is unsigned.
     * @param mixed ...$args Additional arguments to be passed to the constructor.
     *
     * @psalm-param ColumnType::* $type
     * @psalm-param array<string, mixed> $args
     */
    public function __construct(
        string|null $type = null,
        private bool $autoIncrement = false,
        private string|null $check = null,
        private string|null $comment = null,
        private bool $computed = false,
        private string|null $dbType = null,
        private mixed $defaultValue = null,
        private array|null $enumValues = null,
        private string|null $extra = null,
        private bool $primaryKey = false,
        private string|null $name = null,
        private bool $notNull = false,
        private ForeignKeyConstraint|null $reference = null,
        private int|null $scale = null,
        private int|null $size = null,
        private bool $unique = false,
        private bool $unsigned = false,
        mixed ...$args,
    ) {
        $this->type = $type ?? static::DEFAULT_TYPE;

        /** @var array<string, mixed> $args */
        foreach ($args as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @deprecated Use {@see notNull()} instead. Will be removed in version 2.0.
     */
    public function allowNull(bool $allowNull = true): static
    {
        $this->notNull(!$allowNull);
        return $this;
    }

    public function autoIncrement(bool $autoIncrement = true): static
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function check(string|null $check): static
    {
        $this->check = $check;
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

    public function getCheck(): string|null
    {
        return $this->check;
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

    /**
     * @deprecated Will be removed in version 2.0.
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * @deprecated Use {@see getSize()} instead. Will be removed in version 2.0.
     */
    public function getPrecision(): int|null
    {
        return $this->getSize();
    }

    public function getPhpType(): string
    {
        return PhpType::MIXED;
    }

    public function getReference(): ForeignKeyConstraint|null
    {
        return $this->reference;
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

    /**
     * @deprecated Use {@see isNotNull()} instead. Will be removed in version 2.0.
     */
    public function isAllowNull(): bool
    {
        return !$this->isNotNull();
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isComputed(): bool
    {
        return $this->computed;
    }

    public function isNotNull(): bool
    {
        return $this->notNull;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @deprecated Will be removed in version 2.0.
     */
    public function name(string|null $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function notNull(bool $notNull = true): static
    {
        $this->notNull = $notNull;
        return $this;
    }

    /**
     * @deprecated Use {@see size()} instead. Will be removed in version 2.0.
     */
    public function precision(int|null $precision): static
    {
        return $this->size($precision);
    }

    public function primaryKey(bool $primaryKey = true): static
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function reference(ForeignKeyConstraint|null $reference): static
    {
        $this->reference = $reference;
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

    public function unique(bool $unique = true): static
    {
        $this->unique = $unique;
        return $this;
    }

    public function unsigned(bool $unsigned = true): static
    {
        $this->unsigned = $unsigned;
        return $this;
    }
}
