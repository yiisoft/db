<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * ColumnSchema class describes the metadata of a column in a database table.
 */
interface ColumnSchemaInterface
{
    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value
     */
    public function phpTypecast(mixed $value): mixed;

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
    public function dbTypecast(mixed $value): mixed;

    public function setType(string $value): void;

    /**
     * @return string name of this column (without quotes).
     */
    public function getName(): string;

    /**
     * @return bool whether this column can be null.
     */
    public function isAllowNull(): bool;

    /**
     * @return string abstract type of this column. Possible abstract types include: char, string, text, boolean,
     * smallint, integer, bigint, float, decimal, datetime, timestamp, time, date, binary, and money.
     */
    public function getType(): string;

    /**
     * @return string|null the PHP type of this column. Possible PHP types include: `string`, `boolean`, `integer`,
     * `double`, `array`.
     */
    public function getPhpType(): ?string;

    /**
     * @return string the DB type of this column. Possible DB types vary according to the type of DBMS.
     */
    public function getDbType(): string;

    /**
     * @return mixed default value of this column
     */
    public function getDefaultValue(): mixed;

    /**
     * @return array|null enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public function getEnumValues(): ?array;

    /**
     * @return int|null display size of the column.
     */
    public function getSize(): ?int;

    /**
     * @return int|null precision of the column data, if it is numeric.
     */
    public function getPrecision(): ?int;

    /**
     * @return int|null scale of the column data, if it is numeric.
     */
    public function getScale(): ?int;

    /**
     * @return bool whether this column is a primary key
     */
    public function isPrimaryKey(): bool;

    /**
     * @return bool whether this column is auto-incremental
     */
    public function isAutoIncrement(): bool;

    /**
     * @return bool whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     */
    public function isUnsigned(): bool;

    /**
     * @return string|null comment of this column. Not all DBMS support this.
     */
    public function getComment(): ?string;

    /**
     * @return string|null extra of this column. Not all DBMS support this.
     */
    public function getExtra(): ?string;

    public function name(string $value): void;

    public function allowNull(bool $value): void;

    public function type(string $value): void;

    public function phpType(?string $value): void;

    public function dbType(string $value): void;

    public function defaultValue(mixed $value): void;

    public function enumValues(?array $value): void;

    public function size(?int $value): void;

    public function precision(?int $value): void;

    public function scale(?int $value): void;

    public function primaryKey(bool $value): void;

    public function autoIncrement(bool $value): void;

    public function unsigned(bool $value): void;

    public function comment(?string $value): void;

    public function extra(?string $value): void;
}
