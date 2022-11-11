<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Expression\Expression;

abstract class AbstractColumnSchemaBuilderProvider
{
    public function types(): array
    {
        return [
            'integer-1' => ['integer NULL DEFAULT NULL', Schema::TYPE_INTEGER, null, [['unsigned'], ['null']]],
            'integer-2' => ['integer(10)', Schema::TYPE_INTEGER, 10, [['unsigned']]],
            'integer-3' => ['integer(10)', Schema::TYPE_INTEGER, 10, [['comment', 'test']]],
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
