<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

interface ColumnBuilderInterface
{
    public static function pk(bool $autoIncrement = true): ColumnInterface;

    public static function upk(bool $autoIncrement = true): ColumnInterface;

    public static function bigpk(bool $autoIncrement = true): ColumnInterface;

    public static function ubigpk(bool $autoIncrement = true): ColumnInterface;

    public static function uuidpk(bool $autoIncrement = false): ColumnInterface;

    public static function uuidpkseq(): ColumnInterface;

    public static function string(int|null $size = null): ColumnInterface;

    public static function integer(int|null $size = null): ColumnInterface;

    public static function float(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function double(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function decimal(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function money(int|null $size = null, int|null $scale = null): ColumnInterface;
}
