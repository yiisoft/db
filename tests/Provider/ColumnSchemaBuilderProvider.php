<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Schema;

final class ColumnSchemaBuilderProvider
{
    public function types(): array
    {
        return [
            ['integer NULL DEFAULT NULL', Schema::TYPE_INTEGER, null, [
                ['unsigned'], ['null'],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['timestamp() WITH TIME ZONE NOT NULL', 'timestamp() WITH TIME ZONE', null, [
                ['notNull'],
            ]],
            ['timestamp() WITH TIME ZONE DEFAULT NOW()', 'timestamp() WITH TIME ZONE', null, [
                ['defaultValue', new Expression('NOW()')],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
        ];
    }
}
