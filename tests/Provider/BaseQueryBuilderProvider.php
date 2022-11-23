<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\Support\DbHelper;

final class BaseQueryBuilderProvider
{
    public function buildFrom(ConnectionPDOInterface $db): array
    {
        return [
            [
                'table1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    $db->getname(),
                ),
            ],
            [
                ['table1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    $db->getname(),
                ),
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
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table3]] [[alias]]
                    SQL,
                    $db->getname(),
                ),
            ],
            [
                ['alias' => new Expression('table4')],
                <<<SQL
                SELECT * FROM table4 [alias]
                SQL,
            ],
            [
                ['alias' => new Expression('func(:param1, :param2)', ['param1' => 'A', 'param2' => 'B'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM func(:param1, :param2) [[alias]]
                    SQL,
                    $db->getname(),
                ),
                ['param1' => 'A', 'param2' => 'B'],
            ],
        ];
    }
}
