<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use InvalidArgumentException;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Constraint\ForeignKey;

use function array_key_exists;
use function property_exists;

/**
 * Represents the metadata of a column in a database table.
 *
 * It provides information about the column's type, size, scale, and other details.
 *
 * The `Column` class is used to store and retrieve metadata about a column in a database table.
 *
 * It's typically used in conjunction with the TableSchema class, which represents the metadata of a database table as a
 * whole.
 *
 * Here is an example of how to use the `Column` class:
 *
 * ```php
 * use Yiisoft\Db\Schema\IntegerColumn;
 *
 * $column = (new IntegerColumn())
 *     ->notNull()
 *     ->dbType('int')
 *     ->size(11)
 *     ->defaultValue(0)
 *     ->autoIncrement()
 *     ->primaryKey();
 * ```
 */
abstract class AbstractColumn implements ColumnInterface
{
    /**
     * @var string The default column abstract type
     * @psalm-var ColumnType::*
     */
    protected const DEFAULT_TYPE = ColumnType::STRING;

    /**
     * @var mixed $defaultValue The default value of the column.
     */
    private mixed $defaultValue;

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
     * @param array|null $enumValues The list of possible values for an ENUM column.
     * @param string|null $extra Any extra information that needs to be appended to the column's definition.
     * @param bool $primaryKey Whether the column is a primary key.
     * @param string|null $name The column's name.
     * @param bool|null $notNull Whether the column is not nullable.
     * @param ForeignKey|null $reference The foreign key constraint.
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
        private array|null $enumValues = null,
        private string|null $extra = null,
        private bool $primaryKey = false,
        private string|null $name = null,
        private bool|null $notNull = null,
        private ForeignKey|null $reference = null,
        private int|null $scale = null,
        private int|null $size = null,
        private bool $unique = false,
        private bool $unsigned = false,
        mixed ...$args,
    ) {
        $this->type = $type ?? static::DEFAULT_TYPE;

        if (array_key_exists('defaultValue', $args)) {
            $this->defaultValue = $args['defaultValue'];
            unset($args['defaultValue']);
        }

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

    /** @psalm-mutation-free */
    public function getCheck(): string|null
    {
        return $this->check;
    }

    /** @psalm-mutation-free */
    public function getComment(): string|null
    {
        return $this->comment;
    }

    /** @psalm-mutation-free */
    public function getDbType(): string|null
    {
        return $this->dbType;
    }

    /** @psalm-mutation-free */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue ?? null;
    }

    /** @psalm-mutation-free */
    public function getEnumValues(): array|null
    {
        return $this->enumValues;
    }

    /** @psalm-mutation-free */
    public function getExtra(): string|null
    {
        return $this->extra;
    }

    /** @psalm-mutation-free */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * @deprecated Use {@see getSize()} instead. Will be removed in version 2.0.
     * @psalm-mutation-free
     */
    public function getPrecision(): int|null
    {
        return $this->getSize();
    }

    /** @psalm-mutation-free */
    public function getPhpType(): string
    {
        return PhpType::MIXED;
    }

    /** @psalm-mutation-free */
    public function getReference(): ForeignKey|null
    {
        return $this->reference;
    }

    /** @psalm-mutation-free */
    public function getScale(): int|null
    {
        return $this->scale;
    }

    /** @psalm-mutation-free */
    public function getSize(): int|null
    {
        return $this->size;
    }

    /** @psalm-mutation-free */
    public function getType(): string
    {
        return $this->type;
    }

    /** @psalm-mutation-free */
    public function hasDefaultValue(): bool
    {
        return property_exists($this, 'defaultValue');
    }

    /**
     * @deprecated Use {@see isNotNull()} instead. Will be removed in version 2.0.
     * @psalm-mutation-free
     */
    public function isAllowNull(): bool
    {
        return !$this->isNotNull();
    }

    /** @psalm-mutation-free */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /** @psalm-mutation-free */
    public function isComputed(): bool
    {
        return $this->computed;
    }

    /** @psalm-mutation-free */
    public function isNotNull(): bool|null
    {
        return $this->notNull;
    }

    /** @psalm-mutation-free */
    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    /** @psalm-mutation-free */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /** @psalm-mutation-free */
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

    public function notNull(bool|null $notNull = true): static
    {
        $this->notNull = $notNull;
        return $this;
    }

    public function null(): static
    {
        $this->notNull = false;
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

    public function reference(ForeignKey|null $reference): static
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

    public function withName(string|null $name): static
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    protected function throwWrongTypeException(string $type): never
    {
        throw new InvalidArgumentException("Wrong $type value for $this->type column.");
    }
}
