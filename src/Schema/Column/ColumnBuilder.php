<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;

/**
 * Column builder for database {@see ColumnInterface} instances.
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
class ColumnBuilder
{
    // Pseudo-type column builders
    /**
     * Builds a column as an `integer` primary key.
     */
    public static function primaryKey(bool $autoIncrement = true): ColumnInterface
    {
        return static::integer()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    /**
     * Builds a column as a `smallint` primary key.
     */
    public static function smallPrimaryKey(bool $autoIncrement = true): ColumnInterface
    {
        return static::smallint()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    /**
     * Builds a column as a `bigint` primary key.
     */
    public static function bigPrimaryKey(bool $autoIncrement = true): ColumnInterface
    {
        return static::bigint()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    /**
     * Builds a column as an `uuid` primary key.
     */
    public static function uuidPrimaryKey(bool $autoIncrement = true): ColumnInterface
    {
        return static::uuid()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    // Abstract type column builders
    /**
     * Builds a column with the abstract type `boolean`.
     */
    public static function boolean(): ColumnInterface
    {
        return new BooleanColumn(ColumnType::BOOLEAN);
    }

    /**
     * Builds a column with the abstract type `bit`.
     *
     * @param int|null $size The number of bits that the column can store.
     */
    public static function bit(int|null $size = null): ColumnInterface
    {
        return new BitColumn(ColumnType::BIT, size: $size);
    }

    /**
     * Builds a column with the abstract type `tinyint`.
     */
    public static function tinyint(int|null $size = null): ColumnInterface
    {
        return new IntegerColumn(ColumnType::TINYINT, size: $size);
    }

    /**
     * Builds a column with the abstract type `smallint`.
     */
    public static function smallint(int|null $size = null): ColumnInterface
    {
        return new IntegerColumn(ColumnType::SMALLINT, size: $size);
    }

    /**
     * Builds a column with the abstract type `integer`.
     */
    public static function integer(int|null $size = null): ColumnInterface
    {
        return new IntegerColumn(ColumnType::INTEGER, size: $size);
    }

    /**
     * Builds a column with the abstract type `bigint`.
     */
    public static function bigint(int|null $size = null): ColumnInterface
    {
        return new IntegerColumn(ColumnType::BIGINT, size: $size);
    }

    /**
     * Builds a column with the abstract type `float`.
     */
    public static function float(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return new DoubleColumn(ColumnType::FLOAT, scale: $scale, size: $size);
    }

    /**
     * Builds a column with the abstract type `double`.
     */
    public static function double(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return new DoubleColumn(ColumnType::DOUBLE, scale: $scale, size: $size);
    }

    /**
     * Builds a column with the abstract type `decimal`.
     */
    public static function decimal(int|null $size = 10, int|null $scale = 0): ColumnInterface
    {
        return new DoubleColumn(ColumnType::DECIMAL, scale: $scale, size: $size);
    }

    /**
     * Builds a column with the abstract type `money`.
     */
    public static function money(int|null $size = 19, int|null $scale = 4): ColumnInterface
    {
        return new DoubleColumn(ColumnType::MONEY, scale: $scale, size: $size);
    }

    /**
     * Builds a column with the abstract type `char`.
     */
    public static function char(int|null $size = 1): ColumnInterface
    {
        return new StringColumn(ColumnType::CHAR, size: $size);
    }

    /**
     * Builds a column with the abstract type `string`.
     */
    public static function string(int|null $size = 255): ColumnInterface
    {
        return new StringColumn(ColumnType::STRING, size: $size);
    }

    /**
     * Builds a column with the abstract type `text`.
     *
     * @param int|null $size The maximum length of the column or `null` if it is not limited.
     *
     * MySQL creates the column as the smallest `TEXT` type large enough to hold values of `$size` characters.
     * This corresponds to `TINYTEXT`, `MEDIUMTEXT`, `TEXT`, and `LONGTEXT` column types and depends on the character
     * set used.
     *
     * For example, the maximum sizes in different character sets are as follows:
     * | Column type | latin1        | utf8          | utf8mb4
     * |-------------|---------------|---------------|----------------
     * | TINYTEXT    | 255           | 85            | 63
     * | TEXT        | 65,535        | 21,845        | 16,383
     * | MEDIUMTEXT  | 16,777,215    | 5,592,405     | 4,194,303
     * | LONGTEXT    | 4,294,967,295 | 4,294,967,295 | 4,294,967,295
     */
    public static function text(int|null $size = null): ColumnInterface
    {
        return new StringColumn(ColumnType::TEXT, size: $size);
    }

    /**
     * Builds a column with the abstract type `binary`.
     */
    public static function binary(int|null $size = null): ColumnInterface
    {
        return new BinaryColumn(ColumnType::BINARY, size: $size);
    }

    /**
     * Builds a column with the abstract type `uuid`.
     */
    public static function uuid(): ColumnInterface
    {
        return new StringColumn(ColumnType::UUID);
    }

    /**
     * Builds a column with the abstract type `datetime`.
     */
    public static function datetime(int|null $size = 0): ColumnInterface
    {
        return new StringColumn(ColumnType::DATETIME, size: $size);
    }

    /**
     * Builds a column with the abstract type `timestamp`.
     */
    public static function timestamp(int|null $size = 0): ColumnInterface
    {
        return new StringColumn(ColumnType::TIMESTAMP, size: $size);
    }

    /**
     * Builds a column with the abstract type `date`.
     */
    public static function date(): ColumnInterface
    {
        return new StringColumn(ColumnType::DATE);
    }

    /**
     * Builds a column with the abstract type `time`.
     */
    public static function time(int|null $size = 0): ColumnInterface
    {
        return new StringColumn(ColumnType::TIME, size: $size);
    }

    /**
     * Builds a column with the abstract type `array`.
     *
     * @param ColumnInterface|null $column The instance of {@see ColumnInterface} of the array elements.
     */
    public static function array(ColumnInterface|null $column = null): ColumnInterface
    {
        return new ArrayColumn(ColumnType::ARRAY, column: $column);
    }

    /**
     * Builds a column with the abstract type `structured`.
     *
     * @param string|null $dbType The DB type of the column.
     * @param ColumnInterface[] $columns The columns (name -> instance) that the structured column should contain.
     *
     * @psalm-param array<string, ColumnInterface> $columns
     */
    public static function structured(string|null $dbType = null, array $columns = []): ColumnInterface
    {
        return new StructuredColumn(ColumnType::STRUCTURED, dbType: $dbType, columns: $columns);
    }

    /**
     * Builds a column with the abstract type `json`.
     */
    public static function json(): ColumnInterface
    {
        return new JsonColumn(ColumnType::JSON);
    }
}
