<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

class ColumnFactoryProvider
{
    public static function definitions(): array
    {
        return [
            // definition, expected type, expected instance of, expected column method results
            '' => ['', ColumnType::STRING, StringColumnSchema::class],
            'text' => ['text', ColumnType::TEXT, StringColumnSchema::class],
            'text NOT NULL' => ['text NOT NULL', ColumnType::TEXT, StringColumnSchema::class],
            'char(1)' => ['char(1)', ColumnType::CHAR, StringColumnSchema::class],
            'decimal(10,2)' => ['decimal(10,2)', ColumnType::DECIMAL, DoubleColumnSchema::class],
            'bigint UNSIGNED' => ['bigint UNSIGNED', ColumnType::BIGINT, BigIntColumnSchema::class],
        ];
    }

    public static function types(): array
    {
        return [
            // type, expected type, expected instance of
            'uuid' => [ColumnType::UUID, ColumnType::UUID, StringColumnSchema::class],
            'char' => [ColumnType::CHAR, ColumnType::CHAR, StringColumnSchema::class],
            'string' => [ColumnType::STRING, ColumnType::STRING, StringColumnSchema::class],
            'text' => [ColumnType::TEXT, ColumnType::TEXT, StringColumnSchema::class],
            'binary' => [ColumnType::BINARY, ColumnType::BINARY, BinaryColumnSchema::class],
            'boolean' => [ColumnType::BOOLEAN, ColumnType::BOOLEAN, BooleanColumnSchema::class],
            'tinyint' => [ColumnType::TINYINT, ColumnType::TINYINT, IntegerColumnSchema::class],
            'smallint' => [ColumnType::SMALLINT, ColumnType::SMALLINT, IntegerColumnSchema::class],
            'integer' => [ColumnType::INTEGER, ColumnType::INTEGER, IntegerColumnSchema::class],
            'bigint' => [ColumnType::BIGINT, ColumnType::BIGINT, IntegerColumnSchema::class],
            'float' => [ColumnType::FLOAT, ColumnType::FLOAT, DoubleColumnSchema::class],
            'double' => [ColumnType::DOUBLE, ColumnType::DOUBLE, DoubleColumnSchema::class],
            'decimal' => [ColumnType::DECIMAL, ColumnType::DECIMAL, DoubleColumnSchema::class],
            'money' => [ColumnType::MONEY, ColumnType::MONEY, StringColumnSchema::class],
            'datetime' => [ColumnType::DATETIME, ColumnType::DATETIME, StringColumnSchema::class],
            'timestamp' => [ColumnType::TIMESTAMP, ColumnType::TIMESTAMP, StringColumnSchema::class],
            'time' => [ColumnType::TIME, ColumnType::TIME, StringColumnSchema::class],
            'date' => [ColumnType::DATE, ColumnType::DATE, StringColumnSchema::class],
            'json' => [ColumnType::JSON, ColumnType::JSON, JsonColumnSchema::class],
        ];
    }
}
