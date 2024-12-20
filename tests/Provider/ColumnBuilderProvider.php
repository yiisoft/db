<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ArrayColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BitColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\Column\StructuredColumnSchema;

class ColumnBuilderProvider
{
    public const DEFAULT_COLUMN_METHOD_RESULTS = [
        'getComment' => null,
        'getDbType' => null,
        'getDefaultValue' => null,
        'getEnumValues' => null,
        'getExtra' => null,
        'getScale' => null,
        'getSize' => null,
        'isAutoIncrement' => false,
        'isComputed' => false,
        'isNotNull' => null,
        'isPrimaryKey' => false,
        'isUnsigned' => false,
    ];

    public static function buildingMethods(): array
    {
        $column = ColumnBuilder::string();
        $columns = ['value' => ColumnBuilder::money(), 'currency_code' => ColumnBuilder::char(3)];

        return [
            // building method, args, expected instance of, expected type, expected column method results
            'primaryKey()' => ['primaryKey', [], IntegerColumnSchema::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'primaryKey(false)' => ['primaryKey', [false], IntegerColumnSchema::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'smallPrimaryKey()' => ['smallPrimaryKey', [], IntegerColumnSchema::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'smallPrimaryKey(false)' => ['smallPrimaryKey', [false], IntegerColumnSchema::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'bigPrimaryKey()' => ['bigPrimaryKey', [], IntegerColumnSchema::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'bigPrimaryKey(false)' => ['bigPrimaryKey', [false], IntegerColumnSchema::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'uuidPrimaryKey()' => ['uuidPrimaryKey', [], StringColumnSchema::class, ColumnType::UUID, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'uuidPrimaryKey(false)' => ['uuidPrimaryKey', [false], StringColumnSchema::class, ColumnType::UUID, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'boolean()' => ['boolean', [], BooleanColumnSchema::class, ColumnType::BOOLEAN],
            'bit()' => ['bit', [], BitColumnSchema::class, ColumnType::BIT],
            'bit(1)' => ['bit', [1], BitColumnSchema::class, ColumnType::BIT, ['getSize' => 1]],
            'tinyint()' => ['tinyint', [], IntegerColumnSchema::class, ColumnType::TINYINT],
            'tinyint(1)' => ['tinyint', [1], IntegerColumnSchema::class, ColumnType::TINYINT, ['getSize' => 1]],
            'smallint()' => ['smallint', [], IntegerColumnSchema::class, ColumnType::SMALLINT],
            'smallint(1)' => ['smallint', [1], IntegerColumnSchema::class, ColumnType::SMALLINT, ['getSize' => 1]],
            'integer()' => ['integer', [], IntegerColumnSchema::class, ColumnType::INTEGER],
            'integer(1)' => ['integer', [1], IntegerColumnSchema::class, ColumnType::INTEGER, ['getSize' => 1]],
            'bigint()' => ['bigint', [], IntegerColumnSchema::class, ColumnType::BIGINT],
            'bigint(1)' => ['bigint', [1], IntegerColumnSchema::class, ColumnType::BIGINT, ['getSize' => 1]],
            'float()' => ['float', [], DoubleColumnSchema::class, ColumnType::FLOAT],
            'float(8)' => ['float', [8], DoubleColumnSchema::class, ColumnType::FLOAT, ['getSize' => 8]],
            'float(8,2)' => ['float', [8, 2], DoubleColumnSchema::class, ColumnType::FLOAT, ['getSize' => 8, 'getScale' => 2]],
            'double()' => ['double', [], DoubleColumnSchema::class, ColumnType::DOUBLE],
            'double(8)' => ['double', [8], DoubleColumnSchema::class, ColumnType::DOUBLE, ['getSize' => 8]],
            'double(8,2)' => ['double', [8, 2], DoubleColumnSchema::class, ColumnType::DOUBLE, ['getSize' => 8, 'getScale' => 2]],
            'decimal()' => ['decimal', [], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 10, 'getScale' => 0]],
            'decimal(8)' => ['decimal', [8], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 0]],
            'decimal(8,2)' => ['decimal', [8, 2], DoubleColumnSchema::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 2]],
            'money()' => ['money', [], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 19, 'getScale' => 4]],
            'money(8)' => ['money', [8], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 4]],
            'money(8,2)' => ['money', [8, 2], DoubleColumnSchema::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 2]],
            'char()' => ['char', [], StringColumnSchema::class, ColumnType::CHAR, ['getSize' => 1]],
            'char(100)' => ['char', [100], StringColumnSchema::class, ColumnType::CHAR, ['getSize' => 100]],
            'string()' => ['string', [], StringColumnSchema::class, ColumnType::STRING, ['getSize' => 255]],
            'string(100)' => ['string', [100], StringColumnSchema::class, ColumnType::STRING, ['getSize' => 100]],
            'text()' => ['text', [], StringColumnSchema::class, ColumnType::TEXT],
            'text(5000)' => ['text', [5000], StringColumnSchema::class, ColumnType::TEXT, ['getSize' => 5000]],
            'binary()' => ['binary', [], BinaryColumnSchema::class, ColumnType::BINARY],
            'binary(8)' => ['binary', [8], BinaryColumnSchema::class, ColumnType::BINARY, ['getSize' => 8]],
            'uuid()' => ['uuid', [], StringColumnSchema::class, ColumnType::UUID],
            'datetime()' => ['datetime', [], StringColumnSchema::class, ColumnType::DATETIME, ['getSize' => 0]],
            'datetime(3)' => ['datetime', [3], StringColumnSchema::class, ColumnType::DATETIME, ['getSize' => 3]],
            'timestamp()' => ['timestamp', [], StringColumnSchema::class, ColumnType::TIMESTAMP, ['getSize' => 0]],
            'timestamp(3)' => ['timestamp', [3], StringColumnSchema::class, ColumnType::TIMESTAMP, ['getSize' => 3]],
            'date()' => ['date', [], StringColumnSchema::class, ColumnType::DATE],
            'time()' => ['time', [], StringColumnSchema::class, ColumnType::TIME, ['getSize' => 0]],
            'time(3)' => ['time', [3], StringColumnSchema::class, ColumnType::TIME, ['getSize' => 3]],
            'array()' => ['array', [], ArrayColumnSchema::class, ColumnType::ARRAY],
            'array($column)' => ['array', [$column], ArrayColumnSchema::class, ColumnType::ARRAY, ['getColumn' => $column]],
            'structured()' => ['structured', [], StructuredColumnSchema::class, ColumnType::STRUCTURED],
            "structured('money_currency')" => [
                'structured',
                ['money_currency'],
                StructuredColumnSchema::class,
                ColumnType::STRUCTURED,
                ['getDbType' => 'money_currency'],
            ],
            "structured('money_currency',\$columns)" => [
                'structured',
                ['money_currency', $columns],
                StructuredColumnSchema::class,
                ColumnType::STRUCTURED,
                ['getDbType' => 'money_currency', 'getColumns' => $columns],
            ],
            'json()' => ['json', [], JsonColumnSchema::class, ColumnType::JSON],
        ];
    }
}
