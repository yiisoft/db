<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\Support\DbHelper;

class ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'db';

    public static function types(): array
    {
        return [
            ['integer NULL DEFAULT NULL', ColumnType::INTEGER, null, [['unsigned'], ['null']]],
            ['integer(10)', ColumnType::INTEGER, 10, [['unsigned']]],
            ['integer(10)', ColumnType::INTEGER, 10, [['comment', 'test']]],
            ['timestamp() WITH TIME ZONE NOT NULL', 'timestamp() WITH TIME ZONE', null, [['notNull']]],
            [
                'timestamp() WITH TIME ZONE DEFAULT NOW()',
                'timestamp() WITH TIME ZONE',
                null,
                [['defaultValue', new Expression('NOW()')]],
            ],
        ];
    }

    public static function createColumnTypes(): array
    {
        return [
            'integer' => [
                Dbhelper::replaceQuotes('[[column]] integer', static::$driverName),
                ColumnType::INTEGER,
                null,
                [],
            ],
            'uuid' => [
                '',
                ColumnType::UUID,
                null,
                [],
            ],
            'uuid not null' => [
                '',
                ColumnType::UUID,
                null,
                [['notNull']],
            ],
            'uuid with default' => [
                '',
                ColumnType::UUID,
                null,
                [['defaultValue', '875343b3-6bd0-4bec-81bb-aa68bb52d945']],
            ],
            'uuid pk' => [
                '',
                PseudoType::UUID_PK,
                null,
                [],
            ],
            'uuid pk not null' => [
                '',
                PseudoType::UUID_PK,
                null,
                [['notNull']],
            ],
            'uuid pk not null with default' => [
                '',
                PseudoType::UUID_PK,
                null,
                [],
            ],
        ];
    }
}
