<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_key_exists;

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
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('int(11)');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement();
 * $column->primaryKey();
 * ```
 */
abstract class Column implements ColumnInterface
{
    private bool|null $allowNull = null;
    private bool $autoIncrement = false;
    private string|ExpressionInterface|null $check = null;
    private string|null $comment = null;
    private bool $computed = false;
    private string|null $dbType = null;
    private mixed $defaultValue = null;
    private string|null $extra = null;
    private bool $primaryKey = false;
    private ForeignKeyConstraint|null $reference = null;
    private int|null $scale = null;
    private int|null $size = null;
    private bool $unique = false;
    private bool $unsigned = false;
    private array $values = [];

    public function __construct(
        private string|null $type = null,
        private string|null $phpType = null,
    ) {
    }

    public function allowNull(bool|null $value = true): static
    {
        $this->allowNull = $value;
        return $this;
    }

    public function autoIncrement(bool $value = true): static
    {
        $this->autoIncrement = $value;
        return $this;
    }

    public function comment(string $value = null): static
    {
        $this->comment = $value;
        return $this;
    }

    public function computed(bool $value = true): static
    {
        $this->computed = $value;
        return $this;
    }

    public function dbType(string $value = null): static
    {
        $this->dbType = $value;
        return $this;
    }

    public function dbTypecast(mixed $value): mixed
    {
        return $value;
    }

    public function defaultValue(mixed $value = null): static
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function extra(string $value = null): static
    {
        $this->extra = $value;
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

    public function getExtra(): string|null
    {
        return $this->extra;
    }

    public function getFullDbType(): string|null
    {
        if ($this->dbType === null) {
            return null;
        }

        if ($this->size === null) {
            return $this->dbType;
        }

        return "$this->dbType($this->size)";
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

    public function getValues(): array
    {
        return $this->values;
    }

    public function isAllowNull(): bool|null
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
        return $this->primaryKey;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function load(array $info): static
    {
        foreach ($info as $key => $value) {
            match ($key) {
                'allow_null' => $this->allowNull($value !== null ? (bool) $value : null),
                'auto_increment' => $this->autoIncrement((bool) $value),
                'comment' => $this->comment($value !== null ? (string) $value : null),
                'computed' => $this->computed((bool) $value),
                'db_type' => $this->dbType($value !== null ? (string) $value : null),
                'default_value' => $this->defaultValue($value),
                'extra' => $this->extra($value !== null ? (string) $value : null),
                'primary_key' => $this->primaryKey((bool) $value),
                'php_type' => $this->phpType($value !== null ? (string) $value : null),
                'scale' => $this->scale($value !== null ? (int) $value : null),
                'size' => $this->size($value !== null ? (int) $value : null),
                'type' => $this->type($value !== null ? (string) $value : null),
                'unsigned' => $this->unsigned((bool) $value),
                'values' => $this->values(is_array($value) ? $value : null),
                default => null,
            };
        }

        if (array_key_exists('default_value_raw', $info)) {
            $this->defaultValue($this->normalizeDefaultValue($info['default_value_raw']));
        }

        return $this;
    }

    public function normalizeDefaultValue(string|null $value): mixed
    {
        if ($value === null || $this->computed || preg_match("/^\(?NULL\b/i", $value) === 1) {
            return null;
        }

        if (preg_match("/^'(.*)'|^\(([^()]*)\)/s", $value, $matches) === 1) {
            return $this->phpTypecast($matches[2] ?? str_replace("''", "'", $matches[1]));
        }

        return new Expression($value);
    }

    public function phpType(string $value = null): static
    {
        $this->phpType = $value;
        return $this;
    }

    public function phpTypecast(mixed $value): mixed
    {
        return $value;
    }

    public function primaryKey(bool $value = true): static
    {
        $this->primaryKey = $value;
        return $this;
    }

    public function scale(int $value = null): static
    {
        $this->scale = $value;
        return $this;
    }

    public function size(int $value = null): static
    {
        $this->size = $value;
        return $this;
    }

    public function type(string $value = null): static
    {
        $this->type = $value;
        return $this;
    }

    public function unsigned(bool $value = true): static
    {
        $this->unsigned = $value;
        return $this;
    }

    public function values(array $value = []): static
    {
        $this->values = $value;
        return $this;
    }
}
