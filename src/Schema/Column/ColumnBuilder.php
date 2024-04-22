<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\SchemaInterface;

class ColumnBuilder implements ColumnBuilderInterface
{
    // Primary key column builders
    public static function pk(bool $autoIncrement = true): ColumnInterface
    {
        return static::integer()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function upk(bool $autoIncrement = true): ColumnInterface
    {
        return static::pk()->unsigned();
    }

    public static function bigpk(bool $autoIncrement = true): ColumnInterface
    {
        return static::bigint()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function ubigpk(bool $autoIncrement = true): ColumnInterface
    {
        return static::bigpk()->unsigned();
    }

    public static function uuidpk(bool $autoIncrement = false): ColumnInterface
    {
        return static::uuid()
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function uuidpkseq(): ColumnInterface
    {
        return static::uuidpk(true);
    }

    // Abstract type column builders
    public static function uuid(): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_UUID);
    }

    public static function char(int|null $size = 1): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_CHAR)
            ->size($size);
    }

    public static function string(int|null $size = 255): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_STRING)
            ->size($size);
    }

    public static function text(): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_TEXT);
    }

    public static function binary(int|null $size = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_BINARY)
            ->size($size);
    }

    public static function boolean(): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_BOOLEAN);
    }

    public static function tinyint(int|null $size = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_TINYINT)
            ->size($size);
    }

    public static function smallint(int|null $size = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_SMALLINT)
            ->size($size);
    }

    public static function integer(int|null $size = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_INTEGER)
            ->size($size);
    }

    public static function bigint(int|null $size = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_BIGINT)
            ->size($size);
    }

    public static function float(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_FLOAT)
            ->size($size)
            ->scale($scale);
    }

    public static function double(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_DOUBLE)
            ->size($size)
            ->scale($scale);
    }

    public static function decimal(int|null $size = 10, int|null $scale = 0): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_DECIMAL)
            ->size($size)
            ->scale($scale);
    }

    public static function money(int|null $size = 19, int|null $scale = 4): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_MONEY)
            ->size($size)
            ->scale($scale);
    }

    public static function datetime(int|null $size = 0): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_DATETIME)
            ->size($size);
    }

    public static function timestamp(int|null $size = 0): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_TIMESTAMP)
            ->size($size);
    }

    public static function time(int|null $size = 0): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_TIME)
            ->size($size);
    }

    public static function date(): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_DATE);
    }

    public static function json(): ColumnInterface
    {
        return static::columnFactory()
            ->fromType(SchemaInterface::TYPE_JSON);
    }

    protected static function columnFactory(): ColumnFactory
    {
        return new ColumnFactory();
    }
}
