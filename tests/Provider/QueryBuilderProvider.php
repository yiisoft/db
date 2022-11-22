<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

final class QueryBuilderProvider
{
    private function buildFrom(): array
    {
        return [
            [
                'table1',
                <<<SQL
                SELECT * FROM table1
                SQL,
            ],
            [
                ['table1'],
                <<<SQL
                SELECT * FROM table1
                SQL,
            ],
            [
                new Expression('table2'),
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                [new Expression('table2')],
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                ['alias' => 'table3'],
                <<<SQL
                SELECT * FROM table3 alias
                SQL,
            ],
            [
                ['alias' => new Expression('table4')],
                <<<SQL
                SELECT * FROM table4 alias
                SQL,
            ],
            [
                ['alias' => new Expression('func(:param1, :param2)', ['param1' => 'A', 'param2' => 'B'])],
                <<<SQL
                SELECT * FROM func(:param1, :param2) alias
                SQL,
                ['param1' => 'A', 'param2' => 'B'],
            ],
        ];
    }
}
