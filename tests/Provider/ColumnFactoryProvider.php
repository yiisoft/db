<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\EnumColumn;
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
            '' => ['', new StringColumn(dbType: '')],
            'text' => ['text', new StringColumn(ColumnType::TEXT, dbType: 'text')],
            'text NOT NULL' => ['text NOT NULL', new StringColumn(ColumnType::TEXT, dbType: 'text', notNull: true)],
            'char(1)' => ['char(1)', new StringColumn(ColumnType::CHAR, dbType: 'char', size: 1)],
            'decimal(10,2)' => ['decimal(10,2)', new StringColumn(ColumnType::DECIMAL, dbType: 'decimal', scale: 2, size: 10)],
            'bigint UNSIGNED' => ['bigint UNSIGNED', new BigIntColumn(dbType: 'bigint', unsigned: true)],
            'integer[]' => ['integer[]', new ArrayColumn(dbType: 'integer', column: new IntegerColumn(dbType: 'integer'))],
            'string(126)[][]' => ['string(126)[][]', new ArrayColumn(size: 126, dimension: 2, column: new StringColumn(size: 126))],
        ];
    }

    public static function pseudoTypes(): array
    {
        return [
            // pseudo-type, expected type, expected instance of, expected column method results
            'pk' => [PseudoType::PK, new IntegerColumn(primaryKey: true, autoIncrement: true)],
            'upk' => [PseudoType::UPK, new IntegerColumn(primaryKey: true, autoIncrement: true, unsigned: true)],
            'bigpk' => [PseudoType::BIGPK, new IntegerColumn(ColumnType::BIGINT, primaryKey: true, autoIncrement: true)],
            'ubigpk' => [PseudoType::UBIGPK, new BigIntColumn(primaryKey: true, autoIncrement: true, unsigned: true)],
            'uuid_pk' => [PseudoType::UUID_PK, new StringColumn(ColumnType::UUID, primaryKey: true, autoIncrement: true)],
            'uuid_pk_seq' => [PseudoType::UUID_PK_SEQ, new StringColumn(ColumnType::UUID, primaryKey: true, autoIncrement: true)],
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
            'decimal' => [ColumnType::DECIMAL, ColumnType::DECIMAL, StringColumn::class],
            'money' => [ColumnType::MONEY, ColumnType::MONEY, StringColumn::class],
            'timestamp' => [ColumnType::TIMESTAMP, ColumnType::TIMESTAMP, DateTimeColumn::class],
            'datetime' => [ColumnType::DATETIME, ColumnType::DATETIME, DateTimeColumn::class],
            'datetimetz' => [ColumnType::DATETIMETZ, ColumnType::DATETIMETZ, DateTimeColumn::class],
            'time' => [ColumnType::TIME, ColumnType::TIME, DateTimeColumn::class],
            'timetz' => [ColumnType::TIMETZ, ColumnType::TIMETZ, DateTimeColumn::class],
            'date' => [ColumnType::DATE, ColumnType::DATE, DateTimeColumn::class],
            'array' => [ColumnType::ARRAY, ColumnType::ARRAY, ArrayColumn::class],
            'structured' => [ColumnType::STRUCTURED, ColumnType::STRUCTURED, StructuredColumn::class],
            'json' => [ColumnType::JSON, ColumnType::JSON, JsonColumn::class],
            'enum' => [ColumnType::ENUM, ColumnType::ENUM, EnumColumn::class],
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
