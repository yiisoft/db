<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

interface ColumnBuilderInterface
{
    // Primary key column builders
    public static function pk(bool $autoIncrement = true): ColumnInterface;

    public static function upk(bool $autoIncrement = true): ColumnInterface;

    public static function bigpk(bool $autoIncrement = true): ColumnInterface;

    public static function ubigpk(bool $autoIncrement = true): ColumnInterface;

    public static function uuidpk(bool $autoIncrement = false): ColumnInterface;

    public static function uuidpkseq(): ColumnInterface;

    // Abstract type column builders
    public static function uuid(): ColumnInterface;

    public static function char(int|null $size = 1): ColumnInterface;

    public static function string(int|null $size = 255): ColumnInterface;

    public static function text(): ColumnInterface;

    public static function binary(int|null $size = null): ColumnInterface;

    public static function boolean(): ColumnInterface;

    public static function tinyint(int|null $size = null): ColumnInterface;

    public static function smallint(int|null $size = null): ColumnInterface;

    public static function integer(int|null $size = null): ColumnInterface;

    public static function bigint(int|null $size = null): ColumnInterface;

    public static function float(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function double(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function decimal(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function money(int|null $size = null, int|null $scale = null): ColumnInterface;

    public static function datetime(int|null $size = null): ColumnInterface;

    public static function timestamp(int|null $size = null): ColumnInterface;

    public static function time(int|null $size = null): ColumnInterface;

    public static function date(): ColumnInterface;

    public static function json(): ColumnInterface;
}
