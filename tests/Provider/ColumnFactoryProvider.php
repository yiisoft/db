<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

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
            '' => ['', 'string', StringColumnSchema::class],
            'text' => ['text', 'text', StringColumnSchema::class],
            'text NOT NULL' => ['text NOT NULL', 'text', StringColumnSchema::class, ['getExtra' => 'NOT NULL']],
            'char(1)' => ['char(1)', 'char', StringColumnSchema::class, ['getSize' => 1]],
            'decimal(10,2)' => ['decimal(10,2)', 'decimal', DoubleColumnSchema::class, ['getPrecision' => 10, 'getScale' => 2]],
            'bigint UNSIGNED' => ['bigint UNSIGNED', 'bigint', BigIntColumnSchema::class, ['isUnsigned' => true]],
        ];
    }

    public static function types(): array
    {
        return [
            'uuid' => ['uuid', 'uuid', StringColumnSchema::class],
            'char' => ['char', 'char', StringColumnSchema::class],
            'string' => ['string', 'string', StringColumnSchema::class],
            'text' => ['text', 'text', StringColumnSchema::class],
            'binary' => ['binary', 'binary', BinaryColumnSchema::class],
            'boolean' => ['boolean', 'boolean', BooleanColumnSchema::class],
            'tinyint' => ['tinyint', 'tinyint', IntegerColumnSchema::class],
            'smallint' => ['smallint', 'smallint', IntegerColumnSchema::class],
            'integer' => ['integer', 'integer', IntegerColumnSchema::class],
            'bigint' => ['bigint', 'bigint', IntegerColumnSchema::class],
            'float' => ['float', 'float', DoubleColumnSchema::class],
            'double' => ['double', 'double', DoubleColumnSchema::class],
            'decimal' => ['decimal', 'decimal', DoubleColumnSchema::class],
            'money' => ['money', 'money', StringColumnSchema::class],
            'datetime' => ['datetime', 'datetime', StringColumnSchema::class],
            'timestamp' => ['timestamp', 'timestamp', StringColumnSchema::class],
            'time' => ['time', 'time', StringColumnSchema::class],
            'date' => ['date', 'date', StringColumnSchema::class],
            'json' => ['json', 'json', JsonColumnSchema::class],
        ];
    }
}
