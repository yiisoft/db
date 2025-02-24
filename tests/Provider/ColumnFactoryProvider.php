<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;

class ColumnFactoryProvider
{
    public static function definitions(): array
    {
        return [
            // definition, expected type, expected instance of, expected column method results
            '' => ['', ColumnType::STRING, StringColumn::class, ['getDbType' => '']],
            'text' => ['text', ColumnType::TEXT, StringColumn::class, ['getDbType' => 'text']],
            'text NOT NULL' => ['text NOT NULL', ColumnType::TEXT, StringColumn::class, ['getDbType' => 'text', 'isNotNull' => true]],
            'char(1)' => ['char(1)', ColumnType::CHAR, StringColumn::class, ['getDbType' => 'char', 'getSize' => 1]],
            'decimal(10,2)' => ['decimal(10,2)', ColumnType::DECIMAL, DoubleColumn::class, ['getDbType' => 'decimal', 'getSize' => 10, 'getScale' => 2]],
            'bigint UNSIGNED' => ['bigint UNSIGNED', ColumnType::BIGINT, BigIntColumn::class, ['getDbType' => 'bigint', 'isUnsigned' => true]],
        ];
    }

    public static function pseudoTypes(): array
    {
        return [
            // pseudo-type, expected type, expected instance of, expected column method results
            'pk' => [PseudoType::PK, ColumnType::INTEGER, IntegerColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'upk' => [PseudoType::UPK, ColumnType::INTEGER, IntegerColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true, 'isUnsigned' => true]],
            'bigpk' => [PseudoType::BIGPK, ColumnType::BIGINT, IntegerColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'ubigpk' => [PseudoType::UBIGPK, ColumnType::BIGINT, BigIntColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true, 'isUnsigned' => true]],
            'uuid_pk' => [PseudoType::UUID_PK, ColumnType::UUID, StringColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'uuid_pk_seq' => [PseudoType::UUID_PK_SEQ, ColumnType::UUID, StringColumn::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
        ];
    }

    public static function types(): array
    {
        return [
            // type, expected type, expected instance of
            'uuid' => [ColumnType::UUID, ColumnType::UUID, StringColumn::class],
            'char' => [ColumnType::CHAR, ColumnType::CHAR, StringColumn::class],
            'string' => [ColumnType::STRING, ColumnType::STRING, StringColumn::class],
            'text' => [ColumnType::TEXT, ColumnType::TEXT, StringColumn::class],
            'binary' => [ColumnType::BINARY, ColumnType::BINARY, BinaryColumn::class],
            'boolean' => [ColumnType::BOOLEAN, ColumnType::BOOLEAN, BooleanColumn::class],
            'tinyint' => [ColumnType::TINYINT, ColumnType::TINYINT, IntegerColumn::class],
            'smallint' => [ColumnType::SMALLINT, ColumnType::SMALLINT, IntegerColumn::class],
            'integer' => [ColumnType::INTEGER, ColumnType::INTEGER, IntegerColumn::class],
            'bigint' => [ColumnType::BIGINT, ColumnType::BIGINT, IntegerColumn::class],
            'float' => [ColumnType::FLOAT, ColumnType::FLOAT, DoubleColumn::class],
            'double' => [ColumnType::DOUBLE, ColumnType::DOUBLE, DoubleColumn::class],
            'decimal' => [ColumnType::DECIMAL, ColumnType::DECIMAL, DoubleColumn::class],
            'money' => [ColumnType::MONEY, ColumnType::MONEY, StringColumn::class],
            'datetime' => [ColumnType::DATETIME, ColumnType::DATETIME, StringColumn::class],
            'timestamp' => [ColumnType::TIMESTAMP, ColumnType::TIMESTAMP, StringColumn::class],
            'time' => [ColumnType::TIME, ColumnType::TIME, StringColumn::class],
            'date' => [ColumnType::DATE, ColumnType::DATE, StringColumn::class],
            'structured' => [ColumnType::STRUCTURED, ColumnType::STRUCTURED, StructuredColumn::class],
            'json' => [ColumnType::JSON, ColumnType::JSON, JsonColumn::class],
        ];
    }

    public static function defaultValueRaw(): array
    {
        return [
            // type, default value, expected value
            'null' => [ColumnType::STRING, null, null],
            '(null)' => [ColumnType::STRING, '(null)', null],
            'NULL' => [ColumnType::STRING, 'NULL', null],
            '(NULL)' => [ColumnType::STRING, '(NULL)', null],
            '' => [ColumnType::STRING, '', null],
            '(0)' => [ColumnType::INTEGER, '(0)', 0],
            '-1' => [ColumnType::INTEGER, '-1', -1],
            '(-1)' => [ColumnType::INTEGER, '(-1)', -1],
            '0.0' => [ColumnType::DOUBLE, '0.0', 0.0],
            '(0.0)' => [ColumnType::DOUBLE, '(0.0)', 0.0],
            '-1.1' => [ColumnType::DOUBLE, '-1.1', -1.1],
            '(-1.1)' => [ColumnType::DOUBLE, '(-1.1)', -1.1],
            'true' => [ColumnType::BOOLEAN, 'true', true],
            'false' => [ColumnType::BOOLEAN, 'false', false],
            '1' => [ColumnType::BOOLEAN, '1', true],
            '0' => [ColumnType::BOOLEAN, '0', false],
            "''" => [ColumnType::STRING, "''", ''],
            "('')" => [ColumnType::STRING, "('')", ''],
            "'str''ing'" => [ColumnType::STRING, "'str''ing'", "str'ing"],
            "('str''ing')" => [ColumnType::STRING, "('str''ing')", "str'ing"],
            'CURRENT_TIMESTAMP' => [ColumnType::TIMESTAMP, 'CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            '(now())' => [ColumnType::TIMESTAMP, '(now())', new Expression('(now())')],
            "timezone('UTC'::text, now())" => [ColumnType::TIMESTAMP, "timezone('UTC'::text, now())", new Expression("timezone('UTC'::text, now())")],
        ];
    }
}
