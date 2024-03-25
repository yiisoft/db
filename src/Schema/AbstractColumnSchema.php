<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;

use function gettype;
use function in_array;
use function is_bool;
use function is_float;
use function is_resource;

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
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ``
 */
abstract class AbstractColumnSchema implements ColumnSchemaInterface
{
    private bool $allowNull = false;
    private bool $autoIncrement = false;
    private string|null $comment = null;
    private bool $computed = false;
    private string|null $dateTimeFormat = null;
    private string|null $dbType = null;
    private mixed $defaultValue = null;
    private array|null $enumValues = null;
    private string|null $extra = null;
    private bool $isPrimaryKey = false;
    private string|null $phpType = null;
    private int|null $precision = null;
    private int|null $scale = null;
    private int|null $size = null;
    private string $type = '';
    private bool $unsigned = false;

    public function __construct(private string $name)
    {
    }

    public function allowNull(bool $value): void
    {
        $this->allowNull = $value;
    }

    public function autoIncrement(bool $value): void
    {
        $this->autoIncrement = $value;
    }

    public function comment(string|null $value): void
    {
        $this->comment = $value;
    }

    public function computed(bool $value): void
    {
        $this->computed = $value;
    }

    public function dateTimeFormat(string|null $value): void
    {
        $this->dateTimeFormat = $value;
    }

    public function dbType(string|null $value): void
    {
        $this->dbType = $value;
    }

    public function dbTypecast(mixed $value): mixed
    {
        /**
         * The default implementation does the same as casting for PHP, but it should be possible to override this with
         * annotation of an explicit PDO type.
         */

        if ($this->dateTimeFormat !== null) {
            if (empty($value) || $value instanceof Expression) {
                return $value;
            }

            if (!$this->hasTimezone() && $this->type !== SchemaInterface::TYPE_DATE) {
                // if data type does not have timezone DB stores datetime without timezone
                // convert datetime to UTC to avoid timezone issues
                if (!$value instanceof DateTimeImmutable) {
                    // make a copy of $value if change timezone
                    if ($value instanceof DateTimeInterface) {
                        $value = DateTimeImmutable::createFromInterface($value);
                    } elseif (is_string($value)) {
                        $value = date_create_immutable($value) ?: $value;
                    }
                }

                if ($value instanceof DateTimeImmutable) { // DateTimeInterface does not have the method setTimezone()
                    $value = $value->setTimezone(new DateTimeZone('UTC'));
                    // Known possible issues:
                    // MySQL converts `TIMESTAMP` values from the current time zone to UTC for storage, and back from UTC to the current time zone when retrieve data.
                    // Oracle `TIMESTAMP WITH LOCAL TIME ZONE` data stored in the database is normalized to the database time zone. And returns it in the users' local session time zone.
                    // Both of them do not store time zone offset and require to convert DateTime to local DB timezone instead of UTC before insert.
                    // To solve the issue it requires to set local DB timezone to UTC if the types are in use
                }
            }

            if ($value instanceof DateTimeInterface) {
                return $value->format($this->dateTimeFormat);
            }

            return (string) $value;
        }

        return $this->typecast($value);
    }

    public function defaultValue(mixed $value): void
    {
        $this->defaultValue = $value;
    }

    public function enumValues(array|null $value): void
    {
        $this->enumValues = $value;
    }

    public function extra(string|null $value): void
    {
        $this->extra = $value;
    }

    public function getComment(): string|null
    {
        return $this->comment;
    }

    public function getDateTimeFormat(): string|null
    {
        return $this->dateTimeFormat;
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

    public function getName(): string
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

    public function hasTimezone(): bool
    {
        return false;
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

    public function phpType(string|null $value): void
    {
        $this->phpType = $value;
    }

    /**
     * @throws \Exception
     */
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value) && $this->dateTimeFormat !== null) {
            if (!$this->hasTimezone()) {
                // if data type does not have timezone datetime was converted to UTC before insert
                $datetime = new DateTimeImmutable($value, new DateTimeZone('UTC'));

                // convert datetime to PHP timezone
                return $datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
            }

            return new DateTimeImmutable($value);
        }

        return $this->typecast($value);
    }

    public function precision(int|null $value): void
    {
        $this->precision = $value;
    }

    public function primaryKey(bool $value): void
    {
        $this->isPrimaryKey = $value;
    }

    public function scale(int|null $value): void
    {
        $this->scale = $value;
    }

    public function size(int|null $value): void
    {
        $this->size = $value;
    }

    public function type(string $value): void
    {
        $this->type = $value;
    }

    public function unsigned(bool $value): void
    {
        $this->unsigned = $value;
    }

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it won't be converted.
     *
     * @param mixed $value The value to be converted.
     *
     * @return mixed The converted value.
     */
    protected function typecast(mixed $value): mixed
    {
        if (
            $value === null
            || $value === '' && !in_array($this->type, [
                SchemaInterface::TYPE_TEXT,
                SchemaInterface::TYPE_STRING,
                SchemaInterface::TYPE_BINARY,
                SchemaInterface::TYPE_CHAR,
            ], true)
        ) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match ($this->phpType) {
            gettype($value) => $value,
            SchemaInterface::PHP_TYPE_RESOURCE,
            SchemaInterface::PHP_TYPE_STRING
                => match (true) {
                    is_resource($value) => $value,
                    /** ensure type cast always has . as decimal separator in all locales */
                    is_float($value) => DbStringHelper::normalizeFloat($value),
                    is_bool($value) => $value ? '1' : '0',
                    default => (string) $value,
                },
            SchemaInterface::PHP_TYPE_INTEGER => (int) $value,
            /** Treating a 0-bit value as false too (@link https://github.com/yiisoft/yii2/issues/9006) */
            SchemaInterface::PHP_TYPE_BOOLEAN => $value && $value !== "\0",
            SchemaInterface::PHP_TYPE_DOUBLE => (float) $value,
            default => $value,
        };
    }
}
