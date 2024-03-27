<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\SchemaInterface;

class ColumnBuilder implements ColumnBuilderInterface
{
    public static function pk(bool $autoIncrement = true): ColumnInterface
    {
        return (new IntegerColumn())
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function upk(bool $autoIncrement = true): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_INTEGER, ['unsigned' => true])
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function bigpk(bool $autoIncrement = true): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_BIGINT)
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function ubigpk(bool $autoIncrement = true): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_BIGINT, ['unsigned' => true])
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function uuidpk(bool $autoIncrement = false): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_UUID)
            ->primaryKey()
            ->autoIncrement($autoIncrement);
    }

    public static function uuidpkseq(): ColumnInterface
    {
        return static::uuidpk(true);
    }

    public static function string(int|null $size = 255): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_STRING)
            ->size($size);
    }

    public static function integer(int|null $size = null): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_INTEGER)
            ->size($size);
    }

    public static function float(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_FLOAT)
            ->size($size)
            ->scale($scale);
    }

    public static function double(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_DOUBLE)
            ->size($size)
            ->scale($scale);
    }

    public static function decimal(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_DECIMAL)
            ->size($size)
            ->scale($scale);
    }

    public static function money(int|null $size = null, int|null $scale = null): ColumnInterface
    {
        return (new ColumnFactory())
            ->fromType(SchemaInterface::TYPE_MONEY)
            ->size($size)
            ->scale($scale);
    }

    // ...
}
