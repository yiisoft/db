<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;

/**
 * Column builder for database {@see ColumnSchemaInterface} instances.
 *
 * @psalm-import-type ColumnInfo from ColumnSchemaInterface
 */
class ColumnBuilder
{
    // Pseudo-type column builders
    /**
     * Builds a column as an `integer` primary key.
     */
    public static function primaryKey(bool $autoIncrement = true): ColumnSchemaInterface
    {
        return static::integer()
            ->primaryKey()
            ->autoIncrement($autoIncrement)
            ->allowNull(false);
    }

    /**
     * Builds a column as a `smallint` primary key.
     */
    public static function smallPrimaryKey(bool $autoIncrement = true): ColumnSchemaInterface
    {
        return static::smallint()
            ->primaryKey()
            ->autoIncrement($autoIncrement)
            ->allowNull(false);
    }

    /**
     * Builds a column as a `bigint` primary key.
     */
    public static function bigPrimaryKey(bool $autoIncrement = true): ColumnSchemaInterface
    {
        return static::bigint()
            ->primaryKey()
            ->autoIncrement($autoIncrement)
            ->allowNull(false);
    }

    /**
     * Builds a column as an `uuid` primary key.
     */
    public static function uuidPrimaryKey(bool $autoIncrement = false): ColumnSchemaInterface
    {
        return static::uuid()
            ->primaryKey()
            ->autoIncrement($autoIncrement)
            ->allowNull(false);
    }

    // Abstract type column builders
    /**
     * Builds a column with the abstract type `boolean`.
     */
    public static function boolean(): ColumnSchemaInterface
    {
        return (new BooleanColumnSchema(ColumnType::BOOLEAN));
    }

    /**
     * Builds a column with the abstract type `bit`.
     */
    public static function bit(int|null $size = null): ColumnSchemaInterface
    {
        return (new BitColumnSchema(ColumnType::BIT))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `tinyint`.
     */
    public static function tinyint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::TINYINT))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `smallint`.
     */
    public static function smallint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::SMALLINT))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `integer`.
     */
    public static function integer(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::INTEGER))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `bigint`.
     */
    public static function bigint(int|null $size = null): ColumnSchemaInterface
    {
        return (new IntegerColumnSchema(ColumnType::BIGINT))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `float`.
     */
    public static function float(int|null $size = null, int|null $scale = null): ColumnSchemaInterface
    {
        return (new DoubleColumnSchema(ColumnType::FLOAT))
            ->size($size)
            ->scale($scale);
    }

    /**
     * Builds a column with the abstract type `double`.
     */
    public static function double(int|null $size = null, int|null $scale = null): ColumnSchemaInterface
    {
        return (new DoubleColumnSchema(ColumnType::DOUBLE))
            ->size($size)
            ->scale($scale);
    }

    /**
     * Builds a column with the abstract type `decimal`.
     */
    public static function decimal(int|null $size = 10, int|null $scale = 0): ColumnSchemaInterface
    {
        return (new DoubleColumnSchema(ColumnType::DECIMAL))
            ->size($size)
            ->scale($scale);
    }

    /**
     * Builds a column with the abstract type `money`.
     */
    public static function money(int|null $size = 19, int|null $scale = 4): ColumnSchemaInterface
    {
        return (new DoubleColumnSchema(ColumnType::MONEY))
            ->size($size)
            ->scale($scale);
    }

    /**
     * Builds a column with the abstract type `char`.
     */
    public static function char(int|null $size = 1): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::CHAR))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `string`.
     */
    public static function string(int|null $size = 255): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::STRING))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `text`.
     */
    public static function text(int|null $size = null): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::TEXT))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `binary`.
     */
    public static function binary(int|null $size = null): ColumnSchemaInterface
    {
        return (new BinaryColumnSchema(ColumnType::BINARY))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `uuid`.
     */
    public static function uuid(): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::UUID));
    }

    /**
     * Builds a column with the abstract type `datetime`.
     */
    public static function datetime(int|null $size = 0): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::DATETIME))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `timestamp`.
     */
    public static function timestamp(int|null $size = 0): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::TIMESTAMP))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `date`.
     */
    public static function date(): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::DATE));
    }

    /**
     * Builds a column with the abstract type `time`.
     */
    public static function time(int|null $size = 0): ColumnSchemaInterface
    {
        return (new StringColumnSchema(ColumnType::TIME))
            ->size($size);
    }

    /**
     * Builds a column with the abstract type `json`.
     */
    public static function json(): ColumnSchemaInterface
    {
        return (new JsonColumnSchema(ColumnType::JSON));
    }
}
