<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use PDO;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Query\Query;
use Yiisoft\Strings\NumericHelper;

/**
 * ColumnSchema class describes the metadata of a column in a database table.
 */
class ColumnSchema
{
    private string $name;
    private bool $allowNull;
    private string $type;
    private ?string $phpType = null;
    private string $dbType;
    private $defaultValue;
    private ?array $enumValues = null;
    private ?int $size = null;
    private ?int $precision = null;
    private ?int $scale = null;
    private bool $isPrimaryKey = false;
    private bool $autoIncrement = false;
    private bool $unsigned = false;
    private ?string $comment = null;

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value
     */
    public function phpTypecast($value)
    {
        return $this->typecast($value);
    }

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value. This may also be an array containing the value as the first element
     * and the PDO type as the second element.
     */
    public function dbTypecast($value)
    {
        /**
         * the default implementation does the same as casting for PHP, but it should be possible to override this with
         * annotation of explicit PDO type.
         */
        return $this->typecast($value);
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
    protected function typecast($value)
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
            return;
        }

        if (
            $value === null
            || gettype($value) === $this->phpType
            || $value instanceof ExpressionInterface
            || $value instanceof Query
        ) {
            return $value;
        }

        if (
            is_array($value)
            && count($value) === 2
            && isset($value[1])
            && in_array($value[1], $this->getPdoParamTypes(), true)
        ) {
            return new PdoValue($value[0], $value[1]);
        }

        switch ($this->phpType) {
            case 'resource':
            case 'string':
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
            case 'integer':
                return (int) $value;
            case 'boolean':
                /**
                 * treating a 0 bit value as false too
                 * https://github.com/yiisoft/yii2/issues/9006
                 */
                return (bool) $value && $value !== "\0";
            case 'double':
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

    public function setType(string $value): void
    {
        $this->type = $value;
    }

    /**
     * @return string name of this column (without quotes).
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool whether this column can be null.
     */
    public function isAllowNull(): bool
    {
        return $this->allowNull;
    }

    /**
     * @return string abstract type of this column. Possible abstract types include: char, string, text, boolean,
     * smallint, integer, bigint, float, decimal, datetime, timestamp, time, date, binary, and money.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string the PHP type of this column. Possible PHP types include: `string`, `boolean`, `integer`,
     * `double`, `array`.
     */
    public function getPhpType(): ?string
    {
        return $this->phpType;
    }

    /**
     * @return string the DB type of this column. Possible DB types vary according to the type of DBMS.
     */
    public function getDbType(): string
    {
        return $this->dbType;
    }

    /**
     * @return mixed default value of this column
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }

    /**
     * @return int display size of the column.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return int precision of the column data, if it is numeric.
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * @return int scale of the column data, if it is numeric.
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * @return bool whether this column is a primary key
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * @return bool whether this column is auto-incremental
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * @return bool whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @return string|null comment of this column. Not all DBMS support this.
     */
    public function getComment(): ?string
    {
        return $this->comment;
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

    public function defaultValue($value): void
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
}
