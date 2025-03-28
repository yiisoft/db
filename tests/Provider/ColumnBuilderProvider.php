<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;

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
            'primaryKey()' => ['primaryKey', [], IntegerColumn::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'primaryKey(false)' => ['primaryKey', [false], IntegerColumn::class, ColumnType::INTEGER, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'smallPrimaryKey()' => ['smallPrimaryKey', [], IntegerColumn::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'smallPrimaryKey(false)' => ['smallPrimaryKey', [false], IntegerColumn::class, ColumnType::SMALLINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'bigPrimaryKey()' => ['bigPrimaryKey', [], IntegerColumn::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'bigPrimaryKey(false)' => ['bigPrimaryKey', [false], IntegerColumn::class, ColumnType::BIGINT, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'uuidPrimaryKey()' => ['uuidPrimaryKey', [], StringColumn::class, ColumnType::UUID, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'uuidPrimaryKey(false)' => ['uuidPrimaryKey', [false], StringColumn::class, ColumnType::UUID, ['isPrimaryKey' => true, 'isAutoIncrement' => false]],
            'boolean()' => ['boolean', [], BooleanColumn::class, ColumnType::BOOLEAN],
            'bit()' => ['bit', [], BitColumn::class, ColumnType::BIT],
            'bit(1)' => ['bit', [1], BitColumn::class, ColumnType::BIT, ['getSize' => 1]],
            'tinyint()' => ['tinyint', [], IntegerColumn::class, ColumnType::TINYINT],
            'tinyint(1)' => ['tinyint', [1], IntegerColumn::class, ColumnType::TINYINT, ['getSize' => 1]],
            'smallint()' => ['smallint', [], IntegerColumn::class, ColumnType::SMALLINT],
            'smallint(1)' => ['smallint', [1], IntegerColumn::class, ColumnType::SMALLINT, ['getSize' => 1]],
            'integer()' => ['integer', [], IntegerColumn::class, ColumnType::INTEGER],
            'integer(1)' => ['integer', [1], IntegerColumn::class, ColumnType::INTEGER, ['getSize' => 1]],
            'bigint()' => ['bigint', [], IntegerColumn::class, ColumnType::BIGINT],
            'bigint(1)' => ['bigint', [1], IntegerColumn::class, ColumnType::BIGINT, ['getSize' => 1]],
            'float()' => ['float', [], DoubleColumn::class, ColumnType::FLOAT],
            'float(8)' => ['float', [8], DoubleColumn::class, ColumnType::FLOAT, ['getSize' => 8]],
            'float(8,2)' => ['float', [8, 2], DoubleColumn::class, ColumnType::FLOAT, ['getSize' => 8, 'getScale' => 2]],
            'double()' => ['double', [], DoubleColumn::class, ColumnType::DOUBLE],
            'double(8)' => ['double', [8], DoubleColumn::class, ColumnType::DOUBLE, ['getSize' => 8]],
            'double(8,2)' => ['double', [8, 2], DoubleColumn::class, ColumnType::DOUBLE, ['getSize' => 8, 'getScale' => 2]],
            'decimal()' => ['decimal', [], DoubleColumn::class, ColumnType::DECIMAL, ['getSize' => 10, 'getScale' => 0]],
            'decimal(8)' => ['decimal', [8], DoubleColumn::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 0]],
            'decimal(8,2)' => ['decimal', [8, 2], DoubleColumn::class, ColumnType::DECIMAL, ['getSize' => 8, 'getScale' => 2]],
            'money()' => ['money', [], DoubleColumn::class, ColumnType::MONEY, ['getSize' => 19, 'getScale' => 4]],
            'money(8)' => ['money', [8], DoubleColumn::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 4]],
            'money(8,2)' => ['money', [8, 2], DoubleColumn::class, ColumnType::MONEY, ['getSize' => 8, 'getScale' => 2]],
            'char()' => ['char', [], StringColumn::class, ColumnType::CHAR, ['getSize' => 1]],
            'char(100)' => ['char', [100], StringColumn::class, ColumnType::CHAR, ['getSize' => 100]],
            'string()' => ['string', [], StringColumn::class, ColumnType::STRING, ['getSize' => 255]],
            'string(100)' => ['string', [100], StringColumn::class, ColumnType::STRING, ['getSize' => 100]],
            'text()' => ['text', [], StringColumn::class, ColumnType::TEXT],
            'text(5000)' => ['text', [5000], StringColumn::class, ColumnType::TEXT, ['getSize' => 5000]],
            'binary()' => ['binary', [], BinaryColumn::class, ColumnType::BINARY],
            'binary(8)' => ['binary', [8], BinaryColumn::class, ColumnType::BINARY, ['getSize' => 8]],
            'uuid()' => ['uuid', [], StringColumn::class, ColumnType::UUID],
            'datetime()' => ['datetime', [], StringColumn::class, ColumnType::DATETIME, ['getSize' => 0]],
            'datetime(3)' => ['datetime', [3], StringColumn::class, ColumnType::DATETIME, ['getSize' => 3]],
            'timestamp()' => ['timestamp', [], StringColumn::class, ColumnType::TIMESTAMP, ['getSize' => 0]],
            'timestamp(3)' => ['timestamp', [3], StringColumn::class, ColumnType::TIMESTAMP, ['getSize' => 3]],
            'date()' => ['date', [], StringColumn::class, ColumnType::DATE],
            'time()' => ['time', [], StringColumn::class, ColumnType::TIME, ['getSize' => 0]],
            'time(3)' => ['time', [3], StringColumn::class, ColumnType::TIME, ['getSize' => 3]],
            'array()' => ['array', [], ArrayColumn::class, ColumnType::ARRAY],
            'array($column)' => ['array', [$column], ArrayColumn::class, ColumnType::ARRAY, ['getColumn' => $column]],
            'structured()' => ['structured', [], StructuredColumn::class, ColumnType::STRUCTURED],
            "structured('money_currency')" => [
                'structured',
                ['money_currency'],
                StructuredColumn::class,
                ColumnType::STRUCTURED,
                ['getDbType' => 'money_currency'],
            ],
            "structured('money_currency',\$columns)" => [
                'structured',
                ['money_currency', $columns],
                StructuredColumn::class,
                ColumnType::STRUCTURED,
                ['getDbType' => 'money_currency', 'getColumns' => $columns],
            ],
            'json()' => ['json', [], JsonColumn::class, ColumnType::JSON],
        ];
    }
}
