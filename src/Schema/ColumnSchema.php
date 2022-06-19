<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Strings\NumericHelper;

class ColumnSchema implements ColumnSchemaInterface
{
    private string $name = '';
    private bool $allowNull = false;
    private string $type = '';
    private ?string $phpType = null;
    private string $dbType = '';
    private mixed $defaultValue = null;
    private ?array $enumValues = null;
    private ?int $size = null;
    private ?int $precision = null;
    private ?int $scale = null;
    private bool $isPrimaryKey = false;
    private bool $autoIncrement = false;
    private bool $unsigned = false;
    private ?string $comment = null;
    private ?string $extra = null;

    public function phpTypecast(mixed $value): mixed
    {
        return $this->typecast($value);
    }

    public function dbTypecast(mixed $value): mixed
    {
        /**
         * the default implementation does the same as casting for PHP, but it should be possible to override this with
         * annotation of explicit PDO type.
         */
        return $this->typecast($value);
    }

    public function setType(string $value): void
    {
        $this->type = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAllowNull(): bool
    {
        return $this->allowNull;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPhpType(): ?string
    {
        return $this->phpType;
    }

    public function getDbType(): string
    {
        return $this->dbType;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function name(string $value): void
    {
        $this->name = $value;
    }

    public function allowNull(bool $value): void
    {
        $this->allowNull = $value;
    }

    public function type(string $value): void
    {
        $this->type = $value;
    }

    public function phpType(?string $value): void
    {
        $this->phpType = $value;
    }

    public function dbType(string $value): void
    {
        $this->dbType = $value;
    }

    public function defaultValue(mixed $value): void
    {
        $this->defaultValue = $value;
    }

    public function enumValues(?array $value): void
    {
        $this->enumValues = $value;
    }

    public function size(?int $value): void
    {
        $this->size = $value;
    }

    public function precision(?int $value): void
    {
        $this->precision = $value;
    }

    public function scale(?int $value): void
    {
        $this->scale = $value;
    }

    public function primaryKey(bool $value): void
    {
        $this->isPrimaryKey = $value;
    }

    public function autoIncrement(bool $value): void
    {
        $this->autoIncrement = $value;
    }

    public function unsigned(bool $value): void
    {
        $this->unsigned = $value;
    }

    public function comment(?string $value): void
    {
        $this->comment = $value;
    }

    public function extra(?string $value): void
    {
        $this->extra = $value;
    }

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value
     */
    protected function typecast(mixed $value): mixed
    {
        if (
            $value === ''
            && !in_array(
                $this->type,
                [
                    Schema::TYPE_TEXT,
                    Schema::TYPE_STRING,
                    Schema::TYPE_BINARY,
                    Schema::TYPE_CHAR,
                ],
                true
            )
        ) {
            return null;
        }

        if (
            $value === null
            || gettype($value) === $this->phpType
            || $value instanceof ExpressionInterface
        ) {
            return $value;
        }

        if (
            is_array($value)
            && count($value) === 2
            && isset($value[1])
            && in_array($value[1], $this->getPdoParamTypes(), true)
        ) {
            return new Param((string) $value[0], $value[1]);
        }

        switch ($this->phpType) {
            case Schema::PHP_TYPE_RESOURCE:
            case Schema::PHP_TYPE_STRING:
                if (is_resource($value)) {
                    return $value;
                }

                if (is_float($value)) {
                    /* ensure type cast always has . as decimal separator in all locales */
                    return NumericHelper::normalize((string) $value);
                }

                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }

                return (string) $value;
            case Schema::PHP_TYPE_INTEGER:
                return (int) $value;
            case Schema::PHP_TYPE_BOOLEAN:
                /**
                 * treating a 0 bit value as false too
                 * https://github.com/yiisoft/yii2/issues/9006
                 */
                return (bool) $value && $value !== "\0";
            case Schema::PHP_TYPE_DOUBLE:
                return (float) $value;
        }

        return $value;
    }

    /**
     * @return int[] array of numbers that represent possible PDO parameter types
     */
    private function getPdoParamTypes(): array
    {
        return [
            PDO::PARAM_BOOL,
            PDO::PARAM_INT,
            PDO::PARAM_STR,
            PDO::PARAM_LOB,
            PDO::PARAM_NULL,
            PDO::PARAM_STMT,
        ];
    }
}
