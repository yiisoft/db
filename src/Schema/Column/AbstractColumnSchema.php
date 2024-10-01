<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;

use Yiisoft\Db\Constraint\ForeignKeyConstraint;

use function is_array;

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
    private bool $autoIncrement = false;
    private string|null $check = null;
    private string|null $comment = null;
    private bool $computed = false;
    private string|null $dbType = null;
    private mixed $defaultValue = null;
    private array|null $enumValues = null;
    private string|null $extra = null;
    private bool $isPrimaryKey = false;
    private string|null $name = null;
    private bool $notNull = false;
    private ForeignKeyConstraint|null $reference = null;
    private int|null $scale = null;
    private int|null $size = null;
    private bool $unique = false;
    private bool $unsigned = false;

    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        private string $type,
    ) {
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
        return $this->isPrimaryKey;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function load(array $info): static
    {
        foreach ($info as $key => $value) {
            /**
             * @psalm-suppress PossiblyInvalidCast
             * @psalm-suppress InvalidCast
             * @psalm-suppress DeprecatedMethod
             */
            match ($key) {
                'allow_null' => $this->allowNull((bool) $value),
                'auto_increment' => $this->autoIncrement((bool) $value),
                'check' => $this->check($value !== null ? (string) $value : null),
                'comment' => $this->comment($value !== null ? (string) $value : null),
                'computed' => $this->computed((bool) $value),
                'db_type' => $this->dbType($value !== null ? (string) $value : null),
                'default_value' => $this->defaultValue($value),
                'enum_values' => $this->enumValues(is_array($value) ? $value : null),
                'extra' => $this->extra($value !== null ? (string) $value : null),
                'name' => $this->name($value !== null ? (string) $value : null),
                'not_null' => $this->notNull((bool) $value),
                'primary_key' => $this->primaryKey((bool) $value),
                'precision' => $this->precision($value !== null ? (int) $value : null),
                'reference' => $this->reference($value instanceof ForeignKeyConstraint ? $value : null),
                'scale' => $this->scale($value !== null ? (int) $value : null),
                'size' => $this->size($value !== null ? (int) $value : null),
                'unique' => $this->unique((bool) $value),
                'unsigned' => $this->unsigned((bool) $value),
                default => null,
            };
        }

        return $this;
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

    public function primaryKey(bool $isPrimaryKey = true): static
    {
        $this->isPrimaryKey = $isPrimaryKey;
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
