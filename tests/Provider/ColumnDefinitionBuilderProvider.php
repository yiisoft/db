<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\TestTrait;

class ColumnDefinitionBuilderProvider
{
    use TestTrait;

    public static function build(): array
    {
        $result = [];

        $column = new StringColumn();
        $result[] = ['varchar', $column];
        $result[] = ['varchar bar', ($column = clone $column)->extra('bar')];
        $result[] = ['varchar foo', ($column = clone $column)->extra('foo')];
        $result[] = ['varchar', (clone $column)->extra('')];

        $column = new StringColumn();
        $result[] = ['varchar CHECK (value > 5)', $column->check('value > 5')];
        $result[] = ['varchar', ($column = clone $column)->check('')];
        $result[] = ['varchar', (clone $column)->check(null)];

        $column = new StringColumn();
        $result[] = ['varchar', $column->comment('comment')];
        $result[] = ['varchar', ($column = clone $column)->comment('')];
        $result[] = ['varchar', (clone $column)->comment(null)];

        $column = new StringColumn();
        $result[] = ["varchar DEFAULT 'value'", $column->defaultValue('value')];
        $result[] = ["varchar DEFAULT ''", ($column = clone $column)->defaultValue('')];
        $result[] = ['varchar', ($column = clone $column)->defaultValue(null)];
        $result[] = ["varchar DEFAULT 'expression'", ($column = clone $column)->defaultValue(new Expression("'expression'"))];
        $result[] = ['varchar DEFAULT ', ($column = clone $column)->defaultValue(new Expression(''))];
        $result[] = ['varchar NULL DEFAULT NULL', (clone $column)->allowNull()->defaultValue(null)];

        $column = new StringColumn();
        $result[] = ['varchar NULL DEFAULT NULL', $column->allowNull()];
        $result[] = ['varchar NOT NULL', (clone $column)->allowNull(false)];

        $result[] = ['varchar UNIQUE', (new StringColumn())->unique()];

        $column = ColumnBuilder::pk();
        $result[] = ['integer PRIMARY KEY', $column];
        $result[] = ['integer UNSIGNED PRIMARY KEY', (clone $column)->unsigned()];

        $column = ColumnBuilder::bigpk();
        $result[] = ['bigint PRIMARY KEY', $column];
        $result[] = ['bigint UNSIGNED PRIMARY KEY', (clone $column)->unsigned()];

        return $result;
    }
/*
    public static function buildColumnDefinition(): array
    {
        return [
            // Primary key columns
            'pk' => [ColumnBuilder::pk(), 'integer PRIMARY KEY'],
            'upk' => [ColumnBuilder::upk(), 'integer UNSIGNED PRIMARY KEY'],
            'bigpk' => [ColumnBuilder::bigpk(), 'bigint PRIMARY KEY'],
            'ubigpk' => [ColumnBuilder::ubigpk(), 'bigint UNSIGNED PRIMARY KEY'],
            'uuidpk' => [ColumnBuilder::uuidpk(), 'uuid PRIMARY KEY'],
            'uuidpkseq' => [ColumnBuilder::uuidpkseq(), 'uuid PRIMARY KEY'],
            // Abstract types
            SchemaInterface::TYPE_UUID => [SchemaInterface::TYPE_UUID, 'char(36)'],
            SchemaInterface::TYPE_CHAR => [SchemaInterface::TYPE_CHAR, 'char(1)'],
            SchemaInterface::TYPE_STRING => [SchemaInterface::TYPE_STRING, 'varchar(255)'],
            SchemaInterface::TYPE_TEXT => [SchemaInterface::TYPE_TEXT, 'text'],
            SchemaInterface::TYPE_BINARY => [SchemaInterface::TYPE_BINARY, 'binary(255)'],
            SchemaInterface::TYPE_BOOLEAN => [SchemaInterface::TYPE_BOOLEAN, 'boolean'],
            SchemaInterface::TYPE_TINYINT => [SchemaInterface::TYPE_TINYINT, 'tinyint'],
            SchemaInterface::TYPE_SMALLINT => [SchemaInterface::TYPE_SMALLINT, 'smallint'],
            SchemaInterface::TYPE_INTEGER => [SchemaInterface::TYPE_INTEGER, 'integer'],
            SchemaInterface::TYPE_BIGINT => [SchemaInterface::TYPE_BIGINT, 'bigint'],
            SchemaInterface::TYPE_FLOAT => [SchemaInterface::TYPE_FLOAT, 'float'],
            SchemaInterface::TYPE_DOUBLE => [SchemaInterface::TYPE_DOUBLE, 'double'],
            SchemaInterface::TYPE_DECIMAL => [SchemaInterface::TYPE_DECIMAL, 'decimal(10,0)'],
            SchemaInterface::TYPE_MONEY => [SchemaInterface::TYPE_MONEY, 'money(19,4)'],
            SchemaInterface::TYPE_DATETIME => [SchemaInterface::TYPE_DATETIME, 'datetime(0)'],
            SchemaInterface::TYPE_TIMESTAMP => [SchemaInterface::TYPE_TIMESTAMP, 'timestamp(0)'],
            SchemaInterface::TYPE_TIME => [SchemaInterface::TYPE_TIME, 'time(0)'],
            SchemaInterface::TYPE_DATE => [SchemaInterface::TYPE_DATE, 'date'],
            SchemaInterface::TYPE_JSON => [SchemaInterface::TYPE_JSON, 'json'],
        ];
    }
*/
}
