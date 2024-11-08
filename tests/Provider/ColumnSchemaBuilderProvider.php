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
}
