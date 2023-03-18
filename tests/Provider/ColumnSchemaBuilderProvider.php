<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\DbHelper;

class ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'db';

    public static function types(): array
    {
        return [
            ['integer NULL DEFAULT NULL', SchemaInterface::TYPE_INTEGER, null, [['unsigned'], ['null']]],
            ['integer(10)', SchemaInterface::TYPE_INTEGER, 10, [['unsigned']]],
            ['integer(10)', SchemaInterface::TYPE_INTEGER, 10, [['comment', 'test']]],
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
                SchemaInterface::TYPE_INTEGER,
                null,
                [],
            ],
            'uuid' => [
                '',
                SchemaInterface::TYPE_UUID,
                null,
                [],
            ],
            'uuid not null' => [
                '',
                SchemaInterface::TYPE_UUID,
                null,
                [['notNull']],
            ],
            'uuid with default' => [
                '',
                SchemaInterface::TYPE_UUID,
                null,
                [['defaultValue', '875343b3-6bd0-4bec-81bb-aa68bb52d945']],
            ],
            'uuid pk' => [
                '',
                SchemaInterface::TYPE_UUID_PK,
                null,
                [],
            ],
            'uuid pk not null' => [
                '',
                SchemaInterface::TYPE_UUID_PK,
                null,
                [['notNull']],
            ],
            'uuid pk not null with default' => [
                '',
                SchemaInterface::TYPE_UUID_PK,
                null,
                [],
            ],
        ];
    }
}
