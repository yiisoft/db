<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\Column\StructuredColumnSchema;

class ColumnFactoryProvider
{
    public static function definitions(): array
    {
        return [
            // definition, expected type, expected instance of, expected column method results
            '' => ['', ColumnType::STRING, StringColumnSchema::class, ['getDbType' => '']],
            'text' => ['text', ColumnType::TEXT, StringColumnSchema::class, ['getDbType' => 'text']],
            'text NOT NULL' => ['text NOT NULL', ColumnType::TEXT, StringColumnSchema::class, ['getDbType' => 'text', 'getExtra' => 'NOT NULL']],
            'char(1)' => ['char(1)', ColumnType::CHAR, StringColumnSchema::class, ['getDbType' => 'char', 'getSize' => 1]],
            'decimal(10,2)' => ['decimal(10,2)', ColumnType::DECIMAL, DoubleColumnSchema::class, ['getDbType' => 'decimal', 'getSize' => 10, 'getScale' => 2]],
            'bigint UNSIGNED' => ['bigint UNSIGNED', ColumnType::BIGINT, BigIntColumnSchema::class, ['getDbType' => 'bigint', 'isUnsigned' => true]],
        ];
    }

    public static function pseudoTypes(): array
    {
        return [
            // pseudo-type, expected type, expected instance of, expected column method results
            'pk' => [PseudoType::PK, ColumnType::INTEGER, IntegerColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'upk' => [PseudoType::UPK, ColumnType::INTEGER, IntegerColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true, 'isUnsigned' => true]],
            'bigpk' => [PseudoType::BIGPK, ColumnType::BIGINT, IntegerColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'ubigpk' => [PseudoType::UBIGPK, ColumnType::BIGINT, BigIntColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true, 'isUnsigned' => true]],
            'uuid_pk' => [PseudoType::UUID_PK, ColumnType::UUID, StringColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
            'uuid_pk_seq' => [PseudoType::UUID_PK_SEQ, ColumnType::UUID, StringColumnSchema::class, ['isPrimaryKey' => true, 'isAutoIncrement' => true]],
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
            'structured' => [ColumnType::STRUCTURED, ColumnType::STRUCTURED, StructuredColumnSchema::class],
            'json' => [ColumnType::JSON, ColumnType::JSON, JsonColumnSchema::class],
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
        ];
    }
}
